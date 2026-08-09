<?php

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventIntroductionNotification;
use App\Notifications\EventReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('sends a reminder to a registrant whose event starts in about a day and has reminders enabled', function () {
    Notification::fake();

    $event = Event::factory()->create(['starts_at' => now()->addHours(24)]);
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['event_reminders' => true, 'pre_event_introductions' => false],
    ]);
    $registration = $event->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');

    Notification::assertSentTo($user, EventReminderNotification::class);
    expect($registration->fresh()->reminder_sent_at)->not->toBeNull();
});

test('does not send a reminder when the registrant has reminders turned off, but still marks it processed', function () {
    Notification::fake();

    $event = Event::factory()->create(['starts_at' => now()->addHours(24)]);
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['event_reminders' => false, 'pre_event_introductions' => false],
    ]);
    $registration = $event->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');

    Notification::assertNothingSent();
    expect($registration->fresh()->reminder_sent_at)->not->toBeNull();
});

test('does not process registrations for events outside the reminder window', function () {
    Notification::fake();

    $tooSoon = Event::factory()->create(['starts_at' => now()->addHours(2)]);
    $tooFar = Event::factory()->create(['starts_at' => now()->addDays(10)]);
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['event_reminders' => true, 'pre_event_introductions' => false],
    ]);
    $soonReg = $tooSoon->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);
    $farReg = $tooFar->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');

    Notification::assertNothingSent();
    expect($soonReg->fresh()->reminder_sent_at)->toBeNull();
    expect($farReg->fresh()->reminder_sent_at)->toBeNull();
});

test('does not re-send a reminder on a later run once already processed', function () {
    Notification::fake();

    $event = Event::factory()->create(['starts_at' => now()->addHours(24)]);
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['event_reminders' => true, 'pre_event_introductions' => false],
    ]);
    $event->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => false, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');
    Artisan::call('events:send-notifications');

    Notification::assertSentToTimes($user, EventReminderNotification::class, 1);
});

test('sends an introduction to the best compatible, mutually opted-in match', function () {
    Notification::fake();

    $event = Event::factory()->create(['starts_at' => now()->addHours(24)]);
    $me = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'industry' => 'Technology',
        'comfort_settings' => ['event_reminders' => false, 'pre_event_introductions' => true],
    ]);
    $other = User::create([
        'first_name' => 'Sam', 'last_name' => 'Rivera',
        'email' => 'sam@example.com', 'password' => 'supersecret',
        'industry' => 'Technology',
        'comfort_settings' => ['event_reminders' => false, 'pre_event_introductions' => false],
    ]);
    $myRegistration = $event->registrations()->create([
        'user_id' => $me->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);
    $event->registrations()->create([
        'user_id' => $other->id, 'open_to_matching' => true, 'interaction_mode' => 'one_to_one',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');

    Notification::assertSentTo($me, EventIntroductionNotification::class, function ($notification) use ($other) {
        return $notification->match->id === $other->id;
    });
    expect($myRegistration->fresh()->introduction_sent_at)->not->toBeNull();
});

test('does not send an introduction when no suitable match exists, but still marks it processed', function () {
    Notification::fake();

    $event = Event::factory()->create(['starts_at' => now()->addHours(24)]);
    $user = User::create([
        'first_name' => 'Lesley', 'last_name' => 'Ayemi',
        'email' => 'lesley@example.com', 'password' => 'supersecret',
        'comfort_settings' => ['event_reminders' => false, 'pre_event_introductions' => true],
    ]);
    $registration = $event->registrations()->create([
        'user_id' => $user->id, 'open_to_matching' => true, 'interaction_mode' => 'either',
        'preferred_group_size' => 4, 'attendance_format' => 'virtual', 'message_before_event' => false,
    ]);

    Artisan::call('events:send-notifications');

    Notification::assertNothingSentTo($user);
    expect($registration->fresh()->introduction_sent_at)->not->toBeNull();
});
