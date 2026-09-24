<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Authenticate user and issue Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        $deviceName = $request->device_name ?? 'spa_client';
        $token = $user->createToken($deviceName)->plainTextToken;

        $role = $user->roles->first()?->name ?? 'user';
        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        $wedding = $user->currentWedding();

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
                'role' => $role,
                'permissions' => $permissions,
                'wedding' => $wedding ? [
                    'id' => $wedding->id,
                    'title' => $wedding->title,
                    'slug' => $wedding->slug,
                    'wedding_date' => $wedding->wedding_date instanceof \DateTimeInterface ? $wedding->wedding_date->format('Y-m-d') : ($wedding->wedding_date ? (string) $wedding->wedding_date : null),
                    'venue_name' => $wedding->venue_name,
                    'status' => $wedding->status instanceof \BackedEnum ? $wedding->status->value : (string) $wedding->status,
                ] : null,
            ],
        ]);
    }

    /**
     * Get the authenticated user with role and permissions (matches Section 12 Me contract).
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->roles->first()?->name ?? 'user';
        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        $wedding = $user->currentWedding();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role,
                    'avatar_url' => $user->avatar_url,
                ],
                'role' => $role,
                'permissions' => $permissions,
                'wedding' => $wedding ? [
                    'id' => $wedding->id,
                    'title' => $wedding->title,
                    'slug' => $wedding->slug,
                    'wedding_date' => $wedding->wedding_date instanceof \DateTimeInterface ? $wedding->wedding_date->format('Y-m-d') : ($wedding->wedding_date ? (string) $wedding->wedding_date : null),
                    'venue_name' => $wedding->venue_name,
                    'status' => $wedding->status instanceof \BackedEnum ? $wedding->status->value : (string) $wedding->status,
                ] : null,
            ],
        ]);
    }
}
