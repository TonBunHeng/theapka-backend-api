<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    /**
     * List all invitations for moderation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invitation::withoutGlobalScopes()->with(['template', 'wedding']);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        $filters = $request->query('filter', []);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $invitations = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => InvitationResource::collection($invitations->items()),
            'meta' => [
                'page' => $invitations->currentPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
                'last_page' => $invitations->lastPage(),
            ],
        ]);
    }

    /**
     * Unpublish an invitation (moderation).
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $invitation = Invitation::withoutGlobalScopes()->findOrFail($id);
        $invitation->update(['status' => 'draft']);

        return response()->json([
            'data' => [
                'message' => 'Invitation unpublished successfully.',
                'invitation' => new InvitationResource($invitation),
            ],
        ]);
    }

    /**
     * Flag an invitation for review.
     */
    public function flag(Request $request, int $id): JsonResponse
    {
        $invitation = Invitation::withoutGlobalScopes()->findOrFail($id);
        $invitation->update(['is_moderation_enabled' => true]);

        return response()->json([
            'data' => [
                'message' => 'Invitation flagged and placed in moderated mode.',
                'invitation' => new InvitationResource($invitation),
            ],
        ]);
    }
}
