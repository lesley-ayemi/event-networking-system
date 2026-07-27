<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can register and receives a token', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'supersecret',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email'], 'token']);
    $response->assertJsonMissingPath('user.password');

    expect(User::where('email', 'lesley@example.com')->exists())->toBeTrue();
});

test('registration fails with a mismatched password confirmation', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
});

test('registration fails with a duplicate email', function () {
    User::create([
        'first_name' => 'First',
        'last_name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/register', [
        'first_name' => 'Second',
        'last_name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'supersecret',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('registration fails with missing required fields', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});
