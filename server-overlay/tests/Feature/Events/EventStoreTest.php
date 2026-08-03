<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to create an event is rejected', function () {
    $response = $this->postJson('/api/events', ['name' => 'Founders Mixer']);

    $response->assertStatus(401);
});

test('a user can create an event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/events', [
        'name' => 'Founders Mixer',
        'starts_at' => '2026-09-01 18:00:00',
        'is_virtual' => true,
        'industry' => 'Technology',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Founders Mixer');
    $response->assertJsonPath('data.created_by', $user->id);
});

test('creating an event requires a name and start date', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/events', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'starts_at']);
});
