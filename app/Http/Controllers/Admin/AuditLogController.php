<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\AdminEvent;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $query = AdminEvent::with('admin')->latest();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        // 26 Kasım 2026: kurum/uzman düzenleme sayfasındaki "Değişiklik geçmişi" bağlantısı.
        if ($request->filled('entity_id')) {
            $query->where('entity_id', (int) $request->entity_id);
        }

        $events = $query->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $events)) {
            return $redirect;
        }

        $eventTypes = AdminEvent::query()->distinct()->orderBy('event_type')->pluck('event_type');

        return view('admin.audit-log.index', compact('events', 'eventTypes'));
    }
}
