<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMessageRequest;
use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private const RECENT_MESSAGE_LIMIT = 50;

    public function index(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->limit(self::RECENT_MESSAGE_LIMIT)
            ->get()
            ->sortBy('id')
            ->values();

        return response()->json(['data' => $messages]);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);
        $message->load('sender');

        // Sending a message implicitly marks the conversation read for the
        // sender up to this point — they've obviously "read" their own message.
        $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        // toOthers() skips the sender's own connection (identified via the
        // X-Socket-Id header the frontend attaches), since they already have
        // this message from the HTTP response — no need to echo it back.
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_if(
            ! $conversation->participants()->where('user_id', $request->user()->id)->exists(),
            403
        );
    }
}
