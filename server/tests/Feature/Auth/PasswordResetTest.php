<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('forgot-password sends a reset notification for an existing account', function () {
    Notification::fake();

    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/forgot-password', ['email' => 'lesley@example.com']);

    $response->assertStatus(200);
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('forgot-password responds the same generic message for an unknown email, without sending anything', function () {
    Notification::fake();

    $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'If an account exists for that email, a password reset link is on its way.']);
    Notification::assertNothingSent();
});

test('a user can reset their password with a valid token', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'old-password',
    ]);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'lesley@example.com',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $response->assertStatus(200);

    $loginResponse = $this->postJson('/api/login', [
        'email' => 'lesley@example.com',
        'password' => 'brand-new-password',
    ]);
    $loginResponse->assertStatus(200);
});

test('resetting a password revokes existing tokens', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'old-password',
    ]);
    $user->createToken('api-token');
    expect($user->tokens()->count())->toBe(1);

    $token = Password::createToken($user);
    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'lesley@example.com',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    expect($user->tokens()->count())->toBe(0);
});

test('reset-password fails with an invalid token', function () {
    User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'old-password',
    ]);

    $response = $this->postJson('/api/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'lesley@example.com',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'errorCode' => 'INVALID_RESET_TOKEN']);
});

test('reset-password requires matching password confirmation', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'old-password',
    ]);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'lesley@example.com',
        'password' => 'brand-new-password',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'errorCode' => 'VALIDATION_ERROR']);
});
