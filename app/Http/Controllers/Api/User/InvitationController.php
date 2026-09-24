<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(
        protected InvitationService $invitationService
    ) {}

    /**
     * Get the user's wedding invitation.
     */
    public function show(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $invitation = $wedding->invitation()->firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'slug' => $wedding->slug,
                'title' => $wedding->title,
                'status' => 'draft',
            ]
        );

        $invitation->load('template');

        return response()->json([
            'data' => new InvitationResource($invitation),
        ]);
    }

    /**
     * Update invitation details.
     */
    public function update(UpdateInvitationRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $invitation = $wedding->invitation()->firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'slug' => $wedding->slug,
                'title' => $wedding->title,
                'status' => 'draft',
            ]
        );

        $invitation->update($request->validated());
        $invitation->load('template');

        return response()->json([
            'data' => new InvitationResource($invitation),
        ]);
    }

    /**
     * Publish invitation.
     */
    public function publish(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $invitation = $this->invitationService->publish($wedding);
        $invitation->load('template');

        return response()->json([
            'data' => new InvitationResource($invitation),
        ]);
    }

    /**
     * Unpublish invitation back to draft.
     */
    public function unpublish(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $invitation = $this->invitationService->unpublish($wedding);
        $invitation->load('template');

        return response()->json([
            'data' => new InvitationResource($invitation),
        ]);
    }
}
