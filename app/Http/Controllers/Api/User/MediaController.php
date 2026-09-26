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
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'per_page' => 30,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        $query = Media::where('wedding_id', $wedding->id)->with('wedding');

        $collection = $request->query('collection');
        if ($collection && $collection !== 'all') {
            $query->where('collection', $collection);
        }

        $media = $query->latest()->paginate((int) $request->query('per_page', 30));

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
     * Upload an image (file or base64 data url), strip EXIF, generate thumbnail variant, and save record.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        if ($wedding->status === \App\Enums\WeddingStatus::SUSPENDED) {
            return response()->json([
                'message' => 'This wedding has been suspended by administration. Media management is disabled.',
            ], 403);
        }

        // Support updating existing photo as cover (setCoverMutation)
        if ($id = $request->input('id')) {
            $existing = Media::where('wedding_id', $wedding->id)->find($id);
            if ($existing) {
                if ($request->boolean('is_cover')) {
                    $wedding->update(['cover_image_url' => $existing->url]);
                }
                $existing->load('wedding');
                return response()->json([
                    'data' => new MediaResource($existing),
                ]);
            }
        }

        $collection = $request->input('collection', 'gallery');
        $disk = 'public';
        $dir = "weddings/{$wedding->id}/media";

        $dimensions = null;
        $thumbPath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::uuid() . '.' . $extension;
            $thumbName = Str::uuid() . '-thumb.' . $extension;
            $filePath = "{$dir}/{$fileName}";
            $thumbTarget = "{$dir}/{$thumbName}";
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType() ?: 'image/jpeg';
            $fileSize = $file->getSize();
            $rawContent = file_get_contents($file->getRealPath());
        } else {
            $url = (string) $request->input('url');
            if (preg_match('/^data:image\/(\w+);base64,/', $url, $matches)) {
                $ext = strtolower($matches[1]);
                $extension = ($ext === 'jpeg') ? 'jpg' : $ext;
                $rawContent = base64_decode(substr($url, strpos($url, ',') + 1));
                $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
            } else {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $rawContent = @file_get_contents($url) ?: '';
                $mimeType = 'image/jpeg';
            }
            $fileName = Str::uuid() . '.' . $extension;
            $thumbName = Str::uuid() . '-thumb.' . $extension;
            $filePath = "{$dir}/{$fileName}";
            $thumbTarget = "{$dir}/{$thumbName}";
            $originalName = $fileName;
            $fileSize = strlen($rawContent);
        }

        try {
            // Process with Intervention Image
            $image = Image::read($rawContent);
            $dimensions = [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

            // Save cleaned file
            $encodedOriginal = $image->encode();
            Storage::disk($disk)->put($filePath, (string) $encodedOriginal);

            // Generate thumbnail (max 300x300)
            $thumb = Image::read($rawContent)->cover(300, 300);
            $encodedThumb = $thumb->encode();
            Storage::disk($disk)->put($thumbTarget, (string) $encodedThumb);
            $thumbPath = $thumbTarget;
        } catch (Throwable $e) {
            // Direct raw storage fallback
            Storage::disk($disk)->put($filePath, $rawContent);
            $thumbPath = null;
        }

        $media = Media::create([
            'wedding_id' => $wedding->id,
            'uploaded_by' => $request->user()->id,
            'disk' => $disk,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbPath,
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'dimensions' => $dimensions,
            'type' => 'image',
            'collection' => $collection,
        ]);

        if ($request->boolean('is_cover') || $collection === 'cover') {
            $wedding->update(['cover_image_url' => $media->url]);
        }

        $media->load('wedding');

        return response()->json([
            'data' => new MediaResource($media),
        ], 201);
    }

    /**
     * Delete a media item.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if ($wedding && $wedding->status === \App\Enums\WeddingStatus::SUSPENDED) {
            return response()->json([
                'message' => 'This wedding has been suspended by administration. Media management is disabled.',
            ], 403);
        }

        $media = Media::where('wedding_id', $wedding?->id)->findOrFail($id);

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
