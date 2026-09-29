<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\FranchiseeApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * Give an application an accepted copy of every document approval requires
 * (no real file needed — approval only looks at the review status).
 */
function acceptRequiredDocuments(FranchiseeApplication $application): void
{
    $entityType = $application->data['entity_type'] ?? null;

    foreach (DocumentType::cases() as $type) {
        if ($type->isRequired($entityType)) {
            $application->documents()->create([
                'type' => $type->value, 'disk' => 'local', 'path' => 'applications/test/'.$type->value.'.enc',
                'original_name' => $type->value.'.pdf', 'status' => DocumentStatus::Accepted,
            ]);
        }
    }
}
