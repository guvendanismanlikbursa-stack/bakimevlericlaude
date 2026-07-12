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

        return view("themes.{$brand['theme']}.family.profile", ['family' => $family]);
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
        }

        session(['family_user_name' => $family->name]);

        return back()->with('success', 'Hesap bilgileriniz güncellendi.');
    }
}
