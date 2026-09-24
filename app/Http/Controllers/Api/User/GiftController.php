<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreGiftRequest;
use App\Http\Resources\GiftRecordResource;
use App\Models\GiftRecord;
use App\Services\GiftLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function __construct(
        protected GiftLedgerService $giftLedgerService
    ) {}

    /**
     * List gift entries for the authenticated couple (filtered by WeddingScope).
     */
    public function index(Request $request): JsonResponse
    {
        $query = GiftRecord::with(['guest', 'recorder', 'correctedRecord']);

        // Search by giver name
        if ($search = $request->query('search')) {
            $query->where('giver_name', 'like', "%{$search}%");
        }

        // Filters
        $filters = $request->query('filter', []);
        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }
        if (isset($filters['entry_type'])) {
            $query->where('entry_type', $filters['entry_type']);
        }
        if (isset($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        // Sorting
        $sortField = $request->query('sort', 'recorded_at');
        $sortDir = 'desc';
        if (str_starts_with($sortField, '-')) {
            $sortField = substr($sortField, 1);
            $sortDir = 'desc';
        } elseif (! empty($sortField)) {
            $sortDir = 'asc';
        }

        $allowedSorts = ['giver_name', 'amount', 'recorded_at', 'created_at'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest('recorded_at');
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => GiftRecordResource::collection($paginated->items()),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * Record a gift or correction idempotently by client_uuid.
     */
    public function store(StoreGiftRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $result = $this->giftLedgerService->record(
            wedding: $wedding,
            recorder: $request->user(),
            data: $request->validated()
        );

        $record = $result['record'];
        $record->load(['guest', 'recorder', 'correctedRecord']);

        // Return 200 on idempotent duplicate, 201 on new record
        $status = $result['is_duplicate'] ? 200 : 201;

        return response()->json([
            'data' => new GiftRecordResource($record),
        ], $status);
    }

    /**
     * Get aggregate totals per currency (USD and KHR strictly separated).
     */
    public function summary(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $summary = $this->giftLedgerService->totalsFor($wedding);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
