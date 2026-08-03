<?php

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to list friends is rejected', function () {
    $response = $this->getJson('/api/friends');

    $response->assertStatus(401);
});

test('a user sees their accepted friends regardless of who sent the request', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $friendWhoSent = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $friendWhoReceived = User::create([
        'first_name' => 'Jordan', 'last_name' => 'Lee',
        'email' => 'jordan@example.com', 'password' => 'supersecret',
    ]);
    $pendingOnly = User::create([
        'first_name' => 'Not', 'last_name' => 'Yet',
        'email' => 'notyet@example.com', 'password' => 'supersecret',
    ]);

    FriendRequest::create(['sender_id' => $friendWhoSent->id, 'recipient_id' => $me->id, 'status' => 'accepted']);
    FriendRequest::create(['sender_id' => $me->id, 'recipient_id' => $friendWhoReceived->id, 'status' => 'accepted']);
    FriendRequest::create(['sender_id' => $pendingOnly->id, 'recipient_id' => $me->id, 'status' => 'pending']);

    $response = $this->getJson('/api/friends', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($friendWhoSent->id);
    expect($ids)->toContain($friendWhoReceived->id);
    expect($ids)->not->toContain($pendingOnly->id);
    expect($ids)->toHaveCount(2);
});

test('a user can remove a friend, in either direction', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $friend = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    FriendRequest::create(['sender_id' => $friend->id, 'recipient_id' => $me->id, 'status' => 'accepted']);

    $response = $this->deleteJson("/api/friends/{$friend->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect(FriendRequest::where('sender_id', $friend->id)->where('recipient_id', $me->id)->exists())->toBeFalse();
});

test('removing a non-friend returns 404', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $stranger = User::create([
        'first_name' => 'Stranger', 'last_name' => 'Danger',
        'email' => 'stranger@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->deleteJson("/api/friends/{$stranger->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});
