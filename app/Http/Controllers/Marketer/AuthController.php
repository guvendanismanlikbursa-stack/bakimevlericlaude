<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Models\Marketer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// 27 Eylul 2026: kullanicinin talebi - pazarlamaci sadece telefon+sifre ile
// giris yapar, e-posta yok. Telefon normalizasyonu classify_phone_type()'taki
// AYNI mantikla (bosluk/tire/0/90 on eki farksiz kabul edilir).
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('marketer.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $normalized = normalize_phone_digits($credentials['phone']);
        $marketer = $normalized === '' ? null : Marketer::all()->first(fn (Marketer $m) => normalize_phone_digits($m->phone) === $normalized);

        if (! $marketer || ! Hash::check($credentials['password'], $marketer->password)) {
            return back()->withErrors(['phone' => 'Telefon veya şifre hatalı.'])->onlyInput('phone');
        }

        if (! $marketer->is_active) {
            return back()->withErrors(['phone' => 'Hesabınız devre dışı bırakılmış, yönetici ile iletişime geçin.']);
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->forget(['admin_id', 'admin_name', 'family_user_id', 'family_user_name', 'facility_user_id', 'facility_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        session(['marketer_id' => $marketer->id, 'marketer_name' => $marketer->name]);

        $marketer->update(['last_login_at' => now(), 'is_logged_in' => true]);

        return redirect()->route('marketer.crm.index');
    }

    public function logout(Request $request)
    {
        $marketerId = session('marketer_id');
        if ($marketerId) {
            Marketer::whereKey($marketerId)->update(['is_logged_in' => false]);
        }

        $request->session()->forget(['marketer_id', 'marketer_name']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('marketer.login');
    }
}
