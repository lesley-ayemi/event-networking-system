<?php

use App\Models\FriendRequest;
use App\Models\User;
use App\Services\MessagingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('friends can message each other even without open messaging enabled', function () {
    $userA = User::create([
        'first_name' => 'A', 'last_name' => 'User', 'email' => 'a@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    $userB = User::create([
        'first_name' => 'B', 'last_name' => 'User', 'email' => 'b@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    FriendRequest::create(['sender_id' => $userA->id, 'recipient_id' => $userB->id, 'status' => 'accepted']);

    expect(MessagingPolicy::canMessage($userA, $userB))->toBeTrue();
});

test('non-friends can message each other when both have open messaging enabled', function () {
    $userA = User::create([
        'first_name' => 'A', 'last_name' => 'User', 'email' => 'a@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => true],
    ]);
    $userB = User::create([
        'first_name' => 'B', 'last_name' => 'User', 'email' => 'b@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => true],
    ]);

    expect(MessagingPolicy::canMessage($userA, $userB))->toBeTrue();
});

test('non-friends cannot message when only one has open messaging enabled', function () {
    $userA = User::create([
        'first_name' => 'A', 'last_name' => 'User', 'email' => 'a@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => true],
    ]);
    $userB = User::create([
        'first_name' => 'B', 'last_name' => 'User', 'email' => 'b@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);

    expect(MessagingPolicy::canMessage($userA, $userB))->toBeFalse();
});

test('non-friends without open messaging cannot message', function () {
    $userA = User::create([
        'first_name' => 'A', 'last_name' => 'User', 'email' => 'a@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    $userB = User::create([
        'first_name' => 'B', 'last_name' => 'User', 'email' => 'b@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);

    expect(MessagingPolicy::canMessage($userA, $userB))->toBeFalse();
});

test('a pending (not yet accepted) friend request does not grant messaging', function () {
    $userA = User::create([
        'first_name' => 'A', 'last_name' => 'User', 'email' => 'a@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    $userB = User::create([
        'first_name' => 'B', 'last_name' => 'User', 'email' => 'b@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['allow_message_first' => false],
    ]);
    FriendRequest::create(['sender_id' => $userA->id, 'recipient_id' => $userB->id, 'status' => 'pending']);

    expect(MessagingPolicy::canMessage($userA, $userB))->toBeFalse();
});
