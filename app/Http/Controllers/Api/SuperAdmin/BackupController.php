<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * List all database backup snapshots.
     */
    public function index(Request $request): JsonResponse
    {
        $backups = Backup::latest('created_at')->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => BackupResource::collection($backups->items()),
            'meta' => [
                'page' => $backups->currentPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
                'last_page' => $backups->lastPage(),
            ],
        ]);
    }

    /**
     * Trigger a new database backup.
     */
    public function store(Request $request): JsonResponse
    {
        $backup = $this->backupService->createBackup($request->user());

        return response()->json([
            'data' => new BackupResource($backup),
        ], 201);
    }

    /**
     * Restore database from backup snapshot.
     */
    public function restore(int $id): JsonResponse
    {
        $backup = Backup::findOrFail($id);

        $this->backupService->restore($backup);

        return response()->json([
            'data' => [
                'message' => "Database restored successfully from backup {$backup->file_name}.",
                'backup' => new BackupResource($backup),
            ],
        ]);
    }

    /**
     * Download backup file.
     */
    public function download(int $id): StreamedResponse|JsonResponse
    {
        $backup = Backup::findOrFail($id);
        $path = 'backups/' . $backup->file_name;

        if (! Storage::disk($backup->disk)->exists($path)) {
            return response()->json([
                'message' => 'Backup file not found on disk.',
            ], 404);
        }

        return Storage::disk($backup->disk)->download($path, $backup->file_name);
    }
}
