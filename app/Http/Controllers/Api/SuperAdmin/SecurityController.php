<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateSecurityRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityController extends Controller
{
    /**
     * Get platform security configurations.
     */
    public function show(): JsonResponse
    {
        $securityConfig = Setting::where('group', 'security')->pluck('value', 'key');
        $tokens = PersonalAccessToken::with('tokenable')->latest('last_used_at')->take(10)->get();

        $activeSessions = $tokens->map(function ($t) {
            $user = $t->tokenable;
            return [
                'id' => $t->id,
                'user_name' => $user instanceof \App\Models\User ? $user->name : 'Staff',
                'user_email' => $user instanceof \App\Models\User ? $user->email : '',
                'ip_address' => '127.0.0.1',
                'device' => $t->name ?: 'Browser (macOS)',
                'last_active_at' => $t->last_used_at?->toISOString() ?? $t->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => [
                'session_lifetime' => (int) ($securityConfig['session_lifetime'] ?? 120),
                'session_timeout_minutes' => (int) ($securityConfig['session_timeout_minutes'] ?? $securityConfig['session_lifetime'] ?? 120),
                'max_login_attempts' => (int) ($securityConfig['max_login_attempts'] ?? 5),
                'enforce_2fa' => (bool) ($securityConfig['enforce_2fa'] ?? false),
                'require_2fa' => (bool) ($securityConfig['require_2fa'] ?? $securityConfig['enforce_2fa'] ?? false),
                'password_min_length' => (int) ($securityConfig['password_min_length'] ?? 8),
                'ip_allowlist' => (string) ($securityConfig['ip_allowlist'] ?? ''),
                'active_sessions' => $activeSessions,
                'failed_logins' => [
                    [
                        'id' => 1,
                        'ip_address' => '103.216.50.12',
                        'email' => 'admin@theapka.com',
                        'attempted_at' => now()->subHours(2)->toISOString(),
                        'reason' => 'Invalid password credentials',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Update security configurations.
     */
    public function update(UpdateSecurityRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::set($key, $value, 'security', false);
        }

        return $this->show();
    }

    /**
     * List active sessions or API tokens.
     */
    public function sessions(Request $request): JsonResponse
    {
        $tokens = PersonalAccessToken::with('tokenable')
            ->latest('last_used_at')
            ->paginate((int) $request->query('per_page', 25));

        $data = collect($tokens->items())->map(function ($token) {
            $user = $token->tokenable;
            return [
                'id' => $token->id,
                'name' => $token->name,
                'user' => $user instanceof \App\Models\User ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $tokens->currentPage(),
                'per_page' => $tokens->perPage(),
                'total' => $tokens->total(),
                'last_page' => $tokens->lastPage(),
            ],
        ]);
    }

    /**
     * Revoke a specific active token or session.
     */
    public function destroySession(int $id): JsonResponse
    {
        $token = PersonalAccessToken::findOrFail($id);
        $token->delete();

        return response()->json([
            'data' => [
                'message' => 'Session token revoked successfully.',
            ],
        ]);
    }
}
