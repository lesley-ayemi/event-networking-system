<?php

use App\Models\Event;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\CompatibilityCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCompatibilityUser(string $email, array $quizAnswers, string $industry = 'Technology'): User
{
    return User::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => $email,
        'password' => 'supersecret',
        'industry' => $industry,
        'quiz_answers' => $quizAnswers,
    ]);
}

test('calculateCompatibility matches the hand-worked weighted score', function () {
    $event = Event::factory()->create();

    $userA = makeCompatibilityUser('a@example.com', [
        'messageBeforeMeeting' => 5,
        'structuredConversation' => 5,
        'responseSpeed' => 3,
        'oneToOnePreference' => 5,
        'networkingGoal' => 2,
    ]);
    $userB = makeCompatibilityUser('b@example.com', [
        'messageBeforeMeeting' => 5,
        'structuredConversation' => 4,
        'responseSpeed' => 3,
        'oneToOnePreference' => 4,
        'networkingGoal' => 2,
    ]);

    $regA = $event->registrations()->create([
        'user_id' => $userA->id,
        'interaction_mode' => 'one_to_one',
        'open_to_matching' => true,
        'message_before_event' => true,
        'preferred_group_size' => 4,
        'attendance_format' => 'virtual',
    ]);
    $regB = $event->registrations()->create([
        'user_id' => $userB->id,
        'interaction_mode' => 'one_to_one',
        'open_to_matching' => true,
        'message_before_event' => true,
        'preferred_group_size' => 4,
        'attendance_format' => 'virtual',
    ]);

    // Communication style: msg |5-5|=0->100, structured |5-4|=1->75, speed |3-3|=0->100; avg = 275/3 = 91.667
    // Interaction mode:     oneToOne |5-4|=1 -> 75
    // Networking goals:     2 === 2 -> 100
    // Industries:           Technology === Technology -> 100
    // Event preferences:    format match 100, message match 100, group size diff 0 -> 100; avg = 100
    // score = 91.667*0.35 + 75*0.25 + 100*0.20 + 100*0.10 + 100*0.10 = 90.833 -> rounds to 91
    expect(CompatibilityCalculator::calculateCompatibility($userA, $regA, $userB, $regB))->toBe(91);
});

test('isSuitable requires both users registered for the same event', function () {
    $eventOne = Event::factory()->create();
    $eventTwo = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $eventOne->registrations()->create(['user_id' => $userA->id, 'open_to_matching' => true]);
    $regB = $eventTwo->registrations()->create(['user_id' => $userB->id, 'open_to_matching' => true]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeFalse();
});

test('isSuitable requires both users to be open to matching', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $event->registrations()->create(['user_id' => $userA->id, 'open_to_matching' => true]);
    $regB = $event->registrations()->create(['user_id' => $userB->id, 'open_to_matching' => false]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeFalse();
});

test('isSuitable excludes users who blocked each other, in either direction', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $event->registrations()->create(['user_id' => $userA->id, 'open_to_matching' => true]);
    $regB = $event->registrations()->create(['user_id' => $userB->id, 'open_to_matching' => true]);

    UserBlock::create(['blocker_id' => $userB->id, 'blocked_id' => $userA->id]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeFalse();
});

test('isSuitable rejects incompatible interaction modes', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $event->registrations()->create([
        'user_id' => $userA->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
    ]);
    $regB = $event->registrations()->create([
        'user_id' => $userB->id, 'open_to_matching' => true, 'interaction_mode' => 'small_group',
    ]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeFalse();
});

test('isSuitable allows a specific mode against "either"', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $event->registrations()->create([
        'user_id' => $userA->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
    ]);
    $regB = $event->registrations()->create([
        'user_id' => $userB->id, 'open_to_matching' => true, 'interaction_mode' => 'either',
    ]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeTrue();
});

test('isSuitable rejects group sizes that are too far apart', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', []);
    $userB = makeCompatibilityUser('b@example.com', []);

    $regA = $event->registrations()->create(['user_id' => $userA->id, 'open_to_matching' => true, 'preferred_group_size' => 2]);
    $regB = $event->registrations()->create(['user_id' => $userB->id, 'open_to_matching' => true, 'preferred_group_size' => 10]);

    expect(CompatibilityCalculator::isSuitable($userA, $regA, $userB, $regB))->toBeFalse();
});

test('matchReasons explains shared interaction mode, messaging, and industry', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', ['structuredConversation' => 5, 'networkingGoal' => 1], 'Technology');
    $userB = makeCompatibilityUser('b@example.com', ['structuredConversation' => 5, 'networkingGoal' => 1], 'Technology');

    $regA = $event->registrations()->create([
        'user_id' => $userA->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'message_before_event' => true, 'attendance_format' => 'virtual', 'preferred_group_size' => 4,
    ]);
    $regB = $event->registrations()->create([
        'user_id' => $userB->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'message_before_event' => true, 'attendance_format' => 'virtual', 'preferred_group_size' => 4,
    ]);

    $reasons = CompatibilityCalculator::matchReasons($userA, $regA, $userB, $regB);

    expect($reasons)->toContain('Prefer one-to-one conversations');
    expect($reasons)->toContain('Like messaging before events');
    expect($reasons)->toContain('Work in Technology');
    expect($reasons)->toContain('Prefer structured conversations');
    expect($reasons)->toContain('Share the same networking goal');
    expect($reasons)->toContain('Both attending virtually');
    expect($reasons)->toContain('Want a similar group size');
});

test('matchReasons omits reasons for dimensions that do not match', function () {
    $event = Event::factory()->create();
    $userA = makeCompatibilityUser('a@example.com', ['structuredConversation' => 1, 'networkingGoal' => 1], 'Technology');
    $userB = makeCompatibilityUser('b@example.com', ['structuredConversation' => 1, 'networkingGoal' => 5], 'Finance');

    $regA = $event->registrations()->create([
        'user_id' => $userA->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'message_before_event' => false, 'attendance_format' => 'virtual', 'preferred_group_size' => 4,
    ]);
    $regB = $event->registrations()->create([
        'user_id' => $userB->id, 'open_to_matching' => true, 'interaction_mode' => 'small_group',
        'message_before_event' => false, 'attendance_format' => 'physical', 'preferred_group_size' => 4,
    ]);

    $reasons = CompatibilityCalculator::matchReasons($userA, $regA, $userB, $regB);

    expect($reasons)->not->toContain('Prefer one-to-one conversations');
    expect($reasons)->not->toContain('Like messaging before events');
    expect($reasons)->not->toContain('Work in Technology');
    expect($reasons)->not->toContain('Share the same networking goal');
    expect($reasons)->not->toContain('Both attending virtually');
});
