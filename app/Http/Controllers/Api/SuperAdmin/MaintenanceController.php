<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Get current maintenance mode status.
     */
    public function show(): JsonResponse
    {
        $enabled = (bool) Setting::get('maintenance_mode', false);
        $message = Setting::get('maintenance_message', 'The service is currently undergoing scheduled maintenance.');

        return response()->json([
            'data' => [
                'enabled' => $enabled,
                'message' => $message,
                'message_km' => Setting::get('maintenance_message_km', 'ប្រព័ន្ធកំពុងស្ថិតក្រោមការថែទាំ។ សូមអភ័យទោសចំពោះការរំខាន។'),
                'message_en' => Setting::get('maintenance_message_en', $message),
                'scheduled_end' => Setting::get('maintenance_scheduled_end', null),
                'allowed_ips' => Setting::get('maintenance_allowed_ips', ''),
            ],
        ]);
    }

    /**
     * Toggle or update maintenance mode.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
            'message_km' => ['nullable', 'string', 'max:500'],
            'message_en' => ['nullable', 'string', 'max:500'],
            'scheduled_end' => ['nullable'],
            'allowed_ips' => ['nullable', 'string'],
        ]);

        Setting::set('maintenance_mode', $request->boolean('enabled'), 'maintenance', true);

        if ($request->filled('message')) {
            Setting::set('maintenance_message', $request->input('message'), 'maintenance', true);
        }
        if ($request->has('message_km')) {
            Setting::set('maintenance_message_km', $request->input('message_km'), 'maintenance', true);
        }
        if ($request->has('message_en')) {
            Setting::set('maintenance_message_en', $request->input('message_en'), 'maintenance', true);
            if (! $request->filled('message')) {
                Setting::set('maintenance_message', $request->input('message_en'), 'maintenance', true);
            }
        }
        if ($request->has('scheduled_end')) {
            Setting::set('maintenance_scheduled_end', $request->input('scheduled_end'), 'maintenance', true);
        }
        if ($request->has('allowed_ips')) {
            Setting::set('maintenance_allowed_ips', $request->input('allowed_ips'), 'maintenance', true);
        }

        return $this->show();
    }
}
