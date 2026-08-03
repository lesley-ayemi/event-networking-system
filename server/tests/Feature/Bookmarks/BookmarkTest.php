<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to save an event is rejected', function () {
    $event = Event::factory()->create();

    $response = $this->postJson("/api/bookmarks/{$event->id}");

    $response->assertStatus(401);
});

test('a user can save an event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->postJson("/api/bookmarks/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.is_bookmarked', true);
    expect($event->bookmarks()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('a user cannot save the same event twice', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->bookmarks()->create(['user_id' => $user->id]);

    $response = $this->postJson("/api/bookmarks/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409);
});

test('a user can remove a saved event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->bookmarks()->create(['user_id' => $user->id]);

    $response = $this->deleteJson("/api/bookmarks/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.is_bookmarked', false);
    expect($event->bookmarks()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('removing a saved event that was never saved returns 404', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->deleteJson("/api/bookmarks/{$event->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});

test('a user can list only the events they have saved', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $savedEvent = Event::factory()->create(['name' => 'Saved Event']);
    $savedEvent->bookmarks()->create(['user_id' => $user->id]);
    Event::factory()->create(['name' => 'Unsaved Event']);

    $response = $this->getJson('/api/bookmarks', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.name', 'Saved Event');
    $response->assertJsonPath('data.0.is_bookmarked', true);
});

test('registering for an event does not affect whether it is bookmarked, and vice versa', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->bookmarks()->create(['user_id' => $user->id]);

    $response = $this->postJson("/api/events/{$event->id}/register", [
        'interaction_mode' => 'either',
        'open_to_matching' => true,
        'message_before_event' => false,
        'preferred_group_size' => 4,
        'attendance_format' => 'virtual',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.is_registered', true);
    $response->assertJsonPath('data.is_bookmarked', true);
});
