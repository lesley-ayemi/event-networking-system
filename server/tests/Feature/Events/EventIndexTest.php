<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to list events is rejected', function () {
    $response = $this->getJson('/api/events');

    $response->assertStatus(401);
});

test('a user can list events', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->count(3)->create();

    $response = $this->getJson('/api/events', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

test('events can be filtered by a name search', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['name' => 'Founders Mixer']);
    Event::factory()->create(['name' => 'Design Meetup']);

    $response = $this->getJson('/api/events?search=Founders', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Founders Mixer');
});

test('events can be filtered by date', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['starts_at' => '2026-08-15 10:00:00']);
    Event::factory()->create(['starts_at' => '2026-09-01 10:00:00']);

    $response = $this->getJson('/api/events?date=2026-08-15', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered by industry', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['industry' => 'Technology']);
    Event::factory()->create(['industry' => 'Finance']);

    $response = $this->getJson('/api/events?industry=Technology', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.industry'))->toBe('Technology');
});

test('events can be filtered by location', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['location' => 'San Francisco', 'is_virtual' => false]);
    Event::factory()->create(['location' => 'New York', 'is_virtual' => false]);

    $response = $this->getJson('/api/events?location=Francisco', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered by virtual or physical format', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['is_virtual' => true]);
    Event::factory()->create(['is_virtual' => false]);

    $response = $this->getJson('/api/events?format=virtual', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.is_virtual'))->toBeTrue();
});

test('events can be filtered by one-to-one availability', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['one_to_one_available' => true]);
    Event::factory()->create(['one_to_one_available' => false]);

    $response = $this->getJson('/api/events?one_to_one=1', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered by small-group availability', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['small_group_available' => true]);
    Event::factory()->create(['small_group_available' => false]);

    $response = $this->getJson('/api/events?small_group=1', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered by free or paid', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['is_free' => true, 'price' => null]);
    Event::factory()->create(['is_free' => false, 'price' => 25]);

    $response = $this->getJson('/api/events?price=free', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.is_free'))->toBeTrue();
});

test('events can be filtered by accessibility options', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['accessibility_options' => ['wheelchair_accessible']]);
    Event::factory()->create(['accessibility_options' => ['captioning']]);

    $response = $this->getJson('/api/events?accessibility[]=wheelchair_accessible', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered to only those created by the current user', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    Event::factory()->create(['created_by' => $user->id]);
    Event::factory()->create();

    $response = $this->getJson('/api/events?mine=1', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.created_by'))->toBe($user->id);
});

test('each event reports whether the current user is registered', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();
    $event->registrations()->create(['user_id' => $user->id]);
    Event::factory()->create();

    $response = $this->getJson('/api/events', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $registered = collect($response->json('data'))->firstWhere('id', $event->id);
    expect($registered['is_registered'])->toBeTrue();
    expect($registered['attendees_count'])->toBe(1);
});
