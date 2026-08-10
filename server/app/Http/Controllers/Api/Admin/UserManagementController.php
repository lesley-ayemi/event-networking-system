<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// General user management: browse/search every account, edit core profile
// fields, and delete (soft) an account. Suspend/unsuspend, admin promotion,
// and organiser approval each already have their own dedicated, guarded
// endpoints elsewhere — this controller deliberately doesn't duplicate that
// business logic, it only covers identity/profile fields.
class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $status = $request->query('status');
        if ($status === 'suspended') {
            $query->where('is_suspended', true);
        } elseif ($status === 'deleted') {
            $query->onlyTrashed();
        }

        $role = $request->query('role');
        if ($role === 'admin') {
            $query->where('is_admin', true);
        } elseif ($role === 'organiser') {
            $query->where('organiser_status', 'approved');
        }

        $users = $query->orderBy('first_name')->paginate(20)->withQueryString();

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

    public function show(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        AuditLog::record($request->user(), 'user.created', $user);

        return response()->json($user, 201);
    }

    public function update(Request $request, string $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        // Laravel's default ConvertEmptyStringsToNull middleware turns a blank
        // "" input into null before validation runs. These fields are optional
        // text the admin form lets an operator clear, so restore "" here rather
        // than loosening the rules to accept null (see UpdateProfileRequest,
        // which does the same for the user-facing equivalent of this form).
        foreach (['job_title', 'industry', 'bio'] as $field) {
            if ($request->has($field) && $request->input($field) === null) {
                $request->merge([$field => '']);
            }
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string'],
        ]);

        $user->update($validated);

        AuditLog::record($request->user(), 'user.updated', $user);

        return response()->json($user);
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === $request->user()->id) {
            throw new ApiException('You cannot delete your own account.', 'CANNOT_DELETE_SELF', 422);
        }

        if ($user->is_admin) {
            throw new ApiException(
                'Remove admin access before deleting this account.',
                'MUST_REMOVE_ADMIN_FIRST',
                422
            );
        }

        $user->tokens()->delete();
        $user->delete();

        AuditLog::record($request->user(), 'user.deleted', $user, ['name' => "{$user->first_name} {$user->last_name}"]);

        return response()->json(null, 204);
    }
}
