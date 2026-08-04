<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// CRUD over admin accounts. "Admin" isn't a separate model/table — it's the
// is_admin flag on User — so destroy() here means revoking admin access
// (demotion), not deleting the underlying user account.
class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('is_admin', true)->orderBy('first_name')->get();

        return response()->json(['data' => $admins]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => true,
        ]);

        AuditLog::record($request->user(), 'admin.created', $admin);

        return response()->json($admin, 201);
    }

    public function update(Request $request, User $user)
    {
        $this->assertIsAdmin($user);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        AuditLog::record($request->user(), 'admin.updated', $user);

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
