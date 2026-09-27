<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
                'wedding' => $wedding ? new \App\Http\Resources\WeddingResource($wedding->loadMissing(['details', 'invitation'])) : null,
            ],
        ]);
    }

    /**
     * Authenticate or register user via Google account.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'credential' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'name' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'string'],
            'google_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $email = $request->email;
        $name = $request->name;
        $avatarUrl = $request->avatar_url;

        // If Google Identity Services JWT credential is provided, decode payload
        if ($request->credential) {
            $parts = explode('.', $request->credential);
            if (count($parts) === 3) {
                $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
                $payload = json_decode($payloadJson, true);
                if (is_array($payload) && !empty($payload['email'])) {
                    $email = $payload['email'];
                    $name = $payload['name'] ?? $name;
                    $avatarUrl = $payload['picture'] ?? $avatarUrl;
                }
            }
        }

        if (!$email) {
            return response()->json([
                'message' => 'Unable to retrieve email from Google account.',
            ], 422);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name ?: explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'avatar_url' => $avatarUrl,
                'is_active' => true,
            ]);
            $user->assignRole('user');
        } else {
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }
            if ($avatarUrl && !$user->avatar_url) {
                $user->update(['avatar_url' => $avatarUrl]);
            }
        }

        $deviceName = $request->device_name ?? 'spa_google_client';
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
                'wedding' => $wedding ? new \App\Http\Resources\WeddingResource($wedding->loadMissing(['details', 'invitation'])) : null,
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
                'wedding' => $wedding ? new \App\Http\Resources\WeddingResource($wedding->loadMissing(['details', 'invitation'])) : null,
            ],
        ]);
    }
}
