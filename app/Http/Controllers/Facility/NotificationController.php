<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $notifications = $user->notifications()->paginate(20);

        return view('themes._shared.facility.notifications', compact('notifications'));
    }

    public function markRead(Request $request, int $notification)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $item = $user->notifications()->findOrFail($notification);
        $item->markAsRead();

        return back();
    }
}
