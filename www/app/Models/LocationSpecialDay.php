<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A date that overrides the weekly hours: closed, or open on different hours. */
#[Fillable(['location_id', 'date', 'label', 'opens_at', 'closes_at'])]
class LocationSpecialDay extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isClosed(): bool
    {
        return $this->opens_at === null;
    }
}
