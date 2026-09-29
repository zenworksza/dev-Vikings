<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['franchisee_application_id', 'type', 'disk', 'path', 'original_name', 'mime_type', 'size', 'status', 'review_note'])]
class ApplicationDocument extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'size' => 'integer',
        ];
    }

    /** @return BelongsTo<FranchiseeApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(FranchiseeApplication::class, 'franchisee_application_id');
    }
}
