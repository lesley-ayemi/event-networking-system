<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('nobody@example.com|127.0.0.1');
    RateLimiter::clear('lesley@example.com|127.0.0.1');
});

test('repeated login attempts for the same email are throttled after 5 per minute', function () {
    User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/login', ['email' => 'lesley@example.com', 'password' => 'wrong-password']);
        $response->assertStatus(401);
    }

    $response = $this->postJson('/api/login', ['email' => 'lesley@example.com', 'password' => 'wrong-password']);

    $response->assertStatus(429);
    $response->assertJsonPath('errorCode', 'TOO_MANY_REQUESTS');
});

test('repeated registration attempts are throttled after 5 per minute', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/register', [
            'first_name' => 'Test', 'last_name' => 'User',
            'email' => 'nobody@example.com', 'password' => 'short',
        ]);
    }

    $response = $this->postJson('/api/register', [
        'first_name' => 'Test', 'last_name' => 'User',
        'email' => 'nobody@example.com', 'password' => 'short',
    ]);

    $response->assertStatus(429);
});

test('repeated forgot-password requests for the same email are throttled after 5 per minute', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);
    }

    $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

    $response->assertStatus(429);
});

test('login throttling is scoped per email, not global', function () {
    User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', ['email' => 'lesley@example.com', 'password' => 'wrong-password']);
    }
    $throttled = $this->postJson('/api/login', ['email' => 'lesley@example.com', 'password' => 'wrong-password']);
    $throttled->assertStatus(429);

    $otherAccount = $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
    $otherAccount->assertStatus(401);
});

test('report submission is throttled after 10 per minute', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/reports', [
            'reportable_type' => 'user',
            'reportable_id' => $target->id,
            'reason' => 'spam',
        ], ['Authorization' => "Bearer {$token}"]);
    }

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'user',
        'reportable_id' => $target->id,
        'reason' => 'spam',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(429);
});
