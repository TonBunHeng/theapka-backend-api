<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreRsvpRequest;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RsvpController extends Controller
{
    /**
     * Submit an RSVP response for a specific guest token.
     */
    public function store(StoreRsvpRequest $request, string $token): JsonResponse
    {
        $guest = Guest::withoutGlobalScopes()->where('token', $token)->first();

        if (! $guest) {
            $wedding = Wedding::withoutGlobalScopes()->where('slug', $token)->first()
                ?: ($request->filled('slug') ? Wedding::withoutGlobalScopes()->where('slug', $request->input('slug'))->first() : null);

            if ($wedding) {
                $guest = Guest::withoutGlobalScopes()->create([
                    'wedding_id' => $wedding->id,
                    'name' => $request->input('name') ?: 'General Guest',
                    'side' => 'mutual',
                    'seats' => max(1, (int) $request->attending_count),
                    'token' => \Illuminate\Support\Str::random(32),
                ]);
            }
        }

        if (! $guest) {
            return response()->json([
                'message' => 'Invalid or expired invitation token.',
            ], 404);
        }

        // Validate attending_count <= guest.seats
        if ($request->attending_count > $guest->seats) {
            throw ValidationException::withMessages([
                'attending_count' => ["Attending count cannot exceed reserved seats ({$guest->seats})."],
            ]);
        }

        $rsvp = Rsvp::create([
            'guest_id' => $guest->id,
            'wedding_id' => $guest->wedding_id,
            'status' => $request->status,
            'attending_count' => $request->attending_count,
            'dietary_requirements' => $request->dietary_requirements,
            'notes' => $request->notes,
            'ip_address' => $request->ip(),
            'responded_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'status' => $rsvp->status instanceof \App\Enums\RsvpStatus ? $rsvp->status->value : (string) $rsvp->status,
                'attending_count' => (int) $rsvp->attending_count,
                'message' => 'Thank you! Your RSVP has been received.',
            ],
        ], 201);
    }
}
