<?php

use App\Models\Conversation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a non-admin cannot list reports', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/admin/reports', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
    $response->assertJsonPath('errorCode', 'FORBIDDEN');
});

test('an admin can list and filter reports', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $target = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
    ]);

    Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'user', 'reportable_id' => $target->id,
        'reason' => 'harassment',
    ]);
    Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'event', 'reportable_id' => 1,
        'reason' => 'false_event_information',
    ]);

    $response = $this->getJson('/api/admin/reports?type=user', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.reportable_type', 'user');
    $response->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

test('an admin can mark a report as reviewed, recording who and when', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $report = Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'user', 'reportable_id' => $reporter->id,
        'reason' => 'spam',
    ]);

    $response = $this->patchJson("/api/admin/reports/{$report->id}", [
        'status' => 'dismissed',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'dismissed');
    $response->assertJsonPath('reviewed_by', $admin->id);
    expect($report->fresh()->reviewed_at)->not->toBeNull();
});

test('an admin can view the surrounding conversation context for a reported message', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $alice = User::create(['first_name' => 'Alice', 'last_name' => 'A', 'email' => 'alice@example.com', 'password' => 'supersecret']);
    $bob = User::create(['first_name' => 'Bob', 'last_name' => 'B', 'email' => 'bob@example.com', 'password' => 'supersecret']);

    $conversation = Conversation::create();
    $conversation->messages()->create(['sender_id' => $alice->id, 'body' => 'Hi Bob']);
    $conversation->messages()->create(['sender_id' => $bob->id, 'body' => 'Hey Alice']);
    $flagged = $conversation->messages()->create(['sender_id' => $bob->id, 'body' => 'Something reportable']);
    $conversation->messages()->create(['sender_id' => $alice->id, 'body' => 'That was rude']);

    $report = Report::create([
        'reporter_id' => $alice->id, 'reportable_type' => 'message', 'reportable_id' => $flagged->id,
        'reason' => 'harassment',
    ]);

    $response = $this->getJson("/api/admin/reports/{$report->id}/context", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $messages = collect($response->json('data'));
    expect($messages)->toHaveCount(4);
    expect($messages->pluck('body'))->toEqual(collect(['Hi Bob', 'Hey Alice', 'Something reportable', 'That was rude']));
    $flaggedInResponse = $messages->firstWhere('id', $flagged->id);
    expect($flaggedInResponse['is_flagged'])->toBeTrue();
    expect($messages->firstWhere('id', '!=', $flagged->id)['is_flagged'])->toBeFalse();
});

test('viewing message context is rejected for a report that is not about a message', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $reporter = User::create(['first_name' => 'Lesley', 'last_name' => 'Ayemi', 'email' => 'lesley@example.com', 'password' => 'supersecret']);
    $report = Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'user', 'reportable_id' => $reporter->id,
        'reason' => 'spam',
    ]);

    $response = $this->getJson("/api/admin/reports/{$report->id}/context", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonPath('errorCode', 'REPORT_NOT_A_MESSAGE');
});

test('a non-admin cannot view reported message context', function () {
    $user = User::create(['first_name' => 'Lesley', 'last_name' => 'Ayemi', 'email' => 'lesley@example.com', 'password' => 'supersecret']);
    $token = $user->createToken('api-token')->plainTextToken;
    $conversation = Conversation::create();
    $message = $conversation->messages()->create(['sender_id' => $user->id, 'body' => 'Hi']);
    $report = Report::create([
        'reporter_id' => $user->id, 'reportable_type' => 'message', 'reportable_id' => $message->id,
        'reason' => 'spam',
    ]);

    $response = $this->getJson("/api/admin/reports/{$report->id}/context", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('viewing message context writes an audit log entry', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret', 'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $reporter = User::create(['first_name' => 'Lesley', 'last_name' => 'Ayemi', 'email' => 'lesley@example.com', 'password' => 'supersecret']);
    $conversation = Conversation::create();
    $message = $conversation->messages()->create(['sender_id' => $reporter->id, 'body' => 'Hi']);
    $report = Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'message', 'reportable_id' => $message->id,
        'reason' => 'spam',
    ]);

    $this->getJson("/api/admin/reports/{$report->id}/context", ['Authorization' => "Bearer {$token}"]);

    expect(\App\Models\AuditLog::where('action', 'report.viewed_context')->where('subject_id', $report->id)->exists())->toBeTrue();
});

test('updating a report writes an audit log entry', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;
    $reporter = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $report = Report::create([
        'reporter_id' => $reporter->id, 'reportable_type' => 'user', 'reportable_id' => $reporter->id,
        'reason' => 'spam',
    ]);

    $this->patchJson("/api/admin/reports/{$report->id}", ['status' => 'actioned'], ['Authorization' => "Bearer {$token}"]);

    expect(\App\Models\AuditLog::where('action', 'report.updated')->where('subject_id', $report->id)->exists())->toBeTrue();
});
