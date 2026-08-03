<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventRegistrationRequest;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query()->withCount('registrations');

        $query->when($request->filled('date'), function ($query) use ($request) {
            $query->whereDate('starts_at', $request->query('date'));
        });

        $query->when($request->filled('industry'), function ($query) use ($request) {
            $query->where('industry', $request->query('industry'));
        });

        $query->when($request->filled('location'), function ($query) use ($request) {
            $query->where('location', 'like', '%'.$request->query('location').'%');
        });

        $query->when($request->filled('format'), function ($query) use ($request) {
            $query->where('is_virtual', $request->query('format') === 'virtual');
        });

        $query->when($request->boolean('one_to_one'), function ($query) {
            $query->where('one_to_one_available', true);
        });

        $query->when($request->boolean('small_group'), function ($query) {
            $query->where('small_group_available', true);
        });

        $query->when($request->filled('price'), function ($query) use ($request) {
            $query->where('is_free', $request->query('price') === 'free');
        });

        $query->when($request->filled('accessibility'), function ($query) use ($request) {
            $options = $request->query('accessibility');
            $options = is_array($options) ? $options : explode(',', $options);

            foreach ($options as $option) {
                $query->whereJsonContains('accessibility_options', trim($option));
            }
        });

        $events = $query->orderBy('starts_at')->paginate(12)->withQueryString();

        $this->attachMyRegistrations($events->getCollection(), $request->user());

        return EventResource::collection($events);
    }

    public function show(Request $request, Event $event)
    {
        $event->loadCount('registrations');
        $this->attachMyRegistrations(collect([$event]), $request->user());

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create($request->validated() + ['created_by' => $request->user()->id]);

        return (new EventResource($event->loadCount('registrations')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        abort_if($event->created_by !== $request->user()->id, 403, 'You can only edit events you created.');

        $event->update($request->validated());

        return new EventResource($event->loadCount('registrations'));
    }

    public function destroy(Request $request, Event $event)
    {
        abort_if($event->created_by !== $request->user()->id, 403, 'You can only delete events you created.');

        $event->delete();

        return response()->json(null, 204);
    }

    /**
     * Registration doubles as the first step of the matching process, so it
     * collects the attendee's interaction preferences for this event rather
     * than a bare "attending: yes" flag.
     */
    public function register(StoreEventRegistrationRequest $request, Event $event)
    {
        $user = $request->user();

        if ($event->registrations()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already registered for this event.'], 409);
        }

        if ($event->capacity !== null && $event->registrations()->count() >= $event->capacity) {
            return response()->json(['message' => 'This event is full.'], 422);
        }

        $event->registrations()->create($request->validated() + ['user_id' => $user->id]);

        $event->loadCount('registrations');
        $this->attachMyRegistrations(collect([$event]), $user);

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function cancelRegistration(Request $request, Event $event)
    {
        $deleted = $event->registrations()->where('user_id', $request->user()->id)->delete();

        if (! $deleted) {
            return response()->json(['message' => 'You are not registered for this event.'], 404);
        }

        $event->loadCount('registrations');
        $this->attachMyRegistrations(collect([$event]), $request->user());

        return new EventResource($event);
    }

    public function myEvents(Request $request)
    {
        $events = $request->user()
            ->registeredEvents()
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->get();

        $this->attachMyRegistrations($events, $request->user());

        return EventResource::collection($events);
    }

    private function attachMyRegistrations($events, $user): void
    {
        $registrationsByEventId = EventRegistration::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->get()
            ->keyBy('event_id');

        $events->each(function (Event $event) use ($registrationsByEventId) {
            $registration = $registrationsByEventId->get($event->id);
            $event->is_registered = (bool) $registration;
            $event->my_registration = $registration;
        });
    }
}
