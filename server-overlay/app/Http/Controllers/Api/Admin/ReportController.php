<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query()->with(['reporter', 'reportable', 'reviewer']);

        $query->when($request->filled('type'), fn ($q) => $q->where('reportable_type', $request->query('type')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')));

        $reports = $query->latest()->paginate(20)->withQueryString();

        return response()->json($reports);
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
}
