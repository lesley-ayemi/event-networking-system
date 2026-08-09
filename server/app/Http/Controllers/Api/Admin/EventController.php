<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\AttachesEventUserContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\AuditLog;
use App\Models\Event;
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
}
