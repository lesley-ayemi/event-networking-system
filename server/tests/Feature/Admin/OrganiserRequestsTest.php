<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an admin can list pending organiser requests', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create([
        'first_name' => 'Pending', 'last_name' => 'Person',
        'email' => 'pending@example.com', 'password' => 'supersecret',
        'organiser_status' => 'pending', 'organiser_requested_at' => now(),
    ]);
    User::create([
        'first_name' => 'Approved', 'last_name' => 'Person',
        'email' => 'approved@example.com', 'password' => 'supersecret',
        'organiser_status' => 'approved',
    ]);

    $response = $this->getJson('/api/admin/organiser-requests', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.email', 'pending@example.com');
});

test('an admin can approve a pending organiser request', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $applicant = User::create([
        'first_name' => 'Pending', 'last_name' => 'Person',
        'email' => 'pending@example.com', 'password' => 'supersecret',
        'organiser_status' => 'pending', 'organiser_requested_at' => now(),
    ]);

    $response = $this->postJson("/api/admin/organiser-requests/{$applicant->id}/approve", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('organiser_status', 'approved');
});

test('an admin can reject a pending organiser request', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $applicant = User::create([
        'first_name' => 'Pending', 'last_name' => 'Person',
        'email' => 'pending@example.com', 'password' => 'supersecret',
        'organiser_status' => 'pending', 'organiser_requested_at' => now(),
    ]);

    $response = $this->postJson("/api/admin/organiser-requests/{$applicant->id}/reject", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('organiser_status', 'rejected');
});

test('approving a request that is not pending is rejected', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $user = User::create([
        'first_name' => 'Regular', 'last_name' => 'Person',
        'email' => 'regular@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson("/api/admin/organiser-requests/{$user->id}/approve", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
    $response->assertJsonPath('errorCode', 'ORGANISER_REQUEST_NOT_PENDING');
});
