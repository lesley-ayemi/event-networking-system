<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\Concerns\AttachesEventUserContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventRegistrationRequest;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    use AttachesEventUserContext;

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

        // Powers the organiser's "My events" management view.
        $query->when($request->boolean('mine'), function ($query) use ($request) {
            $query->where('created_by', $request->user()->id);
        });

        $events = $query->orderBy('starts_at')->paginate(12)->withQueryString();

        $this->attachUserContext($events->getCollection(), $request->user());

        return EventResource::collection($events);
    }

    public function show(Request $request, Event $event)
    {
        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request)
    {
        if ($request->user()->organiser_status !== 'approved') {
            throw new ApiException(
                'You must be an approved event organiser to create events.',
                'ORGANISER_NOT_APPROVED',
                403,
            );
        }

        $event = Event::create($request->validated() + ['created_by' => $request->user()->id]);
        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        abort_if($event->created_by !== $request->user()->id, 403, 'You can only edit events you created.');

        $event->update($request->validated());
        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return new EventResource($event);
    }

    public function destroy(Request $request, Event $event)
    {
        abort_if($event->created_by !== $request->user()->id, 403, 'You can only delete events you created.');

        $event->delete();

        return response()->json(null, 204);
    }

    // Mirrors ProfileController::uploadPhoto's pattern: a separate multipart
    // endpoint rather than folding the file into the JSON create/update
    // payload, since the rest of the event form is submitted as plain JSON.
    public function uploadCoverImage(Request $request, Event $event)
    {
        abort_if($event->created_by !== $request->user()->id, 403, 'You can only edit events you created.');

        $request->validate([
            'cover_image' => ['required', 'image', 'max:4096'],
        ]);

        if ($event->cover_image) {
            $previousPath = Str::after($event->cover_image, '/storage/');
            if ($previousPath && Storage::disk('public')->exists($previousPath)) {
                Storage::disk('public')->delete($previousPath);
            }
        }

        $path = $request->file('cover_image')->store('event-covers', 'public');
        $event->cover_image = Storage::disk('public')->url($path);
        $event->save();

        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return new EventResource($event);
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
            throw new ApiException('You are already registered for this event.', 'EVENT_ALREADY_REGISTERED', 409);
        }

        if ($event->starts_at !== null && $event->starts_at->isPast()) {
            throw new ApiException('Registration for this event is closed.', 'EVENT_REGISTRATION_CLOSED', 422);
        }

        if ($event->capacity !== null && $event->registrations()->count() >= $event->capacity) {
            throw new ApiException('This event is full.', 'EVENT_FULL', 422);
        }

        $event->registrations()->create($request->validated() + ['user_id' => $user->id]);

        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $user);

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function cancelRegistration(Request $request, Event $event)
    {
        $deleted = $event->registrations()->where('user_id', $request->user()->id)->delete();

        if (! $deleted) {
            throw new ApiException('You are not registered for this event.', 'EVENT_NOT_REGISTERED', 404);
        }

        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        return new EventResource($event);
    }

    public function myEvents(Request $request)
    {
        $events = $request->user()
            ->registeredEvents()
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->get();

        $this->attachUserContext($events, $request->user());

        return EventResource::collection($events);
    }
}
