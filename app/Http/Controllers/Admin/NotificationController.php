<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

// 28 Eylul 2026: kullanicinin talebi - "admin nerede görecek" (pazarlamacidan
// gelen kurum bildirimi ornegi). Facility\NotificationController/Family\
// NotificationController ile BIREBIR AYNI, kanitlanmis desen.
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $admin = Admin::findOrFail(session('admin_id'));
        $tab = $request->query('tab', 'unread') === 'read' ? 'read' : 'unread';

        $query = $admin->notifications();
        $tab === 'read' ? $query->whereNotNull('read_at') : $query->whereNull('read_at');

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount = $admin->notifications()->whereNull('read_at')->count();

        return view('admin.notifications.index', compact('notifications', 'admin', 'tab', 'unreadCount'));
    }

    public function markRead(Request $request, int $notification)
    {
        $admin = Admin::findOrFail(session('admin_id'));
        $item = $admin->notifications()->findOrFail($notification);
        $item->markAsRead();

        return back();
    }

    public function destroy(int $notification)
    {
        $admin = Admin::findOrFail(session('admin_id'));
        $admin->notifications()->findOrFail($notification)->delete();

        return back()->with('success', 'Bildirim silindi.');
    }
}
