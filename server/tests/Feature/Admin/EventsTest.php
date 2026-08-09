<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an admin can update any event regardless of ownership', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'Person',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $event = Event::factory()->create(['created_by' => $owner->id, 'name' => 'Original name']);

    $response = $this->patchJson("/api/admin/events/{$event->id}", [
        'name' => 'Updated by admin',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Updated by admin');
    expect($event->fresh()->name)->toBe('Updated by admin');
});

test('updating an event as admin writes an audit log entry', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $this->patchJson("/api/admin/events/{$event->id}", [
        'name' => 'Updated by admin',
    ], ['Authorization' => "Bearer {$token}"]);

    expect(\App\Models\AuditLog::where('action', 'event.updated')->where('subject_id', $event->id)->exists())->toBeTrue();
});

test('a non-admin cannot use the admin event update endpoint', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $user->id]);

    $response = $this->patchJson("/api/admin/events/{$event->id}", [
        'name' => 'Should not apply',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('an admin can remove any event regardless of ownership', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'Person',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->deleteJson("/api/admin/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(204);
    expect(Event::find($event->id))->toBeNull();
});

test('admin removal soft-deletes the event, preserving the row and its registrations', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'Person',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $this->deleteJson("/api/admin/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $trashed = Event::withTrashed()->find($event->id);
    expect($trashed)->not->toBeNull();
    expect($trashed->deleted_at)->not->toBeNull();
});

test('a non-admin cannot use the admin event removal endpoint', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $user->id]);

    $response = $this->deleteJson("/api/admin/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('removing an event writes an audit log entry', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $this->deleteJson("/api/admin/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    expect(\App\Models\AuditLog::where('action', 'event.removed')->where('subject_id', $event->id)->exists())->toBeTrue();
});
