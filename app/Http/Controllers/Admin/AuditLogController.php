<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEvent;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminEvent::with('admin')->latest();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $events = $query->paginate(30)->withQueryString();
        $eventTypes = AdminEvent::query()->distinct()->orderBy('event_type')->pluck('event_type');

        return view('admin.audit-log.index', compact('events', 'eventTypes'));
    }
}
