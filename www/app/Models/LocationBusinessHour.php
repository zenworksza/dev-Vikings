<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Regular opening hours for one weekday at a location. */
#[Fillable(['location_id', 'day_of_week', 'opens_at', 'closes_at'])]
class LocationBusinessHour extends Model
{
    public const DAYS = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday',
    ];

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week];
    }
}
