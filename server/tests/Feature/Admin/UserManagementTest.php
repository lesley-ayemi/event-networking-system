<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can list all users', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);
    User::create(['first_name' => 'Ava', 'last_name' => 'Rivera', 'email' => 'ava@example.com', 'password' => 'supersecret']);

    $response = $this->getJson('/api/admin/users', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('meta.total'))->toBe(3);
});

test('admin can search users by name or email', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);
    User::create(['first_name' => 'Ava', 'last_name' => 'Rivera', 'email' => 'ava@example.com', 'password' => 'supersecret']);

    $response = $this->getJson('/api/admin/users?search=Ben', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $names = collect($response->json('data'))->pluck('first_name');
    expect($names)->toContain('Ben');
    expect($names)->not->toContain('Ava');
});

test('admin can filter users by suspended status', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera', 'email' => 'sam@example.com', 'password' => 'supersecret',
        'is_suspended' => true, 'suspended_at' => now(),
    ]);
    User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);

    $response = $this->getJson('/api/admin/users?status=suspended', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $names = collect($response->json('data'))->pluck('first_name');
    expect($names)->toEqual(collect(['Sam']));
});

test('admin can view a single user', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);

    $response = $this->getJson("/api/admin/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('email', 'ben@example.com');
});

test('admin can update a user\'s profile fields', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);

    $response = $this->patchJson("/api/admin/users/{$target->id}", [
        'job_title' => 'Staff Engineer',
        'industry' => 'Technology',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('job_title', 'Staff Engineer');
    expect(AuditLog::where('action', 'user.updated')->where('subject_id', $target->id)->exists())->toBeTrue();
});

test('admin can clear a user\'s optional text fields by submitting an empty string', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret',
        'bio' => 'Old bio', 'job_title' => 'Old title', 'industry' => 'Old industry',
    ]);

    $response = $this->patchJson("/api/admin/users/{$target->id}", [
        'bio' => '',
        'job_title' => '',
        'industry' => '',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('bio', '');
    $response->assertJsonPath('job_title', '');
    $response->assertJsonPath('industry', '');
});

test('admin cannot update a user to an email already taken', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    User::create(['first_name' => 'Ava', 'last_name' => 'Rivera', 'email' => 'ava@example.com', 'password' => 'supersecret']);
    $target = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);

    $response = $this->patchJson("/api/admin/users/{$target->id}", [
        'email' => 'ava@example.com',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
});

test('admin can delete a user, which soft-deletes them and revokes tokens', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);
    $target->createToken('api-token');

    $response = $this->deleteJson("/api/admin/users/{$target->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(204);
    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();
    expect($target->fresh()->tokens()->count())->toBe(0);
    expect(AuditLog::where('action', 'user.deleted')->where('subject_id', $target->id)->exists())->toBeTrue();
});

test('deleted users are excluded from the default index but included with status=deleted', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $target = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);
    $target->delete();

    $default = $this->getJson('/api/admin/users', ['Authorization' => "Bearer {$token}"]);
    $deletedOnly = $this->getJson('/api/admin/users?status=deleted', ['Authorization' => "Bearer {$token}"]);

    expect(collect($default->json('data'))->pluck('id'))->not->toContain($target->id);
    expect(collect($deletedOnly->json('data'))->pluck('id'))->toContain($target->id);
});

test('an admin cannot delete their own account', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;

    $response = $this->deleteJson("/api/admin/users/{$admin->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'CANNOT_DELETE_SELF');
    expect(User::find($admin->id))->not->toBeNull();
});

test('an admin cannot delete another admin without first removing their admin access', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $otherAdmin = User::create([
        'first_name' => 'Other', 'last_name' => 'Admin', 'email' => 'other-admin@example.com',
        'password' => 'supersecret', 'is_admin' => true,
    ]);

    $response = $this->deleteJson("/api/admin/users/{$otherAdmin->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'MUST_REMOVE_ADMIN_FIRST');
});

test('a non-admin cannot access user management endpoints', function () {
    $user = User::create(['first_name' => 'Ben', 'last_name' => 'Match', 'email' => 'ben@example.com', 'password' => 'supersecret']);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/admin/users', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});
