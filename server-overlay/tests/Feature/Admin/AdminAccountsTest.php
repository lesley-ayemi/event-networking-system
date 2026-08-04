<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a non-admin cannot manage admin accounts', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/admin/admins', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('an admin can list admin accounts', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create([
        'first_name' => 'Regular', 'last_name' => 'Person',
        'email' => 'regular@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->getJson('/api/admin/admins', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.email', 'admin@example.com');
});

test('an admin can create a new admin account', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/admin/admins', [
        'first_name' => 'New', 'last_name' => 'Admin',
        'email' => 'new-admin@example.com',
        'password' => 'supersecret', 'password_confirmation' => 'supersecret',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('is_admin', true);
    expect(User::where('email', 'new-admin@example.com')->first()->is_admin)->toBeTrue();
});

test('an admin can update another admin\'s basic info', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $otherAdmin = User::create([
        'first_name' => 'Other', 'last_name' => 'Admin',
        'email' => 'other-admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);

    $response = $this->patchJson("/api/admin/admins/{$otherAdmin->id}", [
        'first_name' => 'Updated',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('first_name', 'Updated');
});

test('an admin can demote another admin', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $otherAdmin = User::create([
        'first_name' => 'Other', 'last_name' => 'Admin',
        'email' => 'other-admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);

    $response = $this->deleteJson("/api/admin/admins/{$otherAdmin->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('is_admin', false);
    expect($otherAdmin->fresh()->is_admin)->toBeFalse();
});

test('an admin cannot remove their own admin access', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;

    $response = $this->deleteJson("/api/admin/admins/{$admin->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'CANNOT_REMOVE_SELF');
});
