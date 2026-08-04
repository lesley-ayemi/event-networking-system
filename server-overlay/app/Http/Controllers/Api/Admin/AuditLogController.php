<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // created_at alone isn't a reliable tiebreaker for rows inserted
        // within the same second, so order by id as well.
        $logs = AuditLog::with('admin')->latest()->latest('id')->paginate(20)->withQueryString();

        return response()->json($logs);
    }
}
