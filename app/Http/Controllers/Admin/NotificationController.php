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

    // 14 Ekim 2026: kullanıcının talebi - admin panelinde hiç sesli uyarı
    // yoktu (bkz. panel-notification-alerts.blade.php, sadece aile/kurum
    // panelinde çalışıyordu). Facility\NotificationController::unreadCount()
    // ile AYNI desen - o partial'ı admin için de açmak için gereken ucu.
    public function unreadCount()
    {
        $admin = Admin::findOrFail(session('admin_id'));

        return response()->json(['count' => $admin->notifications()->unread()->count()]);
    }
}
