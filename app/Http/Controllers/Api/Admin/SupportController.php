<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportAssignRequest;
use App\Http\Requests\Admin\SupportReplyRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * List support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user', 'assignee']);

        if ($search = $request->query('search')) {
            $query->where('subject', 'like', "%{$search}%");
        }

        $filters = $request->query('filter', []);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $tickets = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => SupportTicketResource::collection($tickets->items()),
            'meta' => [
                'page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    /**
     * Show a support ticket with messages.
     */
    public function show(int $id): JsonResponse
    {
        $ticket = SupportTicket::with(['user', 'assignee', 'messages.user'])->findOrFail($id);

        return response()->json([
            'data' => new SupportTicketResource($ticket),
        ]);
    }

    /**
     * Reply to a support ticket.
     */
    public function reply(SupportReplyRequest $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'attachments' => $request->attachments,
        ]);

        if ($request->boolean('close_ticket')) {
            $ticket->update(['status' => 'closed']);
        } else {
            $ticket->update(['status' => 'in_progress']);
        }

        $ticket->load(['user', 'assignee', 'messages.user']);

        return response()->json([
            'data' => [
                'message' => 'Reply posted successfully.',
                'ticket' => new SupportTicketResource($ticket),
            ],
        ]);
    }

    /**
     * Assign ticket to staff member.
     */
    public function assign(SupportAssignRequest $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['assigned_to' => $request->assigned_to]);
        $ticket->load(['user', 'assignee', 'messages.user']);

        return response()->json([
            'data' => [
                'message' => 'Ticket assigned successfully.',
                'ticket' => new SupportTicketResource($ticket),
            ],
        ]);
    }

    /**
     * Close a support ticket.
     */
    public function close(int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => 'closed']);
        $ticket->load(['user', 'assignee', 'messages.user']);

        return response()->json([
            'data' => [
                'message' => 'Ticket closed successfully.',
                'ticket' => new SupportTicketResource($ticket),
            ],
        ]);
    }
}
