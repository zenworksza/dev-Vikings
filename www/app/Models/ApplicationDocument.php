<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property DocumentStatus $status
 */
#[Fillable(['franchisee_application_id', 'type', 'disk', 'path', 'encrypted', 'original_name', 'mime_type', 'size', 'status', 'review_note', 'reviewed_by', 'reviewed_at'])]
class ApplicationDocument extends Model
{
    protected $attributes = [
        'status' => 'pending',
        'encrypted' => true,
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'size' => 'integer',
            'encrypted' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<FranchiseeApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(FranchiseeApplication::class, 'franchisee_application_id');
    }
}
