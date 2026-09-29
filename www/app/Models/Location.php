<?php

namespace App\Models;

use App\Enums\LocationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A restaurant location owned by a franchisee. New locations are active
 * straight away (the admin list is after-the-fact oversight, not a gate).
 *
 * @property LocationStatus $status
 * @property User $user
 */
#[Fillable([
    'user_id', 'name', 'slug', 'status', 'description',
    'address_line1', 'address_line2', 'suburb', 'city', 'province', 'postal_code', 'country',
    'phone', 'contact_email',
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
