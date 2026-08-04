<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
