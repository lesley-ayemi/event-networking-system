<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const CONTEXT_WINDOW = 5;

    public function index(Request $request)
    {
        $query = Report::query()->with(['reporter', 'reportable', 'reviewer']);

        $query->when($request->filled('type'), fn ($q) => $q->where('reportable_type', $request->query('type')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')));

        $reports = $query->latest()->paginate(20)->withQueryString();

        // Matches the {data, meta} envelope EventResource::collection() produces
        // elsewhere, so the frontend can read pagination.meta consistently.
        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Report::STATUSES)],
        ]);

        $report->status = $validated['status'];
        if ($validated['status'] !== 'pending') {
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now();
        }
        $report->save();

        AuditLog::record($request->user(), 'report.updated', $report, ['status' => $validated['status']]);

        return response()->json($report->fresh(['reporter', 'reportable', 'reviewer']));
    }

    /**
     * Surfaces a small window of surrounding conversation around a reported
     * message so an admin can judge it in context, without a general
     * "browse any conversation" endpoint — this is deliberately only
     * reachable via an existing report on a specific message.
     */
    public function context(Request $request, Report $report)
    {
        if ($report->reportable_type !== 'message') {
            throw new ApiException('This report is not for a message.', 'REPORT_NOT_A_MESSAGE', 422);
        }

        $message = $report->reportable;
        if (! $message) {
            throw new ApiException('This message no longer exists.', 'MESSAGE_NOT_FOUND', 404);
        }

        $before = Message::where('conversation_id', $message->conversation_id)
            ->where('id', '<', $message->id)
            ->with('sender')
            ->latest('id')
            ->limit(self::CONTEXT_WINDOW)
            ->get()
            ->sortBy('id')
            ->values();

        $after = Message::where('conversation_id', $message->conversation_id)
            ->where('id', '>', $message->id)
            ->with('sender')
            ->oldest('id')
            ->limit(self::CONTEXT_WINDOW)
            ->get();

        $message->load('sender');

        $context = $before->concat([$message])->concat($after)->values();
        // Not fn() => $m->is_flagged = ...: Collection::each() treats a
        // callback returning false as a signal to stop iterating early, and
        // an arrow function's implicit return is the assignment's own value
        // — which is false for every non-flagged message, silently cutting
        // the loop short right after the first one.
        $context->each(function ($m) use ($message) {
            $m->is_flagged = $m->id === $message->id;
        });

        AuditLog::record($request->user(), 'report.viewed_context', $report);

        return response()->json(['data' => $context]);
    }
}
