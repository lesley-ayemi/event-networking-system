<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\Models\Report;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request)
    {
        $user = $request->user();
        $alias = $request->validated('reportable_type');

        $modelClass = Relation::getMorphedModel($alias);
        $reportable = $modelClass ? $modelClass::find($request->validated('reportable_id')) : null;

        if (! $reportable) {
            throw new ApiException("We couldn't find what you were trying to report.", 'REPORT_TARGET_NOT_FOUND', 404);
        }

        if ($alias === 'user' && (int) $reportable->id === $user->id) {
            throw new ApiException('You cannot report yourself.', 'CANNOT_REPORT_SELF', 422);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => $alias,
            'reportable_id' => $reportable->id,
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
        ]);

        return response()->json($report, 201);
    }
}
