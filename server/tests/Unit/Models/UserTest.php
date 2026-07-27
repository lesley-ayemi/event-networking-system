<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a user applies interaction preference defaults', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    expect($user->onboarding_completed)->toBeFalse();
    expect($user->interaction_preferences)->toBe([
        'preferred_mode' => 'one-to-one',
        'preferred_group_size' => 2,
        'virtual_preferred' => false,
        'message_before_event' => true,
        'allow_match_requests' => true,
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
