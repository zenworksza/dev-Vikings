<?php

use App\Enums\ApplicationStatus;
use App\Exceptions\InvalidApplicationTransition;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Services\ApplicationWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->workflow = app(ApplicationWorkflow::class);
    $this->admin = User::factory()->create()->assignRole('platform_admin');
    $this->applicant = User::factory()->create();
    $this->application = FranchiseeApplication::create(['user_id' => $this->applicant->id]);
});

test('a new application starts as a draft', function () {
    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Draft);
});

test('the happy path ends approved with the franchisee role granted', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);
    $this->workflow->approve($this->application, $this->admin, 'Welcome aboard');

    $application = $this->application->fresh();

    expect($application->status)->toBe(ApplicationStatus::Approved)
        ->and($application->submitted_at)->not->toBeNull()
        ->and($application->reviewed_at)->not->toBeNull()
        ->and($application->reviewed_by)->toBe($this->admin->id)
        ->and($application->decision_reason)->toBe('Welcome aboard')
        ->and($this->applicant->fresh()->hasRole('franchisee'))->toBeTrue();
});

test('every transition is recorded in the history', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);

    $history = $this->application->history()->reorder('id')->get();

    expect($history)->toHaveCount(2)
        ->and($history[0]->from_status)->toBe(ApplicationStatus::Draft)
        ->and($history[0]->to_status)->toBe(ApplicationStatus::Submitted)
        ->and($history[0]->actor_id)->toBe($this->applicant->id)
        ->and($history[1]->to_status)->toBe(ApplicationStatus::UnderReview);
});

test('invalid transitions are rejected and change nothing', function () {
    expect(fn () => $this->workflow->approve($this->application, $this->admin))
        ->toThrow(InvalidApplicationTransition::class);

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Draft)
        ->and($this->application->history()->count())->toBe(0)
        ->and($this->applicant->fresh()->hasRole('franchisee'))->toBeFalse();
});

test('approved and rejected are final', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);
    $this->workflow->reject($this->application, $this->admin, 'Not a fit');

    expect(fn () => $this->workflow->approve($this->application, $this->admin))
        ->toThrow(InvalidApplicationTransition::class);
    expect($this->applicant->fresh()->hasRole('franchisee'))->toBeFalse();
});

test('requesting changes or rejecting requires a reason', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);

    expect(fn () => $this->workflow->requestChanges($this->application, $this->admin, ''))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $this->workflow->reject($this->application, $this->admin, '  '))
        ->toThrow(InvalidArgumentException::class);

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::UnderReview);
});

test('the applicant can resubmit after changes are requested', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);
    $this->workflow->requestChanges($this->application, $this->admin, 'Upload a clearer ID');

    expect($this->application->fresh()->decision_reason)->toBe('Upload a clearer ID');

    $this->workflow->submit($this->application, $this->applicant);

    $application = $this->application->fresh();
    expect($application->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->decision_reason)->toBeNull();
});

test('the applicant is emailed on submit, changes, approval and rejection but not on start of review', function () {
    $this->workflow->submit($this->application, $this->applicant);
    $this->workflow->startReview($this->application, $this->admin);
    $this->workflow->approve($this->application, $this->admin);

    Notification::assertSentTo($this->applicant, ApplicationStatusChanged::class, function ($n, $channels) {
        return $n->status === ApplicationStatus::Submitted && $channels === ['mail'];
    });
    Notification::assertSentTo($this->applicant, ApplicationStatusChanged::class, function ($n, $channels) {
        return $n->status === ApplicationStatus::Approved && $channels === ['mail'];
    });
    // Start of review has no channels, so nothing is sent for it.
    Notification::assertNotSentTo($this->applicant, ApplicationStatusChanged::class, function ($n) {
        return $n->status === ApplicationStatus::UnderReview;
    });
    Notification::assertSentToTimes($this->applicant, ApplicationStatusChanged::class, 2);
});
