<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\GiftRecord;
use App\Models\User;

class GiftRecordPolicy
{
    /**
     * Determine whether the user can view the gift record.
     */
    public function view(User $user, GiftRecord $giftRecord): bool
    {
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return $user->can('gifts.view');
        }

        $wedding = \App\Models\Wedding::withoutGlobalScopes()->find($giftRecord->wedding_id);
        if (! $wedding) {
            return false;
        }

        return $wedding->owner_id === $user->id || $wedding->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Gift records can NEVER be updated by anyone.
     */
    public function update(User $user, GiftRecord $giftRecord): bool
    {
        return false;
    }

    /**
     * Gift records can NEVER be deleted by anyone.
     */
    public function delete(User $user, GiftRecord $giftRecord): bool
    {
        return false;
    }
}
