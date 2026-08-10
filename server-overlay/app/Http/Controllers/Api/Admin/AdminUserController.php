<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

// Toggles admin status on an existing user. "Admin" isn't a separate
// model/table — it's the is_admin flag on User — so this only flips that
// flag; browsing/searching/creating users lives in UserManagementController.
class AdminUserController extends Controller
{
    public function promote(Request $request, User $user)
    {
        if ($user->is_admin) {
            throw new ApiException('This user already has admin access.', 'ALREADY_ADMIN', 409);
        }

        $user->is_admin = true;
        $user->save();

        AuditLog::record($request->user(), 'admin.promoted', $user);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->assertIsAdmin($user);

        if ($user->id === $request->user()->id) {
            throw new ApiException('You cannot remove your own admin access.', 'CANNOT_REMOVE_SELF', 422);
        }

        $user->is_admin = false;
        $user->save();

        AuditLog::record($request->user(), 'admin.demoted', $user);

        return response()->json($user);
    }

    private function assertIsAdmin(User $user): void
    {
        if (! $user->is_admin) {
            throw new ApiException('This user is not an admin.', 'NOT_AN_ADMIN', 404);
        }
    }
}
