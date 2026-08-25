<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Mail\FacilityStaffInvitedMail;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// 12 Agustos 2026: kullanicinin talebi - "ekibime hesap acamiyorum, tek bir
// email/sifre var". facility_users.role artik 'owner'/'staff' ayrimi
// tasiyor (bkz. migration); SADECE owner ekip yonetebilir - staff hesaplari
// panelin geri kalanina (mesaj/teklif/profil) owner ile ayni erisime sahip,
// sadece bu ekranlara giremez.
class TeamController extends Controller
{
    public function index()
    {
        $brand = current_brand();
        $owner = FacilityUser::findOrFail(session('facility_user_id'));
        abort_unless($owner->role === 'owner', 403);

        $team = $owner->facility->facilityUsers()->orderBy('created_at')->get();

        return view("themes.{$brand['theme']}.facility.team", compact('team', 'owner'));
    }

    public function store(Request $request)
    {
        $owner = FacilityUser::findOrFail(session('facility_user_id'));
        abort_unless($owner->role === 'owner', 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:facility_users,email',
        ]);

        if ($error = email_taken_by_other_account_type($data['email'])) {
            return back()->withErrors(['email' => $error])->withInput();
        }

        // 25 Agustos 2026: kullanicinin bildirdigi gercek hata - varsayilan
        // Str::password() sembol de urettigi icin (\, <, (, ? gibi) mailde
        // cift-tiklamayla TAMAMI secilemiyordu (kelime siniri sayiliyor) -
        // kullanici PC'de kopyala-yapistir yapinca bile eksik/hatali sifre
        // yapistiriyor, telefonda elle yazmak da asiri zor oluyordu. Sadece
        // harf+rakam ureterek hem tek tikla/cift tikla tam secilebilir hem
        // de elle yazilmasi kolay bir gecici sifreye gecildi - guvenlik
        // farkı ihmal edilebilir duzeyde (14 karakter, ilk giriste zaten
        // degistirilmesi ZORUNLU).
        $temporaryPassword = Str::password(14, symbols: false);

        $staff = FacilityUser::create([
            'facility_id' => $owner->facility_id,
            'role' => 'staff',
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'status' => 'active',
        ]);

        // 12 Agustos 2026: kullanicinin daha once tekrarlayan talebi geregi
        // ("kuyruga filan asla alma, dogrudan mail yollasin") - bkz.
        // NotificationQueueSafetyTest, FacilityClaimController::approve()
        // ayni deseni kullanir - ->send() DEGIL ->sendNow().
        Mail::to($staff->email)->sendNow(new FacilityStaffInvitedMail(
            $owner->facility,
            $staff->email,
            $temporaryPassword,
            brand_route('facility.login')
        ));

        return back()->with('success', 'Ekip üyesi eklendi, giriş bilgileri e-postayla gönderildi.');
    }

    // 12 Agustos 2026: kullanicinin talebi - bkz. ReviewController::reply()
    // ayni yorum, /site/{brand}/... rotalarinda ortuk route-model-binding
    // guvenilir olmadigi icin model elle cozuluyor.
    public function destroy(Request $request)
    {
        $member = FacilityUser::findOrFail($request->route('member'));
        $owner = FacilityUser::findOrFail(session('facility_user_id'));
        abort_unless($owner->role === 'owner', 403);
        abort_unless($member->facility_id === $owner->facility_id, 403);
        abort_if($member->id === $owner->id, 400, 'Kendi hesabınızı kaldıramazsınız.');

        $member->delete();

        return back()->with('success', 'Ekip üyesi kaldırıldı.');
    }
}
