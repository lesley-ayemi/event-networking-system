<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $friends = FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)->orWhere('recipient_id', $userId);
            })
            ->with(['sender', 'recipient'])
            ->get()
            ->map(fn (FriendRequest $friendRequest) => $friendRequest->sender_id === $userId
                ? $friendRequest->recipient
                : $friendRequest->sender)
            ->values();

        return response()->json(['data' => $friends]);
    }

    public function destroy(Request $request, User $user)
    {
        $userId = $request->user()->id;

        $friendRequest = FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userId, $user) {
                $query->where(function ($q) use ($userId, $user) {
                    $q->where('sender_id', $userId)->where('recipient_id', $user->id);
                })->orWhere(function ($q) use ($userId, $user) {
                    $q->where('sender_id', $user->id)->where('recipient_id', $userId);
                });
            })
            ->first();

        if (! $friendRequest) {
            throw new ApiException('You are not friends with this user.', 'NOT_FRIENDS', 404);
        }

        $friendRequest->delete();

        return response()->json(['message' => 'Friend removed.']);
    }
}
