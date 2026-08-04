<?php

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
