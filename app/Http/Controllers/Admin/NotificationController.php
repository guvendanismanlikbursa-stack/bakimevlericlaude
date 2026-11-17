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

        // 17 Kasım 2026: kullanıcının talebi - canlı sohbet için görsel bildirim + WhatsApp tarzı tikler.
        // Admin panelinin herhangi bir sayfası açıkken (her 20 sn) misafir mesajları "iletildi" sayılır;
        // yanıtta okunmamış sohbet sayısı ve son misafir mesajının id'si döner (yeni mesajda ekranda uyarı için).
        \App\Models\ChatMessage::alindiIsaretle(null, 'guest', false);
        $sonMisafirMesaji = \App\Models\ChatMessage::where('sender_type', 'guest')->latest('id')->first(['id', 'chat_thread_id', 'body', 'attachment_type']);
        $sohbet = $sonMisafirMesaji ? \App\Models\ChatThread::find($sonMisafirMesaji->chat_thread_id, ['id', 'guest_name', 'brand']) : null;

        return response()->json([
            'count' => $admin->notifications()->unread()->count(),
            'sohbet_okunmamis' => \App\Models\ChatThread::where('unread_by_admin', true)->count(),
            'sohbet_son' => $sonMisafirMesaji ? [
                'id' => $sonMisafirMesaji->id,
                'ad' => $sohbet?->guest_name ?: 'Ziyaretçi',
                'site' => $sohbet ? config("brands.brands.{$sohbet->brand}.name", $sohbet->brand) : '',
                'onizleme' => $sonMisafirMesaji->body ? \Illuminate\Support\Str::limit($sonMisafirMesaji->body, 70) : '['.($sonMisafirMesaji->attachment_type ?? 'dosya').']',
                'url' => route('admin.chat.show', $sonMisafirMesaji->chat_thread_id),
            ] : null,
        ]);
    }
}
