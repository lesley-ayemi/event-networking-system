<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\Concerns\AttachesEventUserContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    use AttachesEventUserContext;

    public function index(Request $request)
    {
        $events = $request->user()
            ->bookmarkedEvents()
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->get();

        $this->attachUserContext($events, $request->user());

        return EventResource::collection($events);
    }

    public function store(Request $request, Event $event)
    {
        $user = $request->user();

        if ($event->bookmarks()->where('user_id', $user->id)->exists()) {
            throw new ApiException('You have already saved this event.', 'EVENT_ALREADY_SAVED', 409);
        }

        $event->bookmarks()->create(['user_id' => $user->id]);

        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $user);

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Event $event)
    {
        $deleted = $event->bookmarks()->where('user_id', $request->user()->id)->delete();

        if (! $deleted) {
            throw new ApiException('You have not saved this event.', 'BOOKMARK_NOT_FOUND', 404);
        }

        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return new EventResource($event);
    }
}
