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
            $defaultTemplate = \App\Models\Template::where('is_active', true)->first();
            $invitation = $wedding->invitation()->firstOrCreate(
                ['wedding_id' => $wedding->id],
                [
                    'slug' => $wedding->slug,
                    'title' => $wedding->title,
                    'status' => 'draft',
                    'template_id' => $defaultTemplate?->id,
                    'content' => $defaultTemplate?->config,
                    'music_url' => '/music/ភ្ជាប់និស្ស័យ.mp3',
                ]
            );
            $invitation->load('template');
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

        $gallery = $wedding->media()
            ->where('collection', 'gallery')
            ->latest()
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'thumbnail_url' => $m->thumbnail_url ?: $m->url,
                'is_cover' => (bool) ($wedding->cover_image_url === $m->url),
                'file_name' => $m->file_name,
            ]);

        $wishes = $wedding->wishes()
            ->where('is_visible', true)
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'sender_name' => $w->sender_name,
                'name' => $w->sender_name,
                'message' => $w->message,
                'created_at' => $w->created_at->toISOString(),
            ]);

        $groomParents = explode('&', (string) $wedding->details?->groom_parents);
        $brideParents = explode('&', (string) $wedding->details?->bride_parents);

        $custom = is_array($wedding->details?->custom_fields) ? $wedding->details->custom_fields : [];
        $rawGroom = (string) ($wedding->details?->groom_name ?? '');
        $rawBride = (string) ($wedding->details?->bride_name ?? '');

        $groomKh = $custom['groom_name_kh'] ?? null;
        $groomEn = $custom['groom_name_en'] ?? null;
        if (! $groomKh || ! $groomEn) {
            if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawGroom, $m)) {
                $groomKh = $groomKh ?: trim($m[1]);
                $groomEn = $groomEn ?: trim($m[2]);
            } else {
                $groomKh = $groomKh ?: $rawGroom;
                if (! $groomEn) {
                    if (preg_match('/[a-zA-Z]/', $rawGroom)) {
                        $groomEn = $rawGroom;
                    } elseif ($wedding->slug && preg_match('/^([a-z0-9-]+?)-([a-z0-9-]+?)-wedding$/i', $wedding->slug, $sm)) {
                        $groomEn = ucwords(str_replace('-', ' ', $sm[1]));
                    }
                }
            }
        }

        $brideKh = $custom['bride_name_kh'] ?? null;
        $brideEn = $custom['bride_name_en'] ?? null;
        if (! $brideKh || ! $brideEn) {
            if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawBride, $m)) {
                $brideKh = $brideKh ?: trim($m[1]);
                $brideEn = $brideEn ?: trim($m[2]);
            } else {
                $brideKh = $brideKh ?: $rawBride;
                if (! $brideEn) {
                    if (preg_match('/[a-zA-Z]/', $rawBride)) {
                        $brideEn = $rawBride;
                    } elseif ($wedding->slug && preg_match('/^([a-z0-9-]+?)-([a-z0-9-]+?)-wedding$/i', $wedding->slug, $sm)) {
                        $brideEn = ucwords(str_replace('-', ' ', $sm[2]));
                    }
                }
            }
        }

        $weddingPayload = [
            'id' => $wedding->id,
            'title' => $wedding->title,
            'slug' => $wedding->slug,
            'wedding_date' => $wedding->wedding_date instanceof \DateTimeInterface ? $wedding->wedding_date->format('Y-m-d') : ($wedding->wedding_date ? (string) $wedding->wedding_date : null),
            'venue_name' => $wedding->venue_name,
            'venue_address' => $wedding->venue_address,
            'venue_map_url' => $wedding->venue_map_url,
            'map_url' => $wedding->venue_map_url,
            'lat' => is_array($wedding->settings) && isset($wedding->settings['lat']) ? (float) $wedding->settings['lat'] : 11.6685,
            'lng' => is_array($wedding->settings) && isset($wedding->settings['lng']) ? (float) $wedding->settings['lng'] : 104.9452,
            'timezone' => $wedding->timezone,
            'cover_image_url' => $wedding->cover_image_url,
            'cover_photo' => $wedding->cover_image_url,
            'music_url' => $invitation->music_url,
            'template_config' => $invitation->content ?? $invitation->template?->config,
            'groom_name' => $groomKh,
            'groom_name_kh' => $groomKh,
            'groom_name_en' => $groomEn,
            'bride_name' => $brideKh,
            'bride_name_kh' => $brideKh,
            'bride_name_en' => $brideEn,
            'groom_father_kh' => trim($groomParents[0] ?? ''),
            'groom_mother_kh' => trim($groomParents[1] ?? ''),
            'bride_father_kh' => trim($brideParents[0] ?? ''),
            'bride_mother_kh' => trim($brideParents[1] ?? ''),
            'groom_parents' => $wedding->details?->groom_parents ?? '',
            'bride_parents' => $wedding->details?->bride_parents ?? '',
            'story' => $wedding->details?->story ?? '',
            'welcome_message' => $wedding->details?->welcome_message ?? '',
            'dress_code' => $wedding->details?->dress_code ?? '',
            'contact_phones' => $wedding->details?->contact_phones ?? [],
        ];

        return response()->json([
            'data' => [
                'wedding' => $weddingPayload,
                'details' => $wedding->details ? [
                    'groom_name' => $groomKh,
                    'groom_name_kh' => $groomKh,
                    'groom_name_en' => $groomEn,
                    'groom_title' => $wedding->details->groom_title,
                    'groom_parents' => $wedding->details->groom_parents,
                    'groom_father_kh' => trim($groomParents[0] ?? ''),
                    'groom_mother_kh' => trim($groomParents[1] ?? ''),
                    'bride_name' => $brideKh,
                    'bride_name_kh' => $brideKh,
                    'bride_name_en' => $brideEn,
                    'bride_title' => $wedding->details->bride_title,
                    'bride_parents' => $wedding->details->bride_parents,
                    'bride_father_kh' => trim($brideParents[0] ?? ''),
                    'bride_mother_kh' => trim($brideParents[1] ?? ''),
                    'story' => $wedding->details->story,
                    'welcome_message' => $wedding->details->welcome_message,
                    'dress_code' => $wedding->details->dress_code,
                    'contact_phones' => $wedding->details->contact_phones,
                    'custom_fields' => $wedding->details->custom_fields,
                ] : null,
                'schedules' => $wedding->schedules->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'title' => $s->title,
                        'description' => $s->description,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'location' => $s->location,
                        'venue' => $s->location,
                    ];
                }),
                'invitation' => [
                    'title' => $invitation->title,
                    'custom_css' => $invitation->custom_css,
                    'content' => $invitation->content,
                    'template_config' => $invitation->content,
                    'music_url' => $invitation->music_url,
                    'template' => $invitation->template ? [
                        'name' => $invitation->template->name,
                        'slug' => $invitation->template->slug,
                        'config' => $invitation->template->config,
                    ] : null,
                ],
                'guest' => $guestData,
                'group' => $guest?->group ? [
                    'id' => $guest->group->id,
                    'name' => $guest->group->name,
                    'color' => $guest->group->color ?? '#8B1E3F',
                ] : null,
                'gallery' => $gallery,
                'wishes' => $wishes,
            ],
        ]);
    }
}
