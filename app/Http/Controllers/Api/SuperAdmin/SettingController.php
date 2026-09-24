<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateSettingsRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * List all platform settings.
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $query = Setting::query();

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();
        $map = $settings->pluck('value', 'key')->all();

        $defaults = [
            'platform_name' => 'TheapKa Online',
            'default_lang' => 'km',
            'contact_email' => 'support@theapka.com',
            'contact_phone' => '+855 12 888 999',
            'max_guests_free' => 50,
            'max_guests_premium' => 500,
            'allow_khqr' => true,
            'allow_payway' => true,
        ];
        $merged = array_merge($defaults, $map);

        return response()->json([
            'data' => array_merge($merged, [
                'items' => SettingResource::collection($settings),
            ]),
        ]);
    }

    /**
     * Batch update platform settings.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated('settings') as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'] ?? null,
                    'group' => $item['group'] ?? 'general',
                    'is_public' => $item['is_public'] ?? false,
                ]
            );
        }

        return response()->json([
            'data' => [
                'message' => 'Settings updated successfully.',
                'settings' => SettingResource::collection(Setting::all()),
            ],
        ]);
    }
}
