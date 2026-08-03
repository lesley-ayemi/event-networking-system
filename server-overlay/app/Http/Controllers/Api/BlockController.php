<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request)
    {
        $blocked = $request->user()
            ->blockedUsers()
            ->get();

        return response()->json(['data' => $blocked]);
    }

    public function store(Request $request, User $user)
    {
        $blocker = $request->user();

        if ($user->id === $blocker->id) {
            return response()->json(['message' => 'You cannot block yourself.'], 422);
        }

        if (UserBlock::where('blocker_id', $blocker->id)->where('blocked_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already blocked this user.'], 409);
        }

        UserBlock::create(['blocker_id' => $blocker->id, 'blocked_id' => $user->id]);

        // Blocking implies severing any existing relationship or pending
        // request between the pair, in either direction.
        FriendRequest::query()
            ->where(function ($query) use ($blocker, $user) {
                $query->where('sender_id', $blocker->id)->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($blocker, $user) {
                $query->where('sender_id', $user->id)->where('recipient_id', $blocker->id);
            })
            ->delete();

        return response()->json(['message' => 'User blocked.'], 201);
    }

    public function destroy(Request $request, User $user)
    {
        $deleted = UserBlock::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'You have not blocked this user.'], 404);
        }

        return response()->json(['message' => 'User unblocked.']);
    }
}
