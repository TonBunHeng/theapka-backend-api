<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Write an audit log entry.
     *
     * @param  int|null  $actorId
     * @param  string  $action
     * @param  string|null  $subjectType
     * @param  int|string|null  $subjectId
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  string|null  $ipAddress
     * @param  string|null  $userAgent
     * @return \App\Models\AuditLog
     */
    public static function log(
        ?int $actorId,
        string $action,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        // Strip sensitive fields from values if present
        $sanitize = function (?array $values): ?array {
            if ($values === null) {
                return null;
            }
            $sensitiveKeys = ['password', 'password_confirmation', 'secret', 'api_key', 'token', 'access_token', 'private_key'];
            foreach ($sensitiveKeys as $key) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = '[REDACTED]';
                }
            }
            return $values;
        };

        return AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId ? (int) $subjectId : null,
            'old_values' => $sanitize($oldValues),
            'new_values' => $sanitize($newValues),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Helper to log from request context.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @param  string|null  $subjectType
     * @param  int|string|null  $subjectId
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @return \App\Models\AuditLog
     */
    public static function logFromRequest(
        Request $request,
        string $action,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return self::log(
            actorId: $request->user()?->id,
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
    }
}
