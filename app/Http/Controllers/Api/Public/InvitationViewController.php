<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\WeddingStatus;
use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Wedding;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationViewController extends Controller
{
    public function __construct(
        protected InvitationService $invitationService
    ) {}

    /**
     * View public invitation (generic or personalized by guest token).
     */
    public function show(Request $request, string $slug, ?string $token = null): JsonResponse
    {
        $wedding = Wedding::withoutGlobalScopes()
            ->where('slug', $slug)
            ->with(['details', 'schedules', 'invitation.template'])
            ->firstOrFail();

        if ($wedding->status === WeddingStatus::SUSPENDED) {
            return response()->json([
                'message' => 'This wedding invitation is temporarily unavailable.',
            ], 403);
        }

        $invitation = $wedding->invitation;
        if (! ($invitation instanceof Invitation)) {
            return response()->json([
                'message' => 'Invitation not configured yet.',
            ], 404);
        }

        $guestData = null;
        $guest = null;

        if ($token) {
            $guest = Guest::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->where('token', $token)
                ->first();

            if ($guest) {
                $guestData = [
                    'name' => $guest->name,
                    'side' => $guest->side,
                    'seats' => (int) $guest->seats,
                    'token' => $guest->token,
                    'rsvp_status' => $guest->rsvp_status,
                ];
            }
        }

        // Record view and track opened_at
        $this->invitationService->recordView($invitation, $guest);

        return response()->json([
            'data' => [
                'wedding' => [
                    'title' => $wedding->title,
                    'slug' => $wedding->slug,
                    'wedding_date' => $wedding->wedding_date instanceof \DateTimeInterface ? $wedding->wedding_date->format('Y-m-d') : ($wedding->wedding_date ? (string) $wedding->wedding_date : null),
                    'venue_name' => $wedding->venue_name,
                    'venue_address' => $wedding->venue_address,
                    'venue_map_url' => $wedding->venue_map_url,
                    'timezone' => $wedding->timezone,
                    'cover_image_url' => $wedding->cover_image_url,
                ],
                'details' => $wedding->details ? [
                    'groom_name' => $wedding->details->groom_name,
                    'groom_title' => $wedding->details->groom_title,
                    'groom_parents' => $wedding->details->groom_parents,
                    'bride_name' => $wedding->details->bride_name,
                    'bride_title' => $wedding->details->bride_title,
                    'bride_parents' => $wedding->details->bride_parents,
                    'story' => $wedding->details->story,
                    'welcome_message' => $wedding->details->welcome_message,
                    'dress_code' => $wedding->details->dress_code,
                    'contact_phones' => $wedding->details->contact_phones,
                ] : null,
                'schedules' => $wedding->schedules->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'title' => $s->title,
                        'description' => $s->description,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'location' => $s->location,
                    ];
                }),
                'invitation' => [
                    'title' => $invitation->title,
                    'custom_css' => $invitation->custom_css,
                    'content' => $invitation->content,
                    'music_url' => $invitation->music_url,
                    'template' => $invitation->template ? [
                        'name' => $invitation->template->name,
                        'slug' => $invitation->template->slug,
                        'config' => $invitation->template->config,
                    ] : null,
                ],
                'guest' => $guestData,
            ],
        ]);
    }
}
