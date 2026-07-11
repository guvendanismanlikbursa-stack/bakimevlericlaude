<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $notifications = $family->notifications()->paginate(20);

        return view('themes._shared.family.notifications', compact('notifications'));
    }

    public function markRead(Request $request, int $notification)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $item = $family->notifications()->findOrFail($notification);
        $item->markAsRead();

        return back();
    }

    // Panel acikken sesli bildirim icin hafif polling ucu - bkz.
    // layouts/brand.blade.php'deki js-notification-poll script'i.
    public function unreadCount()
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));

        return response()->json(['count' => $family->notifications()->unread()->count()]);
    }
}
