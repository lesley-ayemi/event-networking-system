<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a non-admin cannot manage admin access', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Regular', 'last_name' => 'Person',
        'email' => 'regular@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson("/api/admin/admins/{$target->id}/promote", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('an admin can grant admin access to a user', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Regular', 'last_name' => 'Person',
        'email' => 'regular@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson("/api/admin/admins/{$target->id}/promote", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('is_admin', true);
    expect($target->fresh()->is_admin)->toBeTrue();
});

test('an admin cannot grant admin access to a user who already has it', function () {
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

    $response = $this->postJson("/api/admin/admins/{$otherAdmin->id}/promote", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
    $response->assertJsonPath('errorCode', 'ALREADY_ADMIN');
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
