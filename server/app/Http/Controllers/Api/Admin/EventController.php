<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // Unlike the owner-scoped EventController::destroy(), an admin may remove
    // any event regardless of who created it.
    public function destroy(Request $request, Event $event)
    {
        AuditLog::record($request->user(), 'event.removed', $event, ['name' => $event->name]);

        $event->delete();

        return response()->json(null, 204);
    }
}
