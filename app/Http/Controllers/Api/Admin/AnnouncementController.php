<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::with('creator');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $announcements = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => AnnouncementResource::collection($announcements->items()),
            'meta' => [
                'page' => $announcements->currentPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
                'last_page' => $announcements->lastPage(),
            ],
        ]);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = Announcement::create([
            'created_by' => $request->user()->id,
            ...$request->validated(),
        ]);

        return response()->json([
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $announcement = Announcement::with('creator')->findOrFail($id);

        return response()->json([
            'data' => new AnnouncementResource($announcement),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update($request->validated());

        return response()->json([
            'data' => new AnnouncementResource($announcement),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'data' => [
                'message' => 'Announcement deleted successfully.',
            ],
        ]);
    }
}
