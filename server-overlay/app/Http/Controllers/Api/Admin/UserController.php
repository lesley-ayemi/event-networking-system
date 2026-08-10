<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\AccountUnsuspendedNotification;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // "Unusual activity" is grounded in data the app already collects rather
    // than a separate anomaly-detection system: accounts with several reports
    // filed against them are surfaced here for review.
    private const FLAGGED_THRESHOLD = 3;

    public function flagged(Request $request)
    {
        $threshold = (int) ($request->query('min') ?? self::FLAGGED_THRESHOLD);

        // A HAVING clause referencing a withCount() alias is a MySQL-only
        // extension; has() compiles to a portable subquery count comparison
        // that also works on SQLite (used in tests).
        $users = User::query()
            ->withCount(['receivedReports as reports_count'])
            ->has('receivedReports', '>=', $threshold)
            ->orderByDesc('reports_count')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function suspend(Request $request, User $user)
    {
        if ($user->is_suspended) {
            throw new ApiException('This user is already suspended.', 'ALREADY_SUSPENDED', 409);
        }

        $user->is_suspended = true;
        $user->suspended_at = now();
        $user->save();

        // Suspension takes effect immediately, not just on next login.
        $user->tokens()->delete();

        AuditLog::record($request->user(), 'user.suspended', $user);
        $user->notify(new AccountSuspendedNotification());

        return response()->json($user);
    }

    public function unsuspend(Request $request, User $user)
    {
        if (! $user->is_suspended) {
            throw new ApiException('This user is not suspended.', 'NOT_SUSPENDED', 409);
        }

        $user->is_suspended = false;
        $user->suspended_at = null;
        $user->save();

        AuditLog::record($request->user(), 'user.unsuspended', $user);
        $user->notify(new AccountUnsuspendedNotification());

        return response()->json($user);
    }
}
