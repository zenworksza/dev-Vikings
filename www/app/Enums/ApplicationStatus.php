<?php

namespace App\Enums;

/**
 * Lifecycle of a franchisee application. Transitions are guarded by
 * canTransitionTo() and applied through App\Services\ApplicationWorkflow.
 */
enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::ChangesRequested => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::UnderReview],
            self::UnderReview => [self::ChangesRequested, self::Approved, self::Rejected],
            self::ChangesRequested => [self::Submitted],
            self::Approved, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** The applicant can still edit the application in these states. */
    public function isEditableByApplicant(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
