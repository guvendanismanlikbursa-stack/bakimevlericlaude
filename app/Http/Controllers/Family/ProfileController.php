<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Aile hesabi icin daha once hic olmayan "Hesap Bilgilerim" sayfasi - kurum
// tarafindaki Facility\ProfileController'in cok daha kucuk bir esdegeri
// (aile bir "kurum" gibi genis bir varlik degil, sadece kendi hesabini
// duzenliyor). 12 Temmuz 2026'da panel denetiminde bulundu: aile bu
// bilgileri hicbir yerden degistiremiyordu.
class ProfileController extends Controller
{
    public function edit()
    {
        $brand = current_brand();
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $notificationGroups = notification_preference_groups('family');

        return view("themes.{$brand['theme']}.family.profile", compact('family', 'notificationGroups'));
    }

    public function update(Request $request)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));

        $data = $request->validate([
            'name' => 'required|string|max:180',
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $family->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $family->update(['password' => Hash::make($data['password'])]);
            // 15 Agustos 2026: bkz. Facility\AuthController::changePassword()
            // ayni tarihli yorum - sifre degisirken session ID yenilenmiyordu.
            $request->session()->regenerate();
            $request->session()->regenerateToken();
        }

        session(['family_user_name' => $family->name]);

        return back()->with('success', 'Hesap bilgileriniz güncellendi.');
    }

    /**
     * 12 Agustos 2026: kullanicinin talebi - "hangi olaylar icin e-posta/push
     * gelsin secemiyorum". Bilerek ANA profil formundan (update(), name
     * zorunlu alanli) AYRI, kendi rotasi olan kucuk bir islem - bkz.
     * Facility\ProfileController::updateNotifications() ayni tasarim.
     */
    public function updateNotifications(Request $request)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));

        $prefs = [];
        foreach (notification_preference_groups('family') as $group => $meta) {
            foreach ($meta['types'] as $type) {
                $prefs[$type] = [
                    'email' => $request->boolean("notifications.{$group}.email"),
                    'push' => $request->boolean("notifications.{$group}.push"),
                ];
            }
        }

        $family->update(['notification_preferences' => $prefs]);

        return back()->with('success', 'Bildirim tercihleriniz güncellendi.');
    }
}
