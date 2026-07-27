<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('logout revokes the current token', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $logoutResponse = $this->postJson('/api/logout', [], [
        'Authorization' => "Bearer {$token}",
    ]);
    $logoutResponse->assertStatus(200);

    // The sanctum guard memoizes its resolved user on first use within a
    // test, so a second simulated request needs a fresh guard to actually
    // re-check the token against the database.
    Auth::forgetGuards();

    $followUpResponse = $this->getJson('/api/user', [
        'Authorization' => "Bearer {$token}",
    ]);
    $followUpResponse->assertStatus(401);
});
