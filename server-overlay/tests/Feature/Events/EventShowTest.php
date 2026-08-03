<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can view a single event', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['name' => 'Design Mixer']);

    $response = $this->getJson("/api/events/{$event->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Design Mixer');
    $response->assertJsonPath('data.is_registered', false);
});

test('viewing a nonexistent event returns 404', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/events/999', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
});
