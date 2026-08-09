<?php

namespace App\Console\Commands;

use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventIntroductionNotification;
use App\Notifications\EventReminderNotification;
use App\Services\CompatibilityCalculator;
use Illuminate\Console\Command;

// Scheduled hourly (see routes/console.php). Reminder and introduction are
// tracked independently per registration so a user who enables
// pre_event_introductions after their reminder already went out still gets
// an introduction on a later run, and vice versa.
class SendEventNotifications extends Command
{
    protected $signature = 'events:send-notifications';

    protected $description = 'Send event reminders and pre-event introductions to registrants whose event starts in about a day';

    private const WINDOW_START_HOURS = 23;

    private const WINDOW_END_HOURS = 25;

    public function handle(): int
    {
        $windowStart = now()->addHours(self::WINDOW_START_HOURS);
        $windowEnd = now()->addHours(self::WINDOW_END_HOURS);

        $registrations = EventRegistration::query()
            ->whereHas('event', fn ($query) => $query->whereBetween('starts_at', [$windowStart, $windowEnd]))
            ->where(function ($query) {
                $query->whereNull('reminder_sent_at')->orWhereNull('introduction_sent_at');
            })
            ->with(['event', 'user'])
            ->get();

        $remindersSent = 0;
        $introductionsSent = 0;

        foreach ($registrations as $registration) {
            $user = $registration->user;
            $event = $registration->event;

            if (is_null($registration->reminder_sent_at)) {
                if ($user->comfort_settings['event_reminders'] ?? false) {
                    $user->notify(new EventReminderNotification($event));
                    $remindersSent++;
                }
                $registration->forceFill(['reminder_sent_at' => now()])->save();
            }

            if (is_null($registration->introduction_sent_at)) {
                if ($registration->open_to_matching && ($user->comfort_settings['pre_event_introductions'] ?? false)) {
                    $match = $this->findBestMatch($user, $registration);
                    if ($match !== null) {
                        [$candidate, $reasons] = $match;
                        $user->notify(new EventIntroductionNotification($event, $candidate, $reasons));
                        $introductionsSent++;
                    }
                }
                $registration->forceFill(['introduction_sent_at' => now()])->save();
            }
        }

        $this->info("Sent {$remindersSent} reminder(s) and {$introductionsSent} introduction(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{0: User, 1: array<int, string>}|null
     */
    private function findBestMatch(User $user, EventRegistration $registration): ?array
    {
        $candidates = EventRegistration::query()
            ->where('event_id', $registration->event_id)
            ->where('user_id', '!=', $user->id)
            ->where('open_to_matching', true)
            ->with('user')
            ->get();

        $best = null;
        $bestScore = -1;

        foreach ($candidates as $candidateRegistration) {
            $candidate = $candidateRegistration->user;

            if (! CompatibilityCalculator::isSuitable($user, $registration, $candidate, $candidateRegistration)) {
                continue;
            }

            $score = CompatibilityCalculator::calculateCompatibility($user, $registration, $candidate, $candidateRegistration);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [$candidate, CompatibilityCalculator::matchReasons($user, $registration, $candidate, $candidateRegistration)];
            }
        }

        return $best;
    }
}
