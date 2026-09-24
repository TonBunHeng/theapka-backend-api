<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Policies\AdminPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        protected AdminPolicy $adminPolicy
    ) {}

    /**
     * List users (excluding Super Admins if actor is an Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $query = User::with('roles');

        // If actor is not Super Admin, hide all Super Admin accounts from listing
        if (! $actor->hasRole(RoleName::SUPER_ADMIN->value)) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', RoleName::SUPER_ADMIN->value);
            });
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $filters = $request->query('filter', []);
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new user (couples only).
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole(RoleName::USER->value);

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Show a user. Block Admin from viewing Super Admin.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $target = User::with('roles')->findOrFail($id);

        if (! $this->adminPolicy->view($request->user(), $target)) {
            return response()->json([
                'message' => 'Unauthorized. Admin cannot view Super Admin accounts.',
            ], 403);
        }

        return response()->json([
            'data' => new UserResource($target),
        ]);
    }

    /**
     * Update user details. Block Admin from touching Super Admin.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if (! $this->adminPolicy->manage($request->user(), $target)) {
            return response()->json([
                'message' => 'Unauthorized. Admin cannot modify Super Admin accounts.',
            ], 403);
        }

        $target->update($request->validated());

        return response()->json([
            'data' => new UserResource($target),
        ]);
    }

    /**
     * Suspend a user.
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if (! $this->adminPolicy->disableOrDelete($request->user(), $target)) {
            return response()->json([
                'message' => 'Action rejected. Admin cannot suspend Super Admin accounts, or this is the last active Super Admin.',
            ], 403);
        }

        $target->update(['is_active' => false]);
        $target->tokens()->delete();

        return response()->json([
            'data' => [
                'message' => "User {$target->name} has been suspended.",
                'user' => new UserResource($target),
            ],
        ]);
    }

    /**
     * Reactivate a user.
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if (! $this->adminPolicy->manage($request->user(), $target)) {
            return response()->json([
                'message' => 'Unauthorized. Admin cannot modify Super Admin accounts.',
            ], 403);
        }

        $target->update(['is_active' => true]);

        return response()->json([
            'data' => [
                'message' => "User {$target->name} has been reactivated.",
                'user' => new UserResource($target),
            ],
        ]);
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if (! $this->adminPolicy->manage($request->user(), $target)) {
            return response()->json([
                'message' => 'Unauthorized. Admin cannot reset Super Admin passwords.',
            ], 403);
        }

        $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $newPassword = $request->input('password') ?: Str::random(12);
        $target->update(['password' => Hash::make($newPassword)]);
        $target->tokens()->delete();

        return response()->json([
            'data' => [
                'message' => 'Password reset successfully.',
                'temporary_password' => $newPassword,
            ],
        ]);
    }

    /**
     * Soft delete a user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if (! $this->adminPolicy->disableOrDelete($request->user(), $target)) {
            return response()->json([
                'message' => 'Action rejected. Admin cannot delete Super Admin accounts, or this is the last active Super Admin.',
            ], 403);
        }

        $target->tokens()->delete();
        $target->delete();

        return response()->json([
            'data' => [
                'message' => 'User deleted successfully.',
            ],
        ]);
    }
}
