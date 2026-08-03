<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('an unauthenticated request to upload a photo is rejected', function () {
    $response = $this->postJson('/api/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertStatus(401);
});

test('a user can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->post('/api/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('profile_image'))->toContain('/storage/profile-photos/');

    $storedPath = 'profile-photos/'.basename($response->json('profile_image'));
    Storage::disk('public')->assertExists($storedPath);
});

test('uploading a non-image file is rejected', function () {
    Storage::fake('public');

    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->post('/api/profile/photo', [
        'photo' => UploadedFile::fake()->create('resume.pdf', 100),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('photo');
});

test('uploading a new photo removes the previous one', function () {
    Storage::fake('public');

    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $firstResponse = $this->post('/api/profile/photo', [
        'photo' => UploadedFile::fake()->image('first.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);
    $firstPath = 'profile-photos/'.basename($firstResponse->json('profile_image'));

    $this->post('/api/profile/photo', [
        'photo' => UploadedFile::fake()->image('second.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    Storage::disk('public')->assertMissing($firstPath);
});
