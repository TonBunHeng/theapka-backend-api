<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    /**
     * Determine whether the user can view the guest.
     */
    public function view(User $user, Guest $guest): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('guests.view');
        }

        return $this->ownsGuestWedding($user, $guest);
    }

    /**
     * Determine whether the user can update the guest.
     */
    public function update(User $user, Guest $guest): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('guests.view');
        }

        return $this->ownsGuestWedding($user, $guest);
    }

    /**
     * Determine whether the user can delete the guest.
     */
    public function delete(User $user, Guest $guest): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('guests.view');
        }

        return $this->ownsGuestWedding($user, $guest);
    }

    /**
     * Helper to verify wedding ownership or membership.
     */
    protected function ownsGuestWedding(User $user, Guest $guest): bool
    {
        $wedding = \App\Models\Wedding::withoutGlobalScopes()->find($guest->wedding_id);
        if (! $wedding) {
            return false;
        }

        if ($wedding->owner_id === $user->id) {
            return true;
        }

        return $wedding->members()->where('user_id', $user->id)->exists();
    }
}
