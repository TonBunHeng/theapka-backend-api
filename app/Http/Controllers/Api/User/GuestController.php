<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreGuestRequest;
use App\Http\Requests\User\UpdateGuestRequest;
use App\Http\Resources\BaseResourceCollection;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Guest::with(['group', 'latestRsvp', 'latestSend']);

        // Search by name or phone
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        $filters = $request->query('filter', []);
        if (isset($filters['side'])) {
            $query->where('side', $filters['side']);
        }
        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        // Sorting
        $sortField = $request->query('sort', 'created_at');
        $sortDir = 'desc';
        if (str_starts_with($sortField, '-')) {
            $sortField = substr($sortField, 1);
            $sortDir = 'desc';
        } elseif (! empty($sortField)) {
            $sortDir = 'asc';
        }

        $allowedSorts = ['name', 'seats', 'created_at', 'side'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => GuestResource::collection($paginated->items()),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function store(StoreGuestRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'token' => Str::random(32),
            ...$request->validated(),
        ]);

        $guest->load(['group', 'latestRsvp', 'latestSend']);

        return response()->json([
            'data' => new GuestResource($guest),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $guest = Guest::with(['group', 'latestRsvp', 'latestSend'])->findOrFail($id);

        return response()->json([
            'data' => new GuestResource($guest),
        ]);
    }

    public function update(UpdateGuestRequest $request, int $id): JsonResponse
    {
        $guest = Guest::findOrFail($id);
        $guest->update($request->validated());
        $guest->load(['group', 'latestRsvp', 'latestSend']);

        return response()->json([
            'data' => new GuestResource($guest),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $guest = Guest::findOrFail($id);
        $guest->delete();

        return response()->json([
            'data' => [
                'message' => 'Guest deleted successfully.',
            ],
        ]);
    }
}
