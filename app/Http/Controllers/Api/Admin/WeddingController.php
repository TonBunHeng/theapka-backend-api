<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuestResource;
use App\Http\Resources\WeddingResource;
use App\Models\Guest;
use App\Models\Wedding;
use App\Services\AuditService;
use App\Services\GiftLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeddingController extends Controller
{
    public function __construct(
        protected GiftLedgerService $giftLedgerService
    ) {}

    /**
     * List platform weddings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Wedding::withoutGlobalScopes()->with(['owner', 'details']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('venue_name', 'like', "%{$search}%");
            });
        }

        $filters = $request->query('filter', []);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $weddings = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => WeddingResource::collection($weddings->items()),
            'meta' => [
                'page' => $weddings->currentPage(),
                'per_page' => $weddings->perPage(),
                'total' => $weddings->total(),
                'last_page' => $weddings->lastPage(),
            ],
        ]);
    }

    /**
     * Show a wedding by id.
     */
    public function show(int $id): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()
            ->with(['owner', 'details', 'schedules', 'invitation'])
            ->findOrFail($id);

        return response()->json([
            'data' => new WeddingResource($wedding),
        ]);
    }

    /**
     * Suspend a wedding page.
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);
        $wedding->update(['status' => WeddingStatus::SUSPENDED]);

        return response()->json([
            'data' => [
                'message' => "Wedding '{$wedding->title}' has been suspended.",
                'wedding' => new WeddingResource($wedding),
            ],
        ]);
    }

    /**
     * Restore a suspended wedding.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);
        $wedding->update(['status' => WeddingStatus::PUBLISHED]);

        return response()->json([
            'data' => [
                'message' => "Wedding '{$wedding->title}' has been restored.",
                'wedding' => new WeddingResource($wedding),
            ],
        ]);
    }

    /**
     * Archive a wedding.
     */
    public function archive(Request $request, int $id): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);
        $wedding->update(['status' => WeddingStatus::ARCHIVED]);

        return response()->json([
            'data' => [
                'message' => "Wedding '{$wedding->title}' has been archived.",
                'wedding' => new WeddingResource($wedding),
            ],
        ]);
    }

    /**
     * Soft delete a wedding.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);
        $wedding->delete();

        return response()->json([
            'data' => [
                'message' => 'Wedding deleted successfully.',
            ],
        ]);
    }

    /**
     * View guests for a wedding (requires guests.view permission).
     */
    public function guests(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole(RoleName::SUPER_ADMIN->value) && ! $user->can('guests.view')) {
            return response()->json([
                'message' => 'Forbidden. You do not have permission to view private wedding guest data.',
            ], 403);
        }

        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);

        // Explicitly audit log reading of couple's private guests
        AuditService::log(
            actorId: $user->id,
            action: 'guests.view',
            subjectType: 'Wedding',
            subjectId: $wedding->id,
            oldValues: null,
            newValues: ['viewed_wedding_id' => $wedding->id],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
        $request->attributes->set('audit_logged', true);

        $guests = Guest::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->with(['group', 'latestRsvp'])
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

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

    /**
     * View gifts summary for a wedding (requires gifts.view permission).
     */
    public function giftsSummary(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole(RoleName::SUPER_ADMIN->value) && ! $user->can('gifts.view')) {
            return response()->json([
                'message' => 'Forbidden. You do not have permission to view private wedding gift data.',
            ], 403);
        }

        $wedding = Wedding::withoutGlobalScopes()->findOrFail($id);

        // Explicitly audit log reading of couple's private gifts
        AuditService::log(
            actorId: $user->id,
            action: 'gifts.view',
            subjectType: 'Wedding',
            subjectId: $wedding->id,
            oldValues: null,
            newValues: ['viewed_wedding_id' => $wedding->id],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
        $request->attributes->set('audit_logged', true);

        $summary = $this->giftLedgerService->totalsFor($wedding);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
