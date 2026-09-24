<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreWishRequest;
use App\Models\Guest;
use App\Models\Wedding;
use App\Models\Wish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishController extends Controller
{
    /**
     * Submit a wish for a wedding using guest token.
     */
    public function store(StoreWishRequest $request, string $token): JsonResponse
    {
        $guest = Guest::withoutGlobalScopes()->where('token', $token)->first();

        $wedding = null;
        $guestId = null;

        if ($guest) {
            $wedding = $guest->wedding()->withoutGlobalScopes()->first();
            $guestId = $guest->id;
        } else {
            // Also allow token to match wedding slug if guest token is omitted
            $wedding = Wedding::withoutGlobalScopes()->where('slug', $token)->first();
        }

        if (! $wedding) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        $invitation = $wedding->invitation()->first();
        $isModerationEnabled = $invitation?->is_moderation_enabled ?? false;

        $wish = Wish::create([
            'wedding_id' => $wedding->id,
            'guest_id' => $guestId,
            'sender_name' => $request->sender_name,
            'message' => $request->message,
            'is_visible' => ! $isModerationEnabled,
        ]);

        return response()->json([
            'data' => [
                'id' => $wish->id,
                'sender_name' => $wish->sender_name,
                'message' => $wish->message,
                'is_visible' => (bool) $wish->is_visible,
                'created_at' => $wish->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Get list of visible wishes for a wedding.
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();

        $wishes = Wish::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('is_visible', true)
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => $wishes->items(),
            'meta' => [
                'page' => $wishes->currentPage(),
                'per_page' => $wishes->perPage(),
                'total' => $wishes->total(),
                'last_page' => $wishes->lastPage(),
            ],
        ]);
    }
}
