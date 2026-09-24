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

            WeddingDetail::create([
                'wedding_id' => $w->id,
                'groom_name' => $groomName,
                'bride_name' => $brideName,
                'groom_parents' => $groomParents ?: null,
                'bride_parents' => $brideParents ?: null,
                'story' => $request->input('story'),
            ]);

            return $w;
        });

        $wedding->load(['details', 'owner']);

        return response()->json([
            'data' => new WeddingResource($wedding),
        ], 201);
    }
}
