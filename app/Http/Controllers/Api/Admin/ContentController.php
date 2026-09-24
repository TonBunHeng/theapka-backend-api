<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContentRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * List all editable content blocks.
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group', 'content');
        $contents = Setting::where('group', $group)->get();

        return response()->json([
            'data' => SettingResource::collection($contents),
        ]);
    }

    /**
     * Show a content item by key.
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        return response()->json([
            'data' => new SettingResource($setting),
        ]);
    }

    /**
     * Update a content block.
     */
    public function update(UpdateContentRequest $request, string $key): JsonResponse
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $request->value,
                'group' => $request->input('group', 'content'),
                'is_public' => $request->input('is_public', true),
            ]
        );

        return response()->json([
            'data' => new SettingResource($setting),
        ]);
    }
}
