<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can list only the events they registered for', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $registeredEvent = Event::factory()->create(['name' => 'Registered Event']);
    $registeredEvent->registrations()->create(['user_id' => $user->id]);
    Event::factory()->create(['name' => 'Unregistered Event']);

    $response = $this->getJson('/api/users/me/events', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.name', 'Registered Event');
    $response->assertJsonPath('data.0.is_registered', true);
});
