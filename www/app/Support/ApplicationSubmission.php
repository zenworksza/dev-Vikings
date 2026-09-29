<?php

namespace App\Support;

use App\Enums\DocumentType;
use App\Models\FranchiseeApplication;

class ApplicationSubmission
{
    /**
     * Required documents the applicant has not uploaded yet.
     *
     * @return list<DocumentType>
     */
    public static function missingDocuments(FranchiseeApplication $application): array
    {
        $entityType = $application->data['entity_type'] ?? null;
        $uploaded = $application->documents()->pluck('type')->all();

        return array_values(array_filter(
            DocumentType::cases(),
            fn (DocumentType $type) => $type->isRequired($entityType) && ! in_array($type->value, $uploaded, true),
        ));
    }
}
