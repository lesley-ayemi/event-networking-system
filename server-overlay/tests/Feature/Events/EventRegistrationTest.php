<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validRegistrationAnswers(array $overrides = []): array
{
    return array_merge([
        'interaction_mode' => 'one_to_one',
        'open_to_matching' => true,
        'message_before_event' => true,
        'preferred_group_size' => 4,
        'attendance_format' => 'virtual',
    ], $overrides);
}

test('registering requires an answer to every matching question', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->postJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'interaction_mode', 'open_to_matching', 'message_before_event', 'preferred_group_size', 'attendance_format',
    ]);
});

test('a user can register for an event with their matching preferences', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->postJson(
        "/api/events/{$event->id}/register",
        validRegistrationAnswers(['interaction_mode' => 'small_group', 'preferred_group_size' => 6]),
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(201);
    $response->assertJsonPath('data.is_registered', true);
    $response->assertJsonPath('data.attendees_count', 1);
    $response->assertJsonPath('data.my_registration.interaction_mode', 'small_group');
    $response->assertJsonPath('data.my_registration.preferred_group_size', 6);
    $response->assertJsonPath('data.my_registration.open_to_matching', true);
    $response->assertJsonPath('data.my_registration.attendance_format', 'virtual');

    $registration = $event->registrations()->where('user_id', $user->id)->first();
    expect($registration->interaction_mode)->toBe('small_group');
    expect($registration->preferred_group_size)->toBe(6);
});

test('a user cannot register for the same event twice', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->registrations()->create(['user_id' => $user->id]);

    $response = $this->postJson(
        "/api/events/{$event->id}/register",
        validRegistrationAnswers(),
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(409);
});

test('a user cannot register for a full event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['capacity' => 1]);
    $otherUser = User::create([
        'first_name' => 'Other', 'last_name' => 'User',
        'email' => 'other@example.com', 'password' => 'supersecret',
    ]);
    $event->registrations()->create(['user_id' => $otherUser->id]);

    $response = $this->postJson(
        "/api/events/{$event->id}/register",
        validRegistrationAnswers(),
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(422);
});

test('a user can cancel their registration', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->registrations()->create(['user_id' => $user->id]);

    $response = $this->deleteJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.is_registered', false);
    $response->assertJsonPath('data.my_registration', null);
    expect($event->registrations()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('cancelling a registration that does not exist returns 404', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->deleteJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});
