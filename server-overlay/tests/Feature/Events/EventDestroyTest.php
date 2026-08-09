<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an owner can delete their event', function () {
    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->deleteJson("/api/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(204);
    expect(Event::find($event->id))->toBeNull();
});

test('deleting an event soft-deletes it rather than removing the row', function () {
    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);
    $registrant = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $registration = $event->registrations()->create([
        'user_id' => $registrant->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    $this->deleteJson("/api/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $trashed = Event::withTrashed()->find($event->id);
    expect($trashed)->not->toBeNull();
    expect($trashed->deleted_at)->not->toBeNull();
    expect($trashed->name)->toBe($event->name);

    // The registration referencing this event survives too, since a soft
    // delete never issues the SQL DELETE that would trigger cascadeOnDelete.
    expect(\App\Models\EventRegistration::find($registration->id))->not->toBeNull();
});

test('a user cannot delete an event they do not own', function () {
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'User',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $otherUser = User::create([
        'first_name' => 'Other', 'last_name' => 'User',
        'email' => 'other@example.com', 'password' => 'supersecret',
    ]);
    $token = $otherUser->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->deleteJson("/api/events/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
    expect(Event::find($event->id))->not->toBeNull();
});
