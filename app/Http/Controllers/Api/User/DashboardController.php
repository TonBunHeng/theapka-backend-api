<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\RsvpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GiftRecordResource;
use App\Http\Resources\WeddingResource;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\Wish;
use App\Services\GiftLedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected GiftLedgerService $giftLedgerService
    ) {}

    /**
     * Get aggregate dashboard stats for couple's wedding.
     */
    public function index(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        // Guests statistics (automatically filtered by WeddingScope)
        $totalGuests = Guest::count();
        $totalSeats = (int) Guest::sum('seats');

        // RSVP counts
        $attendingCount = Rsvp::where('wedding_id', $wedding->id)->where('status', RsvpStatus::ATTENDING->value)->count();
        $declinedCount = Rsvp::where('wedding_id', $wedding->id)->where('status', RsvpStatus::DECLINED->value)->count();
        $maybeCount = Rsvp::where('wedding_id', $wedding->id)->where('status', RsvpStatus::MAYBE->value)->count();
        $pendingCount = max(0, $totalGuests - ($attendingCount + $declinedCount + $maybeCount));

        // Gift summary (strict separation of USD and KHR)
        $giftSummary = $this->giftLedgerService->totalsFor($wedding);

        // Days left until wedding
        $daysLeft = null;
        if ($wedding->wedding_date) {
            $daysLeft = (int) Carbon::now()->diffInDays(Carbon::parse($wedding->wedding_date), false);
        }

        // Recent items
        $recentGifts = $wedding->giftRecords()->latest('recorded_at')->take(5)->get();
        $recentWishes = $wedding->wishes()->latest()->take(5)->get();
        $recentRsvps = $wedding->guests()->has('rsvps')->with('latestRsvp')->take(5)->get();

        return response()->json([
            'data' => [
                'wedding' => new WeddingResource($wedding),
                'days_left' => $daysLeft,
                'guest_stats' => [
                    'total_guests' => $totalGuests,
                    'total_seats' => $totalSeats,
                    'attending' => $attendingCount,
                    'declined' => $declinedCount,
                    'maybe' => $maybeCount,
                    'pending' => $pendingCount,
                ],
                'gift_stats' => $giftSummary,
                'invitation' => [
                    'status' => $wedding->invitation?->status ?? 'draft',
                    'view_count' => $wedding->invitation?->view_count ?? 0,
                    'slug' => $wedding->slug,
                ],
                'recent_gifts' => GiftRecordResource::collection($recentGifts),
                'recent_wishes' => $recentWishes,
            ],
        ]);
    }
}
