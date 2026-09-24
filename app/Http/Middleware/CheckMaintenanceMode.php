<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        $isMaintenance = false;
        try {
            $setting = Setting::where('key', 'maintenance_mode')->first();
            $isMaintenance = $setting && ($setting->value === true || $setting->value === '1' || $setting->value === 'true');
        } catch (\Throwable $e) {
            $isMaintenance = false;
        }

        if ($isMaintenance) {
            // Super admins and admins are exempt
            $user = $request->user();
            if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
                return $next($request);
            }

            // Also exempt super-admin and staff login routes
            if ($request->is('api/auth/login') || $request->is('api/super-admin/*')) {
                return $next($request);
            }

            return response()->json([
                'message' => 'The service is currently undergoing scheduled maintenance. Please check back shortly.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
