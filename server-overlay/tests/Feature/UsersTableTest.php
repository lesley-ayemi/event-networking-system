<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('the users table has the expected profile columns', function () {
    expect(Schema::hasColumns('users', [
        'first_name',
        'last_name',
        'bio',
        'job_title',
        'industry',
        'profile_image',
        'interaction_preferences',
        'quiz_answers',
        'compatibility_profile',
        'onboarding_completed',
    ]))->toBeTrue();

    expect(Schema::hasColumn('users', 'name'))->toBeFalse();
});
