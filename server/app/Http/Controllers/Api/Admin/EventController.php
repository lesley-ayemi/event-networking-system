<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\AttachesEventUserContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use AttachesEventUserContext;

    // Unlike the owner-scoped EventController::update(), an admin may edit
    // any event regardless of who created it.
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());
        $event->loadCount('registrations');
        $this->attachUserContext(collect([$event]), $request->user());

        AuditLog::record($request->user(), 'event.updated', $event, ['name' => $event->name]);

        return new EventResource($event);
    }

    // Unlike the owner-scoped EventController::destroy(), an admin may remove
    // any event regardless of who created it.
    public function destroy(Request $request, Event $event)
    {
        AuditLog::record($request->user(), 'event.removed', $event, ['name' => $event->name]);

        $event->delete();

        return response()->json(null, 204);
    }

    // Support/cleanup tool: lets an admin see who's registered for an event
    // and remove a single bad registration without deleting the whole event.
    public function registrations(Event $event)
    {
        $registrations = $event->registrations()->with('user')->orderBy('created_at')->get();

        return response()->json(['data' => $registrations]);
    }

    public function removeRegistration(Request $request, Event $event, EventRegistration $registration)
    {
        abort_if($registration->event_id !== $event->id, 404);

        AuditLog::record($request->user(), 'event.registration_removed', $event, [
            'user_id' => $registration->user_id,
        ]);

        $registration->delete();

        return response()->json(null, 204);
    }
}
