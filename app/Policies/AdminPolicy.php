<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the actor can view or manage the target user.
     */
    public function manage(User $actor, User $target): bool
    {
        // Target is Super Admin
        if ($target->hasRole(RoleName::SUPER_ADMIN->value)) {
            // Admin can NEVER touch Super Admin
            if (! $actor->hasRole(RoleName::SUPER_ADMIN->value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the actor can view the target user.
     */
    public function view(User $actor, User $target): bool
    {
        if ($target->hasRole(RoleName::SUPER_ADMIN->value) && ! $actor->hasRole(RoleName::SUPER_ADMIN->value)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the target account can be deleted or disabled (self-lockout protection).
     */
    public function disableOrDelete(User $actor, User $target): bool
    {
        if (! $this->manage($actor, $target)) {
            return false;
        }

        // Self-lockout check: if target is super_admin, make sure at least one other active super_admin exists
        if ($target->hasRole(RoleName::SUPER_ADMIN->value)) {
            $otherActiveSuperAdmins = User::role(RoleName::SUPER_ADMIN->value)
                ->where('id', '!=', $target->id)
                ->where('is_active', true)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return false;
            }
        }

        return true;
    }
}
