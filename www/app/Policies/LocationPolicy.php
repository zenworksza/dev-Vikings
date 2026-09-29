<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * Franchisees manage only their own locations; platform admins can see and
 * correct all of them (oversight), but locations are always created by
 * franchisees.
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
        return $user->hasRole('franchisee');
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
