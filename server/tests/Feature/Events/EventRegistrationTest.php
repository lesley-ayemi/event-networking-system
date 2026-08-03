<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can register for an event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->postJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    expect($event->registrations()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('a user cannot register for the same event twice', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->registrations()->create(['user_id' => $user->id]);

    $response = $this->postJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

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

    $response = $this->postJson("/api/events/{$event->id}/register", [], ['Authorization' => "Bearer {$token}"]);

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
