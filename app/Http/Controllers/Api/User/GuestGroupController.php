<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreGuestGroupRequest;
use App\Http\Requests\User\UpdateGuestGroupRequest;
use App\Http\Resources\GuestGroupResource;
use App\Models\GuestGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = GuestGroup::withCount('guests')->orderBy('order')->get();

        return response()->json([
            'data' => GuestGroupResource::collection($groups),
        ]);
    }

    public function store(StoreGuestGroupRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $group = GuestGroup::create([
            'wedding_id' => $wedding->id,
            ...$request->validated(),
        ]);

        return response()->json([
            'data' => new GuestGroupResource($group),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $group = GuestGroup::withCount('guests')->findOrFail($id);

        return response()->json([
            'data' => new GuestGroupResource($group),
        ]);
    }

    public function update(UpdateGuestGroupRequest $request, int $id): JsonResponse
    {
        $group = GuestGroup::findOrFail($id);
        $group->update($request->validated());

        return response()->json([
            'data' => new GuestGroupResource($group),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = GuestGroup::findOrFail($id);
        $group->delete();

        return response()->json([
            'data' => [
                'message' => 'Guest group deleted successfully.',
            ],
        ]);
    }
}
