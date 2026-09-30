<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** An entry in the franchisor's master service catalogue. */
#[Fillable(['name', 'description', 'default_duration_minutes', 'default_price_cents', 'is_active', 'sort_order'])]
class Service extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<LocationService, $this> */
    public function locationServices(): HasMany
    {
        return $this->hasMany(LocationService::class);
    }

    /** @param  Builder<Service>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
