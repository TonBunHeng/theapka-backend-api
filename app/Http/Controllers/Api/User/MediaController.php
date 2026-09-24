<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class MediaController extends Controller
{
    /**
     * List wedding media files.
     */
    public function index(Request $request): JsonResponse
    {
        $collection = $request->query('collection', 'gallery');

        $media = Media::where('collection', $collection)
            ->latest()
            ->paginate((int) $request->query('per_page', 30));

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
     * Upload an image, strip EXIF, generate thumbnail variant, and save record.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $file = $request->file('file');
        $collection = $request->input('collection', 'gallery');
        $disk = 'public';

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = Str::uuid() . '.' . $extension;
        $thumbName = Str::uuid() . '-thumb.' . $extension;

        $dir = "weddings/{$wedding->id}/media";
        $filePath = "{$dir}/{$fileName}";
        $thumbPath = "{$dir}/{$thumbName}";

        $dimensions = null;

        try {
            // Read with Intervention Image (v3) to strip EXIF and process dimensions
            $image = Image::read($file);
            $dimensions = [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

            // Save cleaned file (stripped of EXIF metadata)
            $encodedOriginal = $image->encode();
            Storage::disk($disk)->put($filePath, (string) $encodedOriginal);

            // Generate thumbnail (max 300x300)
            $thumb = Image::read($file)->cover(300, 300);
            $encodedThumb = $thumb->encode();
            Storage::disk($disk)->put($thumbPath, (string) $encodedThumb);
        } catch (Throwable $e) {
            // Fallback: direct storage if image driver encounters issue
            $filePath = $file->storeAs($dir, $fileName, $disk);
            $thumbPath = null;
        }

        $media = Media::create([
            'wedding_id' => $wedding->id,
            'uploaded_by' => $request->user()->id,
            'disk' => $disk,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbPath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize(),
            'dimensions' => $dimensions,
            'type' => 'image',
            'collection' => $collection,
        ]);

        return response()->json([
            'data' => new MediaResource($media),
        ], 201);
    }

    /**
     * Delete a media item.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

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
