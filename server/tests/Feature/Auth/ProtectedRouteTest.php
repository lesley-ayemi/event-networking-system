<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to /api/user is rejected', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

test('an authenticated request to /api/user returns the current user', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('email', 'lesley@example.com');
});
