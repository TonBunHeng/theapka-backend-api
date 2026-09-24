<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\GuestImportCommitRequest;
use App\Http\Requests\User\GuestImportPreviewRequest;
use App\Services\GuestImportService;
use Illuminate\Http\JsonResponse;

class GuestImportController extends Controller
{
    public function __construct(
        protected GuestImportService $guestImportService
    ) {}

    /**
     * Preview CSV import with per-row validation and duplicate detection.
     */
    public function preview(GuestImportPreviewRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $preview = $this->guestImportService->preview($request->file('file'), $wedding);

        return response()->json([
            'data' => $preview,
        ]);
    }

    /**
     * Commit validated rows to the database.
     */
    public function commit(GuestImportCommitRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $importedCount = $this->guestImportService->commit($wedding, $request->validated('rows'));

        return response()->json([
            'data' => [
                'imported_count' => $importedCount,
                'message' => "Successfully imported {$importedCount} guests.",
            ],
        ]);
    }

    /**
     * Direct import of guests from array (used by frontend CSV importer).
     */
    public function import(\Illuminate\Http\Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $items = (array) $request->input('guests', $request->input('rows', []));
        $importedCount = $this->guestImportService->commit($wedding, $items);

        return response()->json([
            'data' => [
                'count' => $importedCount,
                'imported_count' => $importedCount,
                'message' => "Successfully imported {$importedCount} guests.",
            ],
        ]);
    }
}
