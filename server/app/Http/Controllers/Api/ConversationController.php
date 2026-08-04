<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\MessagingPolicy;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->with(['participants.user', 'latestMessage'])
            ->get()
            ->sortByDesc(fn (Conversation $conversation) => $conversation->latestMessage?->created_at ?? $conversation->created_at)
            ->values()
            ->map(fn (Conversation $conversation) => $this->formatConversation($conversation, $user));

        return response()->json(['data' => $conversations]);
    }

    public function store(StoreConversationRequest $request)
    {
        $user = $request->user();
        $recipientId = (int) $request->validated('recipient_id');

        if ($recipientId === $user->id) {
            throw new ApiException('You cannot start a conversation with yourself.', 'CANNOT_MESSAGE_SELF', 422);
        }

        $recipient = User::findOrFail($recipientId);

        if (UserBlock::existsBetween($user->id, $recipient->id)) {
            throw new ApiException('You cannot message this user.', 'USER_BLOCKED', 403);
        }

        if (! MessagingPolicy::canMessage($user, $recipient)) {
            throw new ApiException(
                'You can only message this person once you are friends, or if you both allow open messaging.',
                'MESSAGING_NOT_PERMITTED',
                403,
            );
        }

        $conversation = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->id))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create();
            $conversation->participants()->createMany([
                ['user_id' => $user->id],
                ['user_id' => $recipient->id],
            ]);
        }

        $conversation->load(['participants.user', 'latestMessage']);

        return response()->json($this->formatConversation($conversation, $user), 201);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['participants.user', 'latestMessage']);

        return response()->json($this->formatConversation($conversation, $request->user()));
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $participant = $this->authorizeParticipant($request, $conversation);

        $participant->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): ConversationParticipant
    {
        $participant = $conversation->participants()->where('user_id', $request->user()->id)->first();

        abort_if(! $participant, 403);

        return $participant;
    }

    private function formatConversation(Conversation $conversation, User $viewer): array
    {
        $otherParticipant = $conversation->participants->firstWhere('user_id', '!=', $viewer->id);
        $myParticipant = $conversation->participants->firstWhere('user_id', $viewer->id);

        $unreadCount = $conversation->messages()
            ->where('sender_id', '!=', $viewer->id)
            ->when($myParticipant?->last_read_at, fn ($query, $lastReadAt) => $query->where('created_at', '>', $lastReadAt))
            ->count();

        return [
            'id' => $conversation->id,
            'other_user' => $otherParticipant?->user,
            'last_message' => $conversation->latestMessage ? [
                'body' => $conversation->latestMessage->body,
                'sender_id' => $conversation->latestMessage->sender_id,
                'created_at' => $conversation->latestMessage->created_at,
            ] : null,
            'unread_count' => $unreadCount,
            // Lets the frontend show "read" status on messages I sent, by
            // comparing each message's created_at against this timestamp.
            'other_last_read_at' => $otherParticipant?->last_read_at,
            'created_at' => $conversation->created_at,
        ];
    }
}
