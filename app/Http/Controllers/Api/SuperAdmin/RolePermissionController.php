<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateRolePermissionsRequest;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Get the role-to-permissions matrix and definitions.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->name] = $role->permissions->pluck('name')->values()->all();
        }

        return response()->json([
            'data' => [
                'matrix' => $matrix,
                'admin_permissions' => $matrix['admin'] ?? [],
                'all_permissions' => $permissions->pluck('name')->values()->all(),
                'super_admin_only' => RolePermissionSeeder::SUPER_ADMIN_ONLY_PERMISSIONS,
                'sensitive_couple' => RolePermissionSeeder::SENSITIVE_COUPLE_PERMISSIONS,
            ],
        ]);
    }

    /**
     * Update the permissions assigned to a role.
     * Enforces the hard rule: Admin can NEVER receive super_admin-only keys.
     */
    public function update(UpdateRolePermissionsRequest $request): JsonResponse
    {
        $roleName = $request->role;
        $requestedPermissions = $request->permissions;

        // Hard enforcement: Admin can NEVER receive super_admin-only keys
        if ($roleName === RoleName::ADMIN->value) {
            $forbiddenAssigned = array_intersect($requestedPermissions, RolePermissionSeeder::SUPER_ADMIN_ONLY_PERMISSIONS);
            if (! empty($forbiddenAssigned)) {
                throw ValidationException::withMessages([
                    'permissions' => [
                        'Security violation: The following Super Admin exclusive permissions cannot be assigned to Admin: ' . implode(', ', $forbiddenAssigned),
                    ],
                ]);
            }
        }

        $role = Role::findByName($roleName, 'web');
        $role->syncPermissions($requestedPermissions);

        return response()->json([
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'message' => "Permissions for role '{$role->name}' updated successfully.",
            ],
        ]);
    }
}
