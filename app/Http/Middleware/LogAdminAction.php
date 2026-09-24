<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log write methods (POST, PUT, PATCH, DELETE) or specific logged reads
        $method = $request->method();
        $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        // Check if staff read guest/gift data
        $isSensitiveStaffRead = false;
        $path = $request->path();
        if ($method === 'GET' && (str_contains($path, 'api/admin/weddings/') && (str_contains($path, '/guests') || str_contains($path, '/gifts-summary')) || str_contains($path, 'api/admin/guests'))) {
            $isSensitiveStaffRead = true;
        }

        if (($isWrite || $isSensitiveStaffRead) && $response->isSuccessful()) {
            // Check if already logged in this request lifecycle to avoid duplicates
            if ($request->attributes->get('audit_logged', false)) {
                return $response;
            }

            $user = $request->user();
            if ($user && ($user->hasRole('admin') || $user->hasRole('super_admin'))) {
                // Infer action name
                $action = $this->determineActionName($request, $isSensitiveStaffRead);
                $subjectType = $this->determineSubjectType($request);
                $subjectId = $this->determineSubjectId($request);

                $payload = $isWrite ? $this->filterPayload($request->all()) : null;

                AuditService::log(
                    actorId: $user->id,
                    action: $action,
                    subjectType: $subjectType,
                    subjectId: $subjectId,
                    oldValues: null,
                    newValues: $payload,
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );
            }
        }

        return $response;
    }

    /**
     * Determine action name from route.
     */
    protected function determineActionName(Request $request, bool $isSensitiveStaffRead): string
    {
        if ($isSensitiveStaffRead) {
            if (str_contains($request->path(), 'gifts')) {
                return 'gifts.view';
            }
            return 'guests.view';
        }

        $routeName = $request->route()?->getName();
        if ($routeName) {
            return $routeName;
        }

        // Fallback: build from path and method
        $segments = array_values(array_filter(explode('/', $request->path())));
        // e.g. api, admin, weddings, 1, suspend -> wedding.suspend
        $cleanSegments = array_filter($segments, fn ($s) => ! in_array($s, ['api', 'admin', 'super-admin']) && ! is_numeric($s));
        $action = implode('.', $cleanSegments);

        $methodMap = [
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];

        return $action ? $action : ($methodMap[$request->method()] ?? strtolower($request->method()));
    }

    /**
     * Determine subject type from route.
     */
    protected function determineSubjectType(Request $request): ?string
    {
        $path = $request->path();
        if (str_contains($path, 'users')) return 'User';
        if (str_contains($path, 'weddings')) return 'Wedding';
        if (str_contains($path, 'invitations')) return 'Invitation';
        if (str_contains($path, 'templates')) return 'Template';
        if (str_contains($path, 'guests')) return 'Guest';
        if (str_contains($path, 'payments')) return 'Payment';
        if (str_contains($path, 'announcements')) return 'Announcement';
        if (str_contains($path, 'admins')) return 'User';
        if (str_contains($path, 'roles')) return 'Role';
        if (str_contains($path, 'settings')) return 'Setting';
        if (str_contains($path, 'backups')) return 'Backup';
        if (str_contains($path, 'security')) return 'Security';

        return null;
    }

    /**
     * Determine subject id from route parameters.
     */
    protected function determineSubjectId(Request $request): ?int
    {
        $route = $request->route();
        if (! $route) return null;

        foreach (['id', 'user', 'wedding', 'invitation', 'template', 'guest', 'payment', 'announcement', 'admin'] as $param) {
            $val = $route->parameter($param);
            if ($val !== null) {
                if (is_numeric($val)) return (int) $val;
                if (is_object($val) && isset($val->id)) return (int) $val->id;
            }
        }

        return null;
    }

    /**
     * Filter sensitive fields from payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterPayload(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'secret',
            'api_key',
            'private_key',
            'webhook_secret',
            'merchant_id',
        ];

        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}
