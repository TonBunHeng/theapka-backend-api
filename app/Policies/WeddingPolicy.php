<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\Wedding;

class WeddingPolicy
{
    /**
     * Determine whether the user can view the wedding.
     */
    public function view(User $user, Wedding $wedding): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('weddings.view');
        }

        return $this->isMemberOrOwner($user, $wedding);
    }

    /**
     * Determine whether the user can update the wedding.
     */
    public function update(User $user, Wedding $wedding): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('weddings.edit');
        }

        return $this->isMemberOrOwner($user, $wedding);
    }

    /**
     * Determine whether the user can delete the wedding.
     */
    public function delete(User $user, Wedding $wedding): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('weddings.delete');
        }

        return $wedding->owner_id === $user->id;
    }

    /**
     * Helper to check if user is owner or accepted member.
     */
    protected function isMemberOrOwner(User $user, Wedding $wedding): bool
    {
        if ($wedding->owner_id === $user->id) {
            return true;
        }

        return $wedding->members()->where('user_id', $user->id)->exists();
    }
}
