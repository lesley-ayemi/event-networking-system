<?php

use App\Models\Conversation;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to list conversations is rejected', function () {
    $response = $this->getJson('/api/conversations');

    $response->assertStatus(401);
});

test('friends can start a conversation', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    FriendRequest::create(['sender_id' => $me->id, 'recipient_id' => $friend->id, 'status' => 'accepted']);

    $response = $this->postJson('/api/conversations', [
        'recipient_id' => $friend->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('other_user.id', $friend->id);
    expect(Conversation::count())->toBe(1);
});

test('starting a conversation twice reuses the existing one', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    FriendRequest::create(['sender_id' => $me->id, 'recipient_id' => $friend->id, 'status' => 'accepted']);

    $first = $this->postJson('/api/conversations', ['recipient_id' => $friend->id], ['Authorization' => "Bearer {$token}"]);
    $second = $this->postJson('/api/conversations', ['recipient_id' => $friend->id], ['Authorization' => "Bearer {$token}"]);

    expect($first->json('id'))->toBe($second->json('id'));
    expect(Conversation::count())->toBe(1);
});

test('a user cannot start a conversation with themselves', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/conversations', ['recipient_id' => $me->id], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
});

test('a user cannot message someone they are not friends with and who has not enabled open messaging', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $stranger = User::create([
        'first_name' => 'Stranger', 'last_name' => 'Danger',
        'email' => 'stranger@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);

    $response = $this->postJson('/api/conversations', ['recipient_id' => $stranger->id], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('strangers can message each other when both enabled open messaging', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => true],
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $stranger = User::create([
        'first_name' => 'Stranger', 'last_name' => 'Danger',
        'email' => 'stranger@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => true],
    ]);

    $response = $this->postJson('/api/conversations', ['recipient_id' => $stranger->id], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
});

test('a user cannot message someone they have blocked or who has blocked them', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    FriendRequest::create(['sender_id' => $me->id, 'recipient_id' => $friend->id, 'status' => 'accepted']);
    UserBlock::create(['blocker_id' => $friend->id, 'blocked_id' => $me->id]);

    $response = $this->postJson('/api/conversations', ['recipient_id' => $friend->id], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('only participants can view a conversation', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $outsider = User::create([
        'first_name' => 'Out', 'last_name' => 'Sider',
        'email' => 'outsider@example.com', 'password' => 'supersecret',
    ]);
    $outsiderToken = $outsider->createToken('api-token')->plainTextToken;

    $conversation = Conversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $me->id],
        ['user_id' => $friend->id],
    ]);

    $response = $this->getJson("/api/conversations/{$conversation->id}", ['Authorization' => "Bearer {$outsiderToken}"]);

    $response->assertStatus(403);
});

test('a user can mark a conversation as read', function () {
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

    $response = $this->postJson("/api/conversations/{$conversation->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($conversation->participants()->where('user_id', $me->id)->first()->last_read_at)->not->toBeNull();
});

test('the conversation list includes an unread count and last-message preview', function () {
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
    $conversation->messages()->create(['sender_id' => $friend->id, 'body' => 'Hey there!']);
    $conversation->messages()->create(['sender_id' => $friend->id, 'body' => 'Are you around?']);

    $response = $this->getJson('/api/conversations', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.unread_count', 2);
    $response->assertJsonPath('data.0.last_message.body', 'Are you around?');
});

test('a conversation exposes the other participant\'s last_read_at for read-status display', function () {
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
        ['user_id' => $friend->id, 'last_read_at' => '2026-08-03 12:00:00'],
    ]);

    $response = $this->getJson("/api/conversations/{$conversation->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('other_last_read_at'))->toStartWith('2026-08-03T12:00:00');
});
