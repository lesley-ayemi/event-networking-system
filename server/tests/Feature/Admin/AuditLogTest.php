<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a non-admin cannot view audit logs', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/admin/audit-logs', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(403);
});

test('an admin can view the audit log, newest first', function () {
    $admin = User::create([
        'first_name' => 'Admina', 'last_name' => 'Strator',
        'email' => 'admin@example.com', 'password' => 'supersecret',
        'is_admin' => true,
    ]);
    $token = $admin->createToken('api-token')->plainTextToken;

    AuditLog::record($admin, 'user.suspended');
    AuditLog::record($admin, 'event.removed');

    $response = $this->getJson('/api/admin/audit-logs', ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.action', 'event.removed');
    $response->assertJsonPath('data.1.action', 'user.suspended');
    $response->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});
