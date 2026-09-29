<?php

namespace App\Services;

use App\Models\Location;
use App\Models\LocationOwnershipHistory;
use App\Models\User;
use App\Notifications\LocationOwnershipChanged;

/**
 * Called by the Location model whenever a location is created or its owner
 * changes, so every code path (admin, franchisee portal, console) is recorded
 * and notified the same way. Writes an append-only history row, and emails
 * the franchisee who gained or lost the location — unless they made the
 * change themselves.
 */
class LocationOwnershipRecorder
{
    public function recordCreation(Location $location): void
    {
        $this->write($location, from: null, to: $location->user_id, creation: true);

        $this->notifyIfSomeoneElse($location->user_id, $location, LocationOwnershipChanged::ASSIGNED);
    }

    public function recordChange(Location $location, ?int $fromUserId, ?int $toUserId): void
    {
        $this->write($location, from: $fromUserId, to: $toUserId, creation: false);

        $this->notifyIfSomeoneElse($toUserId, $location, LocationOwnershipChanged::ASSIGNED);
        $this->notifyIfSomeoneElse($fromUserId, $location, LocationOwnershipChanged::REMOVED);
    }

    /** Owner account deleted: their locations revert to the franchisor. */
    public function recordOwnerRemoved(Location $location, User $owner, string $note): void
    {
        $this->write($location, from: $owner->id, to: null, creation: false, note: $note);
    }

    private function write(Location $location, ?int $from, ?int $to, bool $creation, ?string $note = null): void
    {
        $actor = auth()->user();

        LocationOwnershipHistory::create([
            'location_id' => $location->getKey(),
            'from_user_id' => $from,
            'to_user_id' => $to,
            'from_owner_name' => $from ? User::find($from)?->name : null,
            'to_owner_name' => $to ? User::find($to)?->name : null,
            'is_creation' => $creation,
            'note' => $note,
            'actor_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
        ]);
    }

    private function notifyIfSomeoneElse(?int $recipientId, Location $location, string $kind): void
    {
        if ($recipientId === null || $recipientId === auth()->id()) {
            return;
        }

        User::find($recipientId)?->notify(new LocationOwnershipChanged($location, $kind));
    }
}
