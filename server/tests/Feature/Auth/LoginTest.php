<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can log in with correct credentials and receives a token', function () {
    User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email'], 'token']);
});

test('login fails with an incorrect password', function () {
    User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'lesley@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'These credentials do not match our records.',
        'errorCode' => 'INVALID_CREDENTIALS',
    ]);
});

test('login fails for an email that does not exist', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'supersecret',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'These credentials do not match our records.',
        'errorCode' => 'INVALID_CREDENTIALS',
    ]);
});
