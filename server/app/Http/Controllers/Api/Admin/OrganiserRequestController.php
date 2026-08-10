<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class OrganiserRequestController extends Controller
{
    public function index()
    {
        $users = User::where('organiser_status', 'pending')
            ->orderBy('organiser_requested_at')
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

    public function approve(Request $request, User $user)
    {
        $this->assertPending($user);

        $user->organiser_status = 'approved';
        $user->save();

        AuditLog::record($request->user(), 'organiser.approved', $user);

        return response()->json($user);
    }

    public function reject(Request $request, User $user)
    {
        $this->assertPending($user);

        $user->organiser_status = 'rejected';
        $user->save();

        AuditLog::record($request->user(), 'organiser.rejected', $user);

        return response()->json($user);
    }

    private function assertPending(User $user): void
    {
        if ($user->organiser_status !== 'pending') {
            throw new ApiException(
                'This user does not have a pending organiser request.',
                'ORGANISER_REQUEST_NOT_PENDING',
                409,
            );
        }
    }
}
