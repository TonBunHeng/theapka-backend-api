<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreScheduleRequest;
use App\Http\Requests\User\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = Schedule::orderBy('order')->orderBy('start_time')->get();

        return response()->json([
            'data' => ScheduleResource::collection($schedules),
        ]);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $schedule = Schedule::create([
            'wedding_id' => $wedding->id,
            ...$request->validated(),
        ]);

        return response()->json([
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        return response()->json([
            'data' => new ScheduleResource($schedule),
        ]);
    }

    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update($request->validated());

        return response()->json([
            'data' => new ScheduleResource($schedule),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'data' => [
                'message' => 'Schedule item deleted successfully.',
            ],
        ]);
    }
}
