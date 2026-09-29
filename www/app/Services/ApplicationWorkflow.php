<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\DocumentsNotVerified;
use App\Exceptions\InvalidApplicationTransition;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Support\ApplicationSubmission;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only place an application's status changes. Guards the transition,
 * records it in the audit history, stamps review fields, grants the
 * `franchisee` role on approval, and notifies the applicant.
 */
class ApplicationWorkflow
{
    public function submit(FranchiseeApplication $application, User $actor): FranchiseeApplication
    {
        return $this->transition($application, ApplicationStatus::Submitted, $actor);
    }

    public function startReview(FranchiseeApplication $application, User $admin): FranchiseeApplication
    {
        return $this->transition($application, ApplicationStatus::UnderReview, $admin);
    }

    public function requestChanges(FranchiseeApplication $application, User $admin, string $reason): FranchiseeApplication
    {
        return $this->transition($application, ApplicationStatus::ChangesRequested, $admin, $reason);
    }

    public function approve(FranchiseeApplication $application, User $admin, ?string $note = null): FranchiseeApplication
    {
        return $this->transition($application, ApplicationStatus::Approved, $admin, $note);
    }

    public function reject(FranchiseeApplication $application, User $admin, string $reason): FranchiseeApplication
    {
        return $this->transition($application, ApplicationStatus::Rejected, $admin, $reason);
    }

    public function transition(
        FranchiseeApplication $application,
        ApplicationStatus $to,
        User $actor,
        ?string $note = null,
    ): FranchiseeApplication {
        $from = $application->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidApplicationTransition::between($from, $to);
        }

        if (in_array($to, [ApplicationStatus::ChangesRequested, ApplicationStatus::Rejected], true) && blank($note)) {
            throw new InvalidArgumentException("A reason is required to move an application to '{$to->label()}'.");
        }

        if ($to === ApplicationStatus::Approved) {
            $unverified = ApplicationSubmission::unverifiedDocuments($application);

            if ($unverified !== []) {
                throw DocumentsNotVerified::for($unverified);
            }
        }

        DB::transaction(function () use ($application, $from, $to, $actor, $note) {
            $attributes = ['status' => $to];

            if ($to === ApplicationStatus::Submitted) {
                $attributes['submitted_at'] = now();
                // A resubmission clears the previous reviewer's decision.
                $attributes['decision_reason'] = null;
            }

            if (in_array($to, [ApplicationStatus::ChangesRequested, ApplicationStatus::Approved, ApplicationStatus::Rejected], true)) {
                $attributes['reviewed_at'] = now();
                $attributes['reviewed_by'] = $actor->getKey();
                $attributes['decision_reason'] = $note;
            }

            $application->update($attributes);

            $application->history()->create([
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $actor->getKey(),
                'note' => $note,
            ]);

            if ($to === ApplicationStatus::Approved) {
                $application->user->assignRole('franchisee');
            }
        });

        $application->user->notify(new ApplicationStatusChanged($application, $to));

        return $application;
    }
}
