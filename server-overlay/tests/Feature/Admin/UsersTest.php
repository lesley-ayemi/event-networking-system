<?php

use App\Models\Report;
use App\Models\User;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\AccountUnsuspendedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('flagged accounts surfaces users with several reports against them', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $flagged = User::create([
        'first_name' => 'Flag', 'last_name' => 'Ged',
        'email' => 'flagged@example.com', 'password' => 'supersecret',
    ]);
    $quiet = User::create([
        'first_name' => 'Quiet', 'last_name' => 'One',
        'email' => 'quiet@example.com', 'password' => 'supersecret',
    ]);

    foreach (range(1, 3) as $i) {
        Report::create([
            'reporter_id' => $admin->id, 'reportable_type' => 'user', 'reportable_id' => $flagged->id,
            'reason' => 'harassment',
        ]);
    }
    Report::create([
        'reporter_id' => $admin->id, 'reportable_type' => 'user', 'reportable_id' => $quiet->id,
        'reason' => 'spam',
    ]);

    $response = $this->getJson('/api/admin/flagged-accounts', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($flagged->id);
    expect($ids)->not->toContain($quiet->id);
});

test('an admin can suspend a user, which revokes their active tokens', function () {
    Notification::fake();

    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $adminToken = $admin->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $targetToken = $target->createToken('api-token')->plainTextToken;

    $response = $this->postJson("/api/admin/users/{$target->id}/suspend", [], ['Authorization' => "Bearer {$adminToken}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('is_suspended', true);
    expect($target->fresh()->tokens()->count())->toBe(0);
    Notification::assertSentTo($target, AccountSuspendedNotification::class);

    Auth::forgetGuards();
    $blockedResponse = $this->getJson('/api/user', ['Authorization' => "Bearer {$targetToken}"]);
    $blockedResponse->assertStatus(401);
});

test('a suspended user is locked out of the app with a calm error', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $adminToken = $admin->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $this->postJson("/api/admin/users/{$target->id}/suspend", [], ['Authorization' => "Bearer {$adminToken}"]);

    Auth::forgetGuards();
    $newToken = $target->fresh()->createToken('api-token')->plainTextToken;
    $response = $this->getJson('/api/user', ['Authorization' => "Bearer {$newToken}"]);

    $response->assertStatus(403);
    $response->assertJsonPath('errorCode', 'ACCOUNT_SUSPENDED');
});

test('an admin can unsuspend a user', function () {
    Notification::fake();

    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
        'is_suspended' => true,
        'suspended_at' => now(),
    ]);

    $response = $this->postJson("/api/admin/users/{$target->id}/unsuspend", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('is_suspended', false);
    Notification::assertSentTo($target, AccountUnsuspendedNotification::class);
});
