<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to list matches is rejected', function () {
    $response = $this->getJson('/api/matches');

    $response->assertStatus(401);
});

test('a user sees a compatible, mutually opted-in registrant of the same event as a match', function () {
    $event = Event::factory()->create();

    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'industry' => 'Technology',
        'quiz_answers' => ['networkingGoal' => 1],
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $other = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
        'industry' => 'Technology',
        'quiz_answers' => ['networkingGoal' => 1],
    ]);

    $event->registrations()->create([
        'user_id' => $me->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => true,
    ]);
    $event->registrations()->create([
        'user_id' => $other->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => true,
    ]);

    $response = $this->getJson('/api/matches', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.user.id', $other->id);
    $response->assertJsonPath('data.0.event.id', $event->id);
    expect($response->json('data.0.score'))->toBeInt();
    expect($response->json('data.0.reasons'))->toContain('Prefer one-to-one conversations');
});

test('a user does not see themselves, or registrants who are not open to matching', function () {
    $event = Event::factory()->create();

    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $notOptedIn = User::create([
        'first_name' => 'Closed', 'last_name' => 'Off',
        'email' => 'closed@example.com', 'password' => 'supersecret',
    ]);

    $event->registrations()->create(['user_id' => $me->id, 'open_to_matching' => true]);
    $event->registrations()->create(['user_id' => $notOptedIn->id, 'open_to_matching' => false]);

    $response = $this->getJson('/api/matches', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(0);
});

test('matches are sorted by score descending', function () {
    $event = Event::factory()->create();

    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'quiz_answers' => ['networkingGoal' => 1, 'oneToOnePreference' => 5],
    ]);
    $token = $me->createToken('api-token')->plainTextToken;

    $closeMatch = User::create([
        'first_name' => 'Close', 'last_name' => 'Match',
        'email' => 'close@example.com', 'password' => 'supersecret',
        'quiz_answers' => ['networkingGoal' => 1, 'oneToOnePreference' => 5],
    ]);
    $farMatch = User::create([
        'first_name' => 'Far', 'last_name' => 'Match',
        'email' => 'far@example.com', 'password' => 'supersecret',
        'quiz_answers' => ['networkingGoal' => 5, 'oneToOnePreference' => 1],
    ]);

    $event->registrations()->create(['user_id' => $me->id, 'open_to_matching' => true]);
    $event->registrations()->create(['user_id' => $closeMatch->id, 'open_to_matching' => true]);
    $event->registrations()->create(['user_id' => $farMatch->id, 'open_to_matching' => true]);

    $response = $this->getJson('/api/matches', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $scores = collect($response->json('data'))->pluck('score')->all();
    expect($scores)->toBe(collect($scores)->sortDesc()->values()->all());
    $response->assertJsonPath('data.0.user.id', $closeMatch->id);
});
