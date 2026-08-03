<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFriendRequestRequest;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;

class FriendRequestController extends Controller
{
    public function store(StoreFriendRequestRequest $request)
    {
        $sender = $request->user();
        $recipientId = (int) $request->validated('recipient_id');

        if ($recipientId === $sender->id) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 422);
        }

        if (UserBlock::existsBetween($sender->id, $recipientId)) {
            return response()->json(['message' => 'You cannot send a friend request to this user.'], 403);
        }

        $alreadyExists = FriendRequest::query()
            ->where(function ($query) use ($sender, $recipientId) {
                $query->where('sender_id', $sender->id)->where('recipient_id', $recipientId);
            })
            ->orWhere(function ($query) use ($sender, $recipientId) {
                $query->where('sender_id', $recipientId)->where('recipient_id', $sender->id);
            })
            ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'A friend request already exists between you and this user.'], 409);
        }

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'status' => 'pending',
        ]);

        return response()->json($friendRequest->load(['sender', 'recipient']), 201);
    }

    public function incoming(Request $request)
    {
        $requests = FriendRequest::query()
            ->where('recipient_id', $request->user()->id)
            ->where('status', 'pending')
            ->with('sender')
            ->latest()
            ->get();

        return response()->json(['data' => $requests]);
    }

    public function outgoing(Request $request)
    {
        $requests = FriendRequest::query()
            ->where('sender_id', $request->user()->id)
            ->where('status', 'pending')
            ->with('recipient')
            ->latest()
            ->get();

        return response()->json(['data' => $requests]);
    }

    public function accept(Request $request, FriendRequest $friendRequest)
    {
        abort_if($friendRequest->recipient_id !== $request->user()->id, 403);
        abort_if($friendRequest->status !== 'pending', 409, 'This request is no longer pending.');

        $friendRequest->update(['status' => 'accepted']);

        return response()->json($friendRequest->load(['sender', 'recipient']));
    }

    /**
     * Deletes the request outright rather than storing a 'declined' status —
     * there is no reason field anywhere, so there is nothing to leak back to
     * the sender about why (spec section 18).
     */
    public function decline(Request $request, FriendRequest $friendRequest)
    {
        abort_if($friendRequest->recipient_id !== $request->user()->id, 403);
        abort_if($friendRequest->status !== 'pending', 409, 'This request is no longer pending.');

        $friendRequest->delete();

        return response()->json(['message' => 'Request declined.']);
    }
}
