<?php

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to view a profile is rejected', function () {
    $user = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->getJson("/api/users/{$user->id}");

    $response->assertStatus(401);
});

test('a user can view another user\'s public profile', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
        'bio' => 'Product designer who loves board games.',
        'job_title' => 'Product Designer',
        'industry' => 'Technology',
        'networking_goals' => 'Meet other designers',
        'availability_status' => 'available',
    ]);

    $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $target->id);
    $response->assertJsonPath('data.first_name', 'Sam');
    $response->assertJsonPath('data.bio', 'Product designer who loves board games.');
    $response->assertJsonPath('data.job_title', 'Product Designer');
    $response->assertJsonPath('data.availability_status', 'available');
});

test('a public profile never leaks the target\'s email or account-management fields', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $keys = array_keys($response->json('data'));
    expect($keys)->not->toContain('email');
    expect($keys)->not->toContain('is_admin');
    expect($keys)->not->toContain('is_suspended');
    expect($keys)->not->toContain('comfort_settings');
    expect($keys)->not->toContain('quiz_answers');
    expect($keys)->not->toContain('conversation_boundaries');
});

test('viewing a profile you have blocked returns not found', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    UserBlock::create(['blocker_id' => $viewer->id, 'blocked_id' => $target->id]);

    $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
    $response->assertJsonPath('errorCode', 'USER_NOT_FOUND');
});

test('viewing a profile of someone who has blocked you returns not found', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    UserBlock::create(['blocker_id' => $target->id, 'blocked_id' => $viewer->id]);

    $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});

test('a suspended user\'s profile is not viewable', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
        'is_suspended' => true,
    ]);

    $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});

test('viewing a profile for a user that does not exist returns not found', function () {
    $viewer = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $viewer->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/users/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});
