<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can request organiser status', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/organiser-requests', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('organiser_status', 'pending');
    expect($user->fresh()->organiser_requested_at)->not->toBeNull();
});

test('a user cannot request organiser status twice while pending', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'organiser_status' => 'pending',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/organiser-requests', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
    $response->assertJsonPath('errorCode', 'ORGANISER_REQUEST_PENDING');
});

test('an already-approved organiser cannot request again', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'organiser_status' => 'approved',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/organiser-requests', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
    $response->assertJsonPath('errorCode', 'ALREADY_APPROVED_ORGANISER');
});
