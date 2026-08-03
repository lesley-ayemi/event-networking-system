<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('an unauthenticated request to list messages is rejected', function () {
    $conversation = Conversation::create();

    $response = $this->getJson("/api/conversations/{$conversation->id}/messages");

    $response->assertStatus(401);
});

test('a participant can list messages in chronological order', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $conversation = Conversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $me->id],
        ['user_id' => $friend->id],
    ]);
    $conversation->messages()->create(['sender_id' => $me->id, 'body' => 'First']);
    $conversation->messages()->create(['sender_id' => $friend->id, 'body' => 'Second']);

    $response = $this->getJson("/api/conversations/{$conversation->id}/messages", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data.0.body'))->toBe('First');
    expect($response->json('data.1.body'))->toBe('Second');
});

test('a non-participant cannot list messages', function () {
    $outsider = User::create([
        'first_name' => 'Out', 'last_name' => 'Sider',
        'email' => 'outsider@example.com', 'password' => 'supersecret',
    ]);
    $token = $outsider->createToken('api-token')->plainTextToken;
    $conversation = Conversation::create();

    $response = $this->getJson("/api/conversations/{$conversation->id}/messages", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('a participant can send a message and it broadcasts', function () {
    Event::fake([MessageSent::class]);

    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $conversation = Conversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $me->id],
        ['user_id' => $friend->id],
    ]);

    $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
        'body' => 'Hello!',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('body', 'Hello!');
    $response->assertJsonPath('sender.id', $me->id);

    Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->body === 'Hello!');
});

test('sending a message marks the conversation as read for the sender', function () {
    Event::fake([MessageSent::class]);

    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $conversation = Conversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $me->id],
        ['user_id' => $friend->id],
    ]);

    $this->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hello!'], ['Authorization' => "Bearer {$token}"]);

    expect($conversation->participants()->where('user_id', $me->id)->first()->last_read_at)->not->toBeNull();
});

test('a non-participant cannot send a message', function () {
    $outsider = User::create([
        'first_name' => 'Out', 'last_name' => 'Sider',
        'email' => 'outsider@example.com', 'password' => 'supersecret',
    ]);
    $token = $outsider->createToken('api-token')->plainTextToken;
    $conversation = Conversation::create();

    $response = $this->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hi'], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('a message body is required', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $conversation = Conversation::create();
    $conversation->participants()->create(['user_id' => $me->id]);

    $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
});
