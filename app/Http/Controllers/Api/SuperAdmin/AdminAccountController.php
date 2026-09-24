<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAdminRequest;
use App\Http\Requests\SuperAdmin\UpdateAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAccountController extends Controller
{
    /**
     * List all staff accounts (Admin and Super Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $admins = User::role([RoleName::ADMIN->value, RoleName::SUPER_ADMIN->value])
            ->with(['roles', 'permissions'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => UserResource::collection($admins->items()),
            'meta' => [
                'page' => $admins->currentPage(),
                'per_page' => $admins->perPage(),
                'total' => $admins->total(),
                'last_page' => $admins->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new staff account.
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole($request->role);

            if ($request->has('permissions') && $request->role === RoleName::ADMIN->value) {
                $user->syncPermissions($request->permissions);
            }

            return $user;
        });

        $user->load(['roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Show a staff account.
     */
    public function show(int $id): JsonResponse
    {
        $admin = User::role([RoleName::ADMIN->value, RoleName::SUPER_ADMIN->value])
            ->with(['roles', 'permissions'])
            ->findOrFail($id);

        return response()->json([
            'data' => new UserResource($admin),
        ]);
    }

    /**
     * Update a staff account with self-lockout guard.
     */
    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        // Self-lockout check: if demoting from super_admin to admin
        if ($target->hasRole(RoleName::SUPER_ADMIN->value) && $request->filled('role') && $request->role !== RoleName::SUPER_ADMIN->value) {
            $otherActive = User::role(RoleName::SUPER_ADMIN->value)
                ->where('id', '!=', $target->id)
                ->where('is_active', true)
                ->count();

            if ($otherActive === 0) {
                return response()->json([
                    'message' => 'Action rejected. You cannot demote the last active Super Admin account.',
                ], 422);
            }
        }

        // Self-lockout check: if disabling target super_admin
        if ($target->hasRole(RoleName::SUPER_ADMIN->value) && $request->has('is_active') && ! $request->boolean('is_active')) {
            $otherActive = User::role(RoleName::SUPER_ADMIN->value)
                ->where('id', '!=', $target->id)
                ->where('is_active', true)
                ->count();

            if ($otherActive === 0) {
                return response()->json([
                    'message' => 'Action rejected. You cannot disable the last active Super Admin account.',
                ], 422);
            }
        }

        DB::transaction(function () use ($target, $request) {
            $target->update($request->safe()->only(['name', 'email', 'phone', 'is_active']));

            if ($request->filled('role')) {
                $target->syncRoles([$request->role]);
            }

            if ($request->has('permissions')) {
                $target->syncPermissions($request->permissions);
            }
        });

        $target->load(['roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($target),
        ]);
    }

    /**
     * Disable a staff account with self-lockout guard.
     */
    public function disable(int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if ($target->hasRole(RoleName::SUPER_ADMIN->value)) {
            $otherActive = User::role(RoleName::SUPER_ADMIN->value)
                ->where('id', '!=', $target->id)
                ->where('is_active', true)
                ->count();

            if ($otherActive === 0) {
                return response()->json([
                    'message' => 'Action rejected. You cannot disable the last active Super Admin account.',
                ], 422);
            }
        }

        $target->update(['is_active' => false]);
        $target->tokens()->delete();

        return response()->json([
            'data' => [
                'message' => "Staff account {$target->name} disabled.",
                'admin' => new UserResource($target),
            ],
        ]);
    }

    /**
     * Enable a staff account.
     */
    public function enable(int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $target->update(['is_active' => true]);

        return response()->json([
            'data' => [
                'message' => "Staff account {$target->name} enabled.",
                'admin' => new UserResource($target),
            ],
        ]);
    }

    /**
     * Force password reset for a staff account.
     */
    public function forceReset(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $newPassword = $request->input('password') ?: Str::random(12);
        $target->update(['password' => Hash::make($newPassword)]);
        $target->tokens()->delete();

        return response()->json([
            'data' => [
                'message' => 'Staff password reset successfully.',
                'temporary_password' => $newPassword,
            ],
        ]);
    }
}
