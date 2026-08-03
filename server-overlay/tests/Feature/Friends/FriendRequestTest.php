<?php

use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('an unauthenticated request to send a friend request is rejected', function () {
    $response = $this->postJson('/api/friends/requests', ['recipient_id' => 1]);

    $response->assertStatus(401);
});

test('a user can send a friend request', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $sender->createToken('api-token')->plainTextToken;
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/friends/requests', [
        'recipient_id' => $recipient->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('sender.id', $sender->id);
    $response->assertJsonPath('recipient.id', $recipient->id);
    $response->assertJsonPath('status', 'pending');
});

test('a user cannot send a friend request to themselves', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/friends/requests', [
        'recipient_id' => $user->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
});

test('a user cannot send a duplicate friend request in either direction', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $sender->createToken('api-token')->plainTextToken;
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    FriendRequest::create(['sender_id' => $recipient->id, 'recipient_id' => $sender->id, 'status' => 'pending']);

    $response = $this->postJson('/api/friends/requests', [
        'recipient_id' => $recipient->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
});

test('a user cannot send a friend request across a block', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $sender->createToken('api-token')->plainTextToken;
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    UserBlock::create(['blocker_id' => $recipient->id, 'blocked_id' => $sender->id]);

    $response = $this->postJson('/api/friends/requests', [
        'recipient_id' => $recipient->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('the recipient can accept a friend request', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $token = $recipient->createToken('api-token')->plainTextToken;
    $friendRequest = FriendRequest::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'status' => 'pending']);

    $response = $this->patchJson("/api/friends/requests/{$friendRequest->id}/accept", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'accepted');
});

test('only the recipient can accept a friend request', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $sender->createToken('api-token')->plainTextToken;
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $friendRequest = FriendRequest::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'status' => 'pending']);

    $response = $this->patchJson("/api/friends/requests/{$friendRequest->id}/accept", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('the recipient can decline a friend request, and it disappears without a reason', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $recipientToken = $recipient->createToken('api-token')->plainTextToken;
    $senderToken = $sender->createToken('api-token')->plainTextToken;
    $friendRequest = FriendRequest::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'status' => 'pending']);

    $response = $this->patchJson("/api/friends/requests/{$friendRequest->id}/decline", [], ['Authorization' => "Bearer {$recipientToken}"]);

    $response->assertStatus(200);
    expect($response->json())->not->toHaveKey('reason');
    expect(FriendRequest::find($friendRequest->id))->toBeNull();

    // The sender's outgoing list simply no longer shows it — no distinct "declined" state to observe.
    // Sanctum's guard memoizes the resolved user within a test, so a second
    // request as a different token needs a fresh guard to actually re-check.
    Auth::forgetGuards();
    $outgoing = $this->getJson('/api/friends/requests/outgoing', ['Authorization' => "Bearer {$senderToken}"]);
    expect($outgoing->json('data'))->toHaveCount(0);
});

test('incoming and outgoing lists only show pending requests for the right user', function () {
    $sender = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $senderToken = $sender->createToken('api-token')->plainTextToken;
    $recipient = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $recipientToken = $recipient->createToken('api-token')->plainTextToken;

    FriendRequest::create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'status' => 'pending']);

    $incoming = $this->getJson('/api/friends/requests/incoming', ['Authorization' => "Bearer {$recipientToken}"]);
    $incoming->assertStatus(200);
    expect($incoming->json('data'))->toHaveCount(1);
    $incoming->assertJsonPath('data.0.sender.id', $sender->id);

    Auth::forgetGuards();
    $outgoing = $this->getJson('/api/friends/requests/outgoing', ['Authorization' => "Bearer {$senderToken}"]);
    $outgoing->assertStatus(200);
    expect($outgoing->json('data'))->toHaveCount(1);
    $outgoing->assertJsonPath('data.0.recipient.id', $recipient->id);
});
