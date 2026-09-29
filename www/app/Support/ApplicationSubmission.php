<?php

namespace App\Support;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\FranchiseeApplication;

class ApplicationSubmission
{
    /**
     * Required documents the applicant still has to upload. A rejected
     * document does not count — they must replace it.
     *
     * @return list<DocumentType>
     */
    public static function missingDocuments(FranchiseeApplication $application): array
    {
        return self::requiredTypesWithout($application, [DocumentStatus::Pending, DocumentStatus::Accepted]);
    }

    /**
     * Required documents with no accepted copy yet — what stands between an
     * application and approval.
     *
     * @return list<DocumentType>
     */
    public static function unverifiedDocuments(FranchiseeApplication $application): array
    {
        return self::requiredTypesWithout($application, [DocumentStatus::Accepted]);
    }

    /**
     * @param  list<DocumentStatus>  $satisfying  Statuses that count as "has one".
     * @return list<DocumentType>
     */
    private static function requiredTypesWithout(FranchiseeApplication $application, array $satisfying): array
    {
        $entityType = $application->data['entity_type'] ?? null;
        $have = $application->documents()
            ->whereIn('status', array_map(fn (DocumentStatus $s) => $s->value, $satisfying))
            ->pluck('type')
            ->all();

        return array_values(array_filter(
            DocumentType::cases(),
            fn (DocumentType $type) => $type->isRequired($entityType) && ! in_array($type->value, $have, true),
        ));
    }
}
