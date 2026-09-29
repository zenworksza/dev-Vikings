<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * Franchisees manage only their own locations. Platform admins manage all of
 * them and can create them — the franchisor opens locations itself (company-
 * owned) and reassigns them when a location is sold or taken back.
 */
class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['franchisee', 'platform_admin']);
    }

    public function view(User $user, Location $location): bool
    {
        return $user->hasRole('platform_admin') || $this->owns($user, $location);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['franchisee', 'platform_admin']);
    }

    public function update(User $user, Location $location): bool
    {
        return $user->hasRole('platform_admin') || $this->owns($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->hasRole('platform_admin') || $this->owns($user, $location);
    }

    public function restore(User $user, Location $location): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return false;
    }

    private function owns(User $user, Location $location): bool
    {
        return $user->hasRole('franchisee') && $location->user_id === $user->id;
    }
}
