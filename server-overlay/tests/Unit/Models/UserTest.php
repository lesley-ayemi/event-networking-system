<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a user applies interaction preference and comfort setting defaults', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    expect($user->onboarding_completed)->toBeFalse();
    expect($user->interaction_preferences)->toBe([
        'one_to_one' => true,
        'small_groups' => false,
        'virtual_interaction' => false,
        'text_communication' => true,
        'meet_before_event' => false,
        'observe_first' => false,
    ]);
    expect($user->comfort_settings)->toBe([
        'max_group_size' => 4,
        'allow_message_first' => true,
        'auto_matching' => true,
        'pre_event_introductions' => true,
        'event_reminders' => true,
    ]);
    expect($user->availability_status)->toBe('available');
    expect($user->conversation_boundaries)->toBe([
        'text_only' => false,
        'no_video_calls' => false,
        'one_message_at_a_time' => false,
        'event_only_meeting' => false,
        'no_spontaneous_calls' => false,
        'ask_before_groups' => false,
    ]);
});

test('the password is hashed automatically', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    expect($user->password)->not->toBe('supersecret');
    expect(password_verify('supersecret', $user->password))->toBeTrue();
});

test('the password and remember token are hidden from array/JSON output', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $array = $user->toArray();

    expect($array)->not->toHaveKey('password');
    expect($array)->not->toHaveKey('remember_token');
});
