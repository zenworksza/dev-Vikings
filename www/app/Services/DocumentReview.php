<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentStatus;
use App\Models\ApplicationDocument;
use App\Models\User;
use DomainException;
use InvalidArgumentException;

/** An admin's decision on one uploaded document. */
class DocumentReview
{
    /** Documents can be reviewed while the application is with the reviewer. */
    public function canReview(ApplicationDocument $document): bool
    {
        return in_array($document->application->status, [ApplicationStatus::Submitted, ApplicationStatus::UnderReview], true);
    }

    public function accept(ApplicationDocument $document, User $admin, ?string $note = null): ApplicationDocument
    {
        return $this->decide($document, DocumentStatus::Accepted, $admin, $note);
    }

    public function reject(ApplicationDocument $document, User $admin, string $note): ApplicationDocument
    {
        if (blank($note)) {
            throw new InvalidArgumentException('A reason is required to reject a document.');
        }

        return $this->decide($document, DocumentStatus::Rejected, $admin, $note);
    }

    private function decide(ApplicationDocument $document, DocumentStatus $status, User $admin, ?string $note): ApplicationDocument
    {
        if (! $this->canReview($document)) {
            throw new DomainException('This application is not currently open for document review.');
        }

        $document->update([
            'status' => $status,
            'review_note' => $note,
            'reviewed_by' => $admin->getKey(),
            'reviewed_at' => now(),
        ]);

        return $document;
    }
}
