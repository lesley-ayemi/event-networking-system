<?php

use App\Models\Conversation;
use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to file a report is rejected', function () {
    $response = $this->postJson('/api/reports', ['reportable_type' => 'user', 'reportable_id' => 1, 'reason' => 'spam']);

    $response->assertStatus(401);
});

test('a user can report another user account', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'user',
        'reportable_id' => $target->id,
        'reason' => 'impersonation',
        'details' => 'Using my photos.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('reportable_type', 'user');
    $response->assertJsonPath('reportable_id', $target->id);
    $response->assertJsonPath('reason', 'impersonation');
    $response->assertJsonPath('status', 'pending');
    expect(Report::where('reporter_id', $reporter->id)->count())->toBe(1);
});

test('a user can report a message', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;
    $other = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);
    $conversation = Conversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $reporter->id],
        ['user_id' => $other->id],
    ]);
    $message = $conversation->messages()->create(['sender_id' => $other->id, 'body' => 'Hey there']);

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'message',
        'reportable_id' => $message->id,
        'reason' => 'inappropriate_messages',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('reportable_type', 'message');
});

test('a user can report an event', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;
    $event = Event::factory()->create();

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'event',
        'reportable_id' => $event->id,
        'reason' => 'false_event_information',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(201);
    $response->assertJsonPath('reportable_type', 'event');
});

test('a user cannot report themselves', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'user',
        'reportable_id' => $reporter->id,
        'reason' => 'harassment',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'CANNOT_REPORT_SELF');
});

test('the reason must be one of the fixed taxonomy values', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'user',
        'reportable_id' => $target->id,
        'reason' => 'i-dont-like-them',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'VALIDATION_ERROR');
});

test('reporting a target that does not exist is rejected', function () {
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $reporter->createToken('api-token')->plainTextToken;

    $response = $this->postJson('/api/reports', [
        'reportable_type' => 'user',
        'reportable_id' => 999999,
        'reason' => 'spam',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(404);
    $response->assertJsonPath('errorCode', 'REPORT_TARGET_NOT_FOUND');
});
