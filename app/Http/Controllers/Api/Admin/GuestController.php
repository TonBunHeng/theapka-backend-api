<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * Cross-wedding search for guests (strictly requires guests.view permission).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole(RoleName::SUPER_ADMIN->value) && ! $user->can('guests.view')) {
            return response()->json([
                'message' => 'Forbidden. You do not have permission to view private guest records.',
            ], 403);
        }

        // Audit log this staff read of private guest data
        AuditService::log(
            actorId: $user->id,
            action: 'guests.view',
            subjectType: 'Guest',
            subjectId: null,
            oldValues: null,
            newValues: ['query' => $request->query()],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
        $request->attributes->set('audit_logged', true);

        $query = Guest::withoutGlobalScopes()->with(['wedding', 'group', 'latestRsvp']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $filters = $request->query('filter', []);
        if (isset($filters['wedding_id'])) {
            $query->where('wedding_id', $filters['wedding_id']);
        }
        if (isset($filters['side'])) {
            $query->where('side', $filters['side']);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $guests = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => GuestResource::collection($guests->items()),
            'meta' => [
                'page' => $guests->currentPage(),
                'per_page' => $guests->perPage(),
                'total' => $guests->total(),
                'last_page' => $guests->lastPage(),
            ],
        ]);
    }
}
