<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * List audit logs with actor and subject filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('actor');

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($actorId = $request->query('actor_id')) {
            $query->where('actor_id', $actorId);
        }
        if ($subjectType = $request->query('subject_type')) {
            $query->where('subject_type', $subjectType);
        }
        if ($fromDate = $request->query('from_date')) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate = $request->query('to_date')) {
            $query->where('created_at', '<=', $toDate);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $logs = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'data' => AuditLogResource::collection($logs->items()),
            'meta' => [
                'page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Export audit logs to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Actor ID', 'Actor Name', 'Action', 'Subject Type', 'Subject ID', 'IP Address', 'Created At']);

            AuditLog::with('actor')->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->actor_id,
                        $log->actor?->name ?? 'System',
                        $log->action,
                        $log->subject_type,
                        $log->subject_id,
                        $log->ip_address,
                        $log->created_at,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
