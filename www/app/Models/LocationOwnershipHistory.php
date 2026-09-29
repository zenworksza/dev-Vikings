<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One ownership event for a location. Append-only. */
#[Fillable([
    'location_id', 'from_user_id', 'to_user_id', 'from_owner_name', 'to_owner_name',
    'is_creation', 'note', 'actor_id', 'actor_name',
])]
class LocationOwnershipHistory extends Model
{
    protected $table = 'location_ownership_history';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['is_creation' => 'boolean'];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Short label for the kind of event. */
    public function event(): string
    {
        return match (true) {
            $this->is_creation && $this->to_owner_name === null => 'Opened (company-owned)',
            $this->is_creation => 'Opened for franchisee',
            $this->from_owner_name === null => 'Sold / assigned to franchisee',
            $this->to_owner_name === null => 'Taken back by franchisor',
            default => 'Transferred between franchisees',
        };
    }

    /** "Alice → company", "company → Bob", etc. */
    public function change(): string
    {
        return ($this->from_owner_name ?? 'Franchisor').' → '.($this->to_owner_name ?? 'Franchisor');
    }
}
