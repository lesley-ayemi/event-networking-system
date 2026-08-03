<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to update the profile is rejected', function () {
    $response = $this->patchJson('/api/profile', ['bio' => 'Hello']);

    $response->assertStatus(401);
});

test('a user can update their personal information', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/profile', [
        'job_title' => 'Product Designer',
        'industry' => 'Technology',
        'bio' => 'I like building things.',
        'networking_goals' => 'Meet other designers.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('job_title', 'Product Designer');
    $response->assertJsonPath('industry', 'Technology');
    $response->assertJsonPath('bio', 'I like building things.');
    $response->assertJsonPath('networking_goals', 'Meet other designers.');

    expect($user->fresh()->job_title)->toBe('Product Designer');
});

test('a user can leave optional profile fields blank', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    // Laravel's ConvertEmptyStringsToNull middleware turns "" into null
    // before validation runs, which used to fail the plain "string" rule.
    $response = $this->patchJson('/api/profile', [
        'job_title' => '',
        'industry' => '',
        'bio' => '',
        'networking_goals' => '',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('job_title', '');
    $response->assertJsonPath('industry', '');
});

test('a user can update communication preferences without losing other keys', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/profile', [
        'interaction_preferences' => [
            'small_groups' => true,
            'observe_first' => true,
        ],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $preferences = $response->json('interaction_preferences');

    expect($preferences)->toMatchArray([
        'one_to_one' => true,
        'small_groups' => true,
        'virtual_interaction' => false,
        'text_communication' => true,
        'meet_before_event' => false,
        'observe_first' => true,
    ]);
});

test('a user can update comfort settings without losing other keys', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/profile', [
        'comfort_settings' => [
            'max_group_size' => 8,
            'allow_message_first' => false,
        ],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $settings = $response->json('comfort_settings');

    expect($settings)->toMatchArray([
        'max_group_size' => 8,
        'allow_message_first' => false,
        'auto_matching' => true,
        'pre_event_introductions' => true,
        'event_reminders' => true,
    ]);
});

test('a user can mark onboarding as completed', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/profile', [
        'onboarding_completed' => true,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('onboarding_completed', true);
});

test('updating the profile rejects an invalid comfort setting', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/profile', [
        'comfort_settings' => ['max_group_size' => 1],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('comfort_settings.max_group_size');
});
