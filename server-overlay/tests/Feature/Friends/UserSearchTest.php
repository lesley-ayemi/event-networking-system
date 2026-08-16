<?php

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to search users is rejected', function () {
    $response = $this->getJson('/api/users/search?q=sam');

    $response->assertStatus(401);
});

test('an empty query returns no results instead of the whole directory', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    User::create(['first_name' => 'Sam', 'last_name' => 'Rivera', 'email' => 'sam@example.com', 'password' => 'supersecret']);

    $response = $this->getJson('/api/users/search?q=', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(0);
});

test('search matches first name, last name, or full name, case-insensitively', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;
    $sam = User::create(['first_name' => 'Sam', 'last_name' => 'Rivera', 'email' => 'sam@example.com', 'password' => 'supersecret']);
    User::create(['first_name' => 'Jordan', 'last_name' => 'Lee', 'email' => 'jordan@example.com', 'password' => 'supersecret']);

    $byFirstName = $this->getJson('/api/users/search?q=SAM', ['Authorization' => "Bearer {$token}"]);
    expect(collect($byFirstName->json('data'))->pluck('id')->all())->toBe([$sam->id]);

    $byLastName = $this->getJson('/api/users/search?q=rivera', ['Authorization' => "Bearer {$token}"]);
    expect(collect($byLastName->json('data'))->pluck('id')->all())->toBe([$sam->id]);

    $byFullName = $this->getJson('/api/users/search?q=sam+rivera', ['Authorization' => "Bearer {$token}"]);
    expect(collect($byFullName->json('data'))->pluck('id')->all())->toBe([$sam->id]);
});

test('search excludes the viewer themselves', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/users/search?q=lesley', ['Authorization' => "Bearer {$token}"]);

    expect($response->json('data'))->toHaveCount(0);
});

test('search excludes suspended and blocked users in either direction', function () {
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $suspended = User::create([
        'first_name' => 'Sammy', 'last_name' => 'Suspended',
        'email' => 'suspended@example.com', 'password' => 'supersecret', 'is_suspended' => true,
    ]);
    $blockedByMe = User::create(['first_name' => 'Sammy', 'last_name' => 'Blocked', 'email' => 'blocked1@example.com', 'password' => 'supersecret']);
    $blockedMe = User::create(['first_name' => 'Sammy', 'last_name' => 'Blocker', 'email' => 'blocked2@example.com', 'password' => 'supersecret']);
    $visible = User::create(['first_name' => 'Sammy', 'last_name' => 'Visible', 'email' => 'visible@example.com', 'password' => 'supersecret']);

    UserBlock::create(['blocker_id' => $me->id, 'blocked_id' => $blockedByMe->id]);
    UserBlock::create(['blocker_id' => $blockedMe->id, 'blocked_id' => $me->id]);

    $response = $this->getJson('/api/users/search?q=sammy', ['Authorization' => "Bearer {$token}"]);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$visible->id]);
});
