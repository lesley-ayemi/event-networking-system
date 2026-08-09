<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('an unauthenticated request to upload a cover image is rejected', function () {
    $event = Event::factory()->create();

    $response = $this->postJson("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $response->assertStatus(401);
});

test('an owner can upload a cover image for their event', function () {
    Storage::fake('public');

    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->post("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('data.cover_image'))->toContain('/storage/event-covers/');

    $storedPath = 'event-covers/'.basename($response->json('data.cover_image'));
    Storage::disk('public')->assertExists($storedPath);
});

test('a user cannot upload a cover image for an event they do not own', function () {
    Storage::fake('public');

    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => 'Person',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $otherUser = User::create([
        'first_name' => 'Other', 'last_name' => 'User',
        'email' => 'other@example.com', 'password' => 'supersecret',
    ]);
    $token = $otherUser->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->post("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    $response->assertStatus(403);
});

test('uploading a non-image file is rejected', function () {
    Storage::fake('public');

    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $response = $this->post("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->create('flyer.pdf', 100),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('cover_image');
});

test('uploading a new cover image removes the previous one', function () {
    Storage::fake('public');

    $owner = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'owner@example.com', 'password' => 'supersecret',
    ]);
    $token = $owner->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $firstResponse = $this->post("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('first.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);
    $firstPath = 'event-covers/'.basename($firstResponse->json('data.cover_image'));

    $this->post("/api/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('second.jpg'),
    ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json']);

    Storage::disk('public')->assertMissing($firstPath);
});
