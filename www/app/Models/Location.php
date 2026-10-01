<?php

namespace App\Models;

use App\Enums\LocationStatus;
use App\Services\LocationOwnershipRecorder;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A restaurant location. `user_id` is the franchisee who owns it; a null
 * owner means it is company-owned (run by the franchisor — opened before
 * being sold on, or taken back from a franchisee). New locations are active
 * straight away. `table_count` and `seat_capacity` (total pax seated) are the
 * limits bookings are checked against.
 *
 * @property LocationStatus $status
 * @property User|null $user
 */
#[Fillable([
    'user_id', 'name', 'slug', 'status', 'description',
    'address_line1', 'address_line2', 'suburb', 'city', 'province', 'postal_code', 'country',
    'phone', 'contact_email', 'table_count', 'seat_capacity',
])]
class Location extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => 'active',
        'country' => 'South Africa',
    ];

    protected static function booted(): void
    {
        // Slugs are the stable public URL, so they are generated once
        // (name + city, made unique) and never change on rename.
        static::creating(function (Location $location) {
            if (blank($location->slug)) {
                $location->slug = self::uniqueSlug($location->name.' '.$location->city);
            }
        });

        // Every ownership event is recorded (and the franchisee told) here so
        // no code path can change an owner silently.
        static::created(fn (Location $location) => app(LocationOwnershipRecorder::class)->recordCreation($location));

        static::updated(function (Location $location) {
            if ($location->wasChanged('user_id')) {
                app(LocationOwnershipRecorder::class)->recordChange(
                    $location,
                    $location->getOriginal('user_id'),
                    $location->user_id,
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => LocationStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LocationOwnershipHistory, $this> */
    public function ownershipHistory(): HasMany
    {
        return $this->hasMany(LocationOwnershipHistory::class)->latest('id');
    }

    /** @return HasMany<LocationBusinessHour, $this> */
    public function businessHours(): HasMany
    {
        return $this->hasMany(LocationBusinessHour::class);
    }

    /** @return HasMany<LocationSpecialDay, $this> */
    public function specialDays(): HasMany
    {
        return $this->hasMany(LocationSpecialDay::class);
    }

    /**
     * Opening window for a date as ['HH:MM', 'HH:MM'], or null when closed.
     * A special day overrides the weekly hours.
     *
     * @return array{0: string, 1: string}|null
     */
    public function hoursOn(CarbonInterface $date): ?array
    {
        $special = $this->specialDays()->whereDate('date', $date->toDateString())->first();

        $row = $special ?? $this->businessHours()->where('day_of_week', $date->dayOfWeek)->first();

        if ($row === null || $row->opens_at === null) {
            return null;
        }

        return [substr($row->opens_at, 0, 5), substr($row->closes_at, 0, 5)];
    }

    public function isCompanyOwned(): bool
    {
        return $this->user_id === null;
    }

    /** @param  Builder<Location>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', LocationStatus::Active->value);
    }

    /** One-line address for lists. */
    public function fullAddress(): string
    {
        return collect([$this->address_line1, $this->address_line2, $this->suburb, $this->city, $this->province, $this->postal_code])
            ->filter()
            ->implode(', ');
    }

    private static function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'location';
        $slug = $base;

        for ($i = 2; self::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
