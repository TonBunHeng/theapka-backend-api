<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * List all platform media files.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Media::withoutGlobalScopes()->with(['wedding', 'uploader']);

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($collection = $request->query('collection')) {
            $query->where('collection', $collection);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));
        $media = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => MediaResource::collection($media->items()),
            'meta' => [
                'page' => $media->currentPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'last_page' => $media->lastPage(),
            ],
        ]);
    }

    /**
     * Delete a media item.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::withoutGlobalScopes()->findOrFail($id);

        if ($media->file_path && Storage::disk($media->disk)->exists($media->file_path)) {
            Storage::disk($media->disk)->delete($media->file_path);
        }
        if ($media->thumbnail_path && Storage::disk($media->disk)->exists($media->thumbnail_path)) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }

        $media->delete();

        return response()->json([
            'data' => [
                'message' => 'Media deleted successfully.',
            ],
        ]);
    }
}
