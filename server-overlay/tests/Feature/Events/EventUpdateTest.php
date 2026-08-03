<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an owner can update their event', function () {
    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id, 'name' => 'Old Name']);

    $response = $this->patchJson("/api/events/{$event->id}", [
        'name' => 'New Name',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'New Name');
});

test('a user cannot update an event they do not own', function () {
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'User',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $otherUser = User::create([
        'first_name' => 'Other', 'last_name' => 'User',
        'email' => 'other@example.com', 'password' => 'supersecret',
    ]);
    $token = $otherUser->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id, 'name' => 'Old Name']);

    $response = $this->patchJson("/api/events/{$event->id}", [
        'name' => 'Hijacked Name',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
    expect($event->fresh()->name)->toBe('Old Name');
});
