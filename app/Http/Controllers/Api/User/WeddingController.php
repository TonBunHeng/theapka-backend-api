<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateWeddingRequest;
use App\Http\Resources\WeddingResource;
use App\Models\Wedding;
use App\Models\WeddingDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeddingController extends Controller
{
    /**
     * Get the current user's wedding with details.
     */
    public function show(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $wedding->load(['details', 'owner']);

        return response()->json([
            'data' => new WeddingResource($wedding),
        ]);
    }

    /**
     * Update the current user's wedding and details.
     */
    public function update(UpdateWeddingRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        if ($wedding->status === \App\Enums\WeddingStatus::SUSPENDED) {
            return response()->json([
                'message' => 'This wedding has been suspended by administration. Editing is disabled.',
            ], 403);
        }

        DB::transaction(function () use ($wedding, $request) {
            $wedding->update($request->safe()->only([
                'title',
                'slug',
                'wedding_date',
                'venue_name',
                'venue_address',
                'venue_map_url',
                'timezone',
                'cover_image_url',
                'settings',
            ]));

            if ($request->has('details')) {
                WeddingDetail::updateOrCreate(
                    ['wedding_id' => $wedding->id],
                    $request->validated('details')
                );
            }
        });

        $wedding->load(['details', 'owner']);

        return response()->json([
            'data' => new WeddingResource($wedding),
        ]);
    }

    /**
     * Store/initialize a new wedding for the current user (e.g. from onboarding).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $existing = $user->currentWedding();

        if ($existing) {
            return $this->update(app(UpdateWeddingRequest::class));
        }

        $title = $request->input('title')
            ?: ($request->input('groom_name_kh') && $request->input('bride_name_kh')
                ? "ពិធីមង្គលការ {$request->input('groom_name_kh')} & {$request->input('bride_name_kh')}"
                : "{$user->name}'s Wedding");

        $slug = $request->input('slug') ?: (\Illuminate\Support\Str::slug($user->name) . '-' . rand(100, 999));

        $wedding = DB::transaction(function () use ($user, $request, $title, $slug) {
            $w = Wedding::create([
                'owner_id' => $user->id,
                'title' => $title,
                'slug' => $slug,
                'wedding_date' => $request->input('wedding_date'),
                'venue_name' => $request->input('venue_name'),
                'venue_address' => $request->input('venue_address'),
                'cover_image_url' => $request->input('cover_photo', $request->input('cover_image_url')),
                'status' => \App\Enums\WeddingStatus::DRAFT->value,
            ]);

            $groomName = $request->input('groom_name_kh', $request->input('groom_name', ''));
            $brideName = $request->input('bride_name_kh', $request->input('bride_name', ''));
            $groomParents = implode(' & ', array_filter([$request->input('groom_father_kh'), $request->input('groom_mother_kh')]));
            $brideParents = implode(' & ', array_filter([$request->input('bride_father_kh'), $request->input('bride_mother_kh')]));

            $groomKh = $request->input('groom_name_kh');
            $groomEn = $request->input('groom_name_en');
            $brideKh = $request->input('bride_name_kh');
            $brideEn = $request->input('bride_name_en');

            $customFields = array_filter([
                'groom_name_kh' => $groomKh,
                'groom_name_en' => $groomEn,
                'bride_name_kh' => $brideKh,
                'bride_name_en' => $brideEn,
            ]);

            WeddingDetail::create([
                'wedding_id' => $w->id,
                'groom_name' => $groomKh ?: ($request->input('groom_name') ?: $groomEn ?: ''),
                'bride_name' => $brideKh ?: ($request->input('bride_name') ?: $brideEn ?: ''),
                'groom_parents' => $groomParents ?: null,
                'bride_parents' => $brideParents ?: null,
                'story' => $request->input('story'),
                'custom_fields' => ! empty($customFields) ? $customFields : null,
            ]);

            $defaultTemplate = \App\Models\Template::where('is_active', true)->first();
            \App\Models\Invitation::firstOrCreate(
                ['wedding_id' => $w->id],
                [
                    'title' => $w->title,
                    'slug' => $w->slug,
                    'status' => 'draft',
                    'template_id' => $defaultTemplate?->id,
                    'content' => $defaultTemplate?->config,
                    'music_url' => '/music/ភ្ជាប់និស្ស័យ.mp3',
                ]
            );

            return $w;
        });

        $wedding->load(['details', 'owner']);

        return response()->json([
            'data' => new WeddingResource($wedding),
        ], 201);
    }

    /**
     * Resolve Google Maps shortlinks and extract place/coordinates.
     */
    public function resolveMapUrl(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));
        if (! $url) {
            return response()->json([
                'data' => [
                    'clean_url' => '',
                    'lat' => null,
                    'lng' => null,
                    'place_name' => null,
                    'embed_url' => null,
                ],
            ]);
        }

        // If iframe passed
        if (preg_match('/src=["\']([^"\']+)["\']/', $url, $m)) {
            $url = $m[1];
        }

        $resolvedUrl = $url;

        // If shortlink (maps.app.goo.gl or goo.gl/maps)
        if (str_contains($url, 'maps.app.goo.gl') || str_contains($url, 'goo.gl/maps')) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                    ->withoutRedirecting()
                    ->get($url);

                $redirect = $response->header('Location');
                if ($redirect) {
                    $resolvedUrl = $redirect;
                }
            } catch (\Throwable $e) {
                // Ignore timeout or network errors
            }
        }

        $lat = null;
        $lng = null;
        $placeName = null;

        // Extract place name
        if (preg_match('/\/maps\/place\/([^\/@?]+)/u', $resolvedUrl, $m)) {
            $placeName = urldecode(str_replace('+', ' ', $m[1]));
        }

        // Extract coordinates from @lat,lng
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $resolvedUrl, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
        }

        // Extract from q=lat,lng
        if ($lat === null && preg_match('/[?&](?:q|ll|query)=(-?\d+\.\d+),(-?\d+\.\d+)/', $resolvedUrl, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
        }

        // Extract from !3dlat!2dlng
        if ($lat === null && preg_match('/!3d(-?\d+\.\d+)/', $resolvedUrl, $latM) && preg_match('/!2d(-?\d+\.\d+)/', $resolvedUrl, $lngM)) {
            $lat = (float) $latM[1];
            $lng = (float) $lngM[1];
        }

        // Generate embedUrl
        $embedUrl = null;
        if (str_contains($resolvedUrl, '/maps/embed') || str_contains($resolvedUrl, 'output=embed')) {
            $embedUrl = $resolvedUrl;
        } elseif ($placeName) {
            $embedUrl = 'https://maps.google.com/maps?q=' . urlencode($placeName) . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
        } elseif ($lat !== null && $lng !== null) {
            $embedUrl = "https://maps.google.com/maps?q={$lat},{$lng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
        }

        return response()->json([
            'data' => [
                'clean_url' => $resolvedUrl,
                'lat' => $lat,
                'lng' => $lng,
                'place_name' => $placeName,
                'embed_url' => $embedUrl,
            ],
        ]);
    }
}
