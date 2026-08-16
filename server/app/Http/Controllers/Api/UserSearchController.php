<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    /**
     * Name search for finding someone to send a friend request to. Same
     * curated field set as UserProfileController::show — this is a
     * directory listing, not the authenticated user's own record.
     */
    public function index(Request $request)
    {
        $term = mb_strtolower(trim((string) $request->query('q', '')));

        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $viewerId = $request->user()->id;

        $blockedIds = UserBlock::query()
            ->where('blocker_id', $viewerId)
            ->orWhere('blocked_id', $viewerId)
            ->get(['blocker_id', 'blocked_id'])
            ->flatMap(fn (UserBlock $block) => [$block->blocker_id, $block->blocked_id])
            ->unique();

        $like = "%{$term}%";

        $users = User::query()
            ->where('id', '!=', $viewerId)
            ->where('is_suspended', false)
            ->whereNotIn('id', $blockedIds)
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(first_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$like]);
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'job_title' => $user->job_title,
                'industry' => $user->industry,
                'profile_image' => $user->profile_image,
                'availability_status' => $user->availability_status,
                'availability_status_updated_at' => $user->availability_status_updated_at,
            ])->values(),
        ]);
    }
}
