<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a validation failure renders the standard error envelope with field errors', function () {
    $response = $this->postJson('/api/register', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'errorCode' => 'VALIDATION_ERROR',
    ]);
    $response->assertJsonStructure(['success', 'message', 'errorCode', 'errors']);
});

test('an unauthenticated request renders the standard error envelope', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'Your session has expired. Please log in again.',
        'errorCode' => 'UNAUTHENTICATED',
    ]);
});

test('a missing model renders a model-specific not-found error code', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/events/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'errorCode' => 'EVENT_NOT_FOUND',
    ]);
});

test('a named business-rule failure renders its specific error code', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->registrations()->create(['user_id' => $user->id]);

    $response = $this->postJson("/api/events/{$event->id}/register", [
        'interaction_mode' => 'one_to_one',
        'open_to_matching' => true,
        'message_before_event' => false,
        'preferred_group_size' => 4,
        'attendance_format' => 'physical',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'You are already registered for this event.',
        'errorCode' => 'EVENT_ALREADY_REGISTERED',
    ]);
});

test('an abort_if with no custom message still renders a calm default message', function () {
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'Person',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $outsider = User::create([
        'first_name' => 'Out', 'last_name' => 'Sider',
        'email' => 'outsider@example.com', 'password' => 'supersecret',
    ]);
    $outsiderToken = $outsider->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->deleteJson("/api/events/{$event->id}", [], ['Authorization' => "Bearer {$outsiderToken}"]);

    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
        'message' => 'You can only delete events you created.',
        'errorCode' => 'FORBIDDEN',
    ]);
});
