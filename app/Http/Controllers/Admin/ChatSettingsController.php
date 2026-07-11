<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatWorkingHour;
use App\Models\Setting;
use Illuminate\Http\Request;

class ChatSettingsController extends Controller
{
    private const WEEKDAY_LABELS = [0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi'];

    public function edit()
    {
        $hours = ChatWorkingHour::orderBy('weekday')->get()->keyBy('weekday');
        $offlineMessage = Setting::get('chat_offline_message', 'Şu an çevrimdışıyız. Mesajınızı bırakın, size en kısa sürede döneriz.');
        $weekdayLabels = self::WEEKDAY_LABELS;

        return view('admin.chat-settings.edit', compact('hours', 'offlineMessage', 'weekdayLabels'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'offline_message' => 'required|string|max:500',
            'days' => 'required|array',
            'days.*.is_active' => 'nullable|boolean',
            'days.*.open_time' => 'required|date_format:H:i',
            'days.*.close_time' => 'required|date_format:H:i|after:days.*.open_time',
        ]);

        Setting::set('chat_offline_message', $data['offline_message']);

        foreach ($data['days'] as $weekday => $day) {
            ChatWorkingHour::updateOrCreate(
                ['weekday' => (int) $weekday],
                [
                    'open_time' => $day['open_time'],
                    'close_time' => $day['close_time'],
                    'is_active' => ! empty($day['is_active']),
                ]
            );
        }

        return back()->with('success', 'Çalışma saatleri güncellendi.');
    }
}
