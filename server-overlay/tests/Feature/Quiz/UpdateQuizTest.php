<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validQuizAnswers(array $overrides = []): array
{
    return array_merge([
        'oneToOnePreference' => 5,
        'preferredGroupSize' => 2,
        'messageBeforeMeeting' => 5,
        'structuredConversation' => 4,
        'responseSpeed' => 3,
        'virtualPreference' => 4,
        'networkingGoal' => 2,
        'industryInterest' => 1,
        'observeFirstPreference' => 3,
        'conversationLengthPreference' => 3,
    ], $overrides);
}

test('an unauthenticated request to update quiz answers is rejected', function () {
    $response = $this->patchJson('/api/quiz', validQuizAnswers());

    $response->assertStatus(401);
});

test('a user can submit their compatibility quiz answers', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson('/api/quiz', validQuizAnswers(), ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(200);
    $response->assertJsonPath('quiz_answers.oneToOnePreference', 5);
    $response->assertJsonPath('quiz_answers.preferredGroupSize', 2);

    expect($user->fresh()->quiz_answers)->toBe(validQuizAnswers());
});

test('submitting the quiz again replaces the previous answers', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $this->patchJson('/api/quiz', validQuizAnswers(), ['Authorization' => "Bearer {$token}"]);
    $this->patchJson('/api/quiz', validQuizAnswers(['oneToOnePreference' => 1]), ['Authorization' => "Bearer {$token}"]);

    expect($user->fresh()->quiz_answers['oneToOnePreference'])->toBe(1);
});

test('quiz submission requires every question to be answered', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $answers = validQuizAnswers();
    unset($answers['responseSpeed']);

    $response = $this->patchJson('/api/quiz', $answers, ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('responseSpeed');
});

test('quiz answers must be within the 1 to 5 scale', function () {
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->patchJson(
        '/api/quiz',
        validQuizAnswers(['virtualPreference' => 6]),
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('virtualPreference');
});
