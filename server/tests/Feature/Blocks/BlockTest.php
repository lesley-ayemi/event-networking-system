<?php

use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to block a user is rejected', function () {
    $response = $this->postJson('/api/blocks/1');

    $response->assertStatus(401);
});

test('a user can block another user', function () {
    $blocker = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $blocker->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson("/api/blocks/{$target->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    expect(UserBlock::where('blocker_id', $blocker->id)->where('blocked_id', $target->id)->exists())->toBeTrue();
});

test('a user cannot block themselves', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson("/api/blocks/{$user->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
});

test('blocking a user removes any existing friend request between them', function () {
    $blocker = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $blocker->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    FriendRequest::create(['sender_id' => $target->id, 'recipient_id' => $blocker->id, 'status' => 'accepted']);

    $this->postJson("/api/blocks/{$target->id}", [], ['Authorization' => "Bearer {$token}"]);

    expect(FriendRequest::where('sender_id', $target->id)->where('recipient_id', $blocker->id)->exists())->toBeFalse();
});

test('a user can unblock another user', function () {
    $blocker = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $blocker->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    UserBlock::create(['blocker_id' => $blocker->id, 'blocked_id' => $target->id]);

    $response = $this->deleteJson("/api/blocks/{$target->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect(UserBlock::where('blocker_id', $blocker->id)->where('blocked_id', $target->id)->exists())->toBeFalse();
});

test('a user can list who they have blocked', function () {
    $blocker = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $blocker->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    UserBlock::create(['blocker_id' => $blocker->id, 'blocked_id' => $target->id]);

    $response = $this->getJson('/api/blocks', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.id', $target->id);
});
