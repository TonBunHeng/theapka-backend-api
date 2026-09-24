<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    /**
     * Determine whether the user can view templates.
     */
    public function view(User $user, ?Template $template = null): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::SUPER_ADMIN->value) || $user->can('templates.create');
    }

    /**
     * Determine whether the user can update the template.
     */
    public function update(User $user, Template $template): bool
    {
        return $user->hasRole(RoleName::SUPER_ADMIN->value) || $user->can('templates.edit');
    }

    /**
     * Determine whether the user can delete the template.
     */
    public function delete(User $user, Template $template): bool
    {
        return $user->hasRole(RoleName::SUPER_ADMIN->value) || $user->can('templates.delete');
    }

    /**
     * Determine whether the user can publish the template.
     */
    public function publish(User $user, Template $template): bool
    {
        return $user->hasRole(RoleName::SUPER_ADMIN->value) || $user->can('templates.publish');
    }
}
