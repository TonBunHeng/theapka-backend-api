<?php

namespace App\Http\Middleware;

use App\Enums\WeddingStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWeddingNotSuspended
{
    /**
     * Handle an incoming request.
     * Block mutating operations if the couple's wedding has been suspended by administration.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow read-only (GET, HEAD) operations so couples can view their dashboard and see why they were suspended
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user) {
            $wedding = $user->currentWedding();
            if ($wedding && $wedding->status === WeddingStatus::SUSPENDED) {
                return response()->json([
                    'message' => 'This wedding has been suspended by platform administration. Modifications are disabled. Please contact support.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
