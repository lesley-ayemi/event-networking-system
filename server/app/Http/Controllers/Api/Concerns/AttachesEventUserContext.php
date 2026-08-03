<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Bookmark;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Collection;

trait AttachesEventUserContext
{
    /**
     * Attaches is_registered/my_registration/is_bookmarked to each event for
     * the given user. Used both for listings and after single-event mutations
     * (register, cancel, bookmark, unbookmark) so every EventResource response
     * reflects the user's full current state, not just the field that changed.
     */
    private function attachUserContext(Collection $events, User $user): void
    {
        $eventIds = $events->pluck('id');

        $registrationsByEventId = EventRegistration::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->get()
            ->keyBy('event_id');

        $bookmarkedEventIds = Bookmark::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->pluck('event_id')
            ->all();

        $events->each(function (Event $event) use ($registrationsByEventId, $bookmarkedEventIds) {
            $registration = $registrationsByEventId->get($event->id);
            $event->is_registered = (bool) $registration;
            $event->my_registration = $registration;
            $event->is_bookmarked = in_array($event->id, $bookmarkedEventIds, true);
        });
    }
}
