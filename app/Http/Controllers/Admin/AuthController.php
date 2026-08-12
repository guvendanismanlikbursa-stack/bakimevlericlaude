<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminLoginCodeMail;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

// 12 Temmuz 2026'da eklendi: admin paneli platformdaki en hassas panel
// (tum kurum/aile verisi, onay yetkileri) ama sadece sifreyle korunuyordu.
// Iki adimli dogrulama (e-posta kodu) eklendi - Google Authenticator tarzi
// bir uygulama kurulumu gerektirmedigi icin teknik olmayan bir admin icin
// de kullanisli; mevcut mail altyapisini kullanir, yeni bagimlilik yok.
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        // 30 Temmuz 2026: mobil PWA'da 2FA kod giris ekrani (giris/dogrula)
        // acilmiyordu - kullanici talebiyle 2FA GECICI olarak devre disi
        // birakildi. Tekrar acmak icin: bu bloğu kaldirip yerine eski
        // sendLoginCode() + admin_2fa_pending_id session + admin.login.verify
        // yonlendirmesini geri getir (showVerify/verify/resendCode metodlari
        // ve route'lari dokunulmadan asagida duruyor, hemen kullanilabilir).
        $admin->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        // 30 Temmuz 2026: ayni cihaz/tarayicida daha once (impersonation ya
        // da dogrudan giris ile) birakilmis facility_user_id/family_user_id
        // varsa, PushSubscriptionController::currentSubscribable() gibi rol
        // kontrolleri gecerli admin_id'yi hic gormeden bunlara takiliyordu -
        // bir role giris yapinca diger rollerin izini burada temizliyoruz.
        $request->session()->forget(['facility_user_id', 'facility_user_name', 'family_user_id', 'family_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

        return redirect()->route('admin.dashboard');
    }

    public function showVerify(Request $request)
    {
        if (! session('admin_2fa_pending_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.login-verify');
    }

    public function verify(Request $request)
    {
        $adminId = session('admin_2fa_pending_id');
        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate(['code' => 'required|string']);

        $admin = Admin::find($adminId);

        if (! $admin
            || ! $admin->two_factor_code
            || ! $admin->two_factor_expires_at
            || $admin->two_factor_expires_at->isPast()
            || ! Hash::check($data['code'], $admin->two_factor_code)
        ) {
            return back()->withErrors(['code' => 'Kod hatalı veya süresi dolmuş, lütfen tekrar deneyin.']);
        }

        $admin->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'last_login_at' => now(),
        ]);

        $request->session()->forget('admin_2fa_pending_id');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

        return redirect()->route('admin.dashboard');
    }

    public function resendCode(Request $request)
    {
        $adminId = session('admin_2fa_pending_id');
        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);
        if ($admin) {
            $this->sendLoginCode($admin);
        }

        return back()->with('success', 'Yeni kod gönderildi.');
    }

    private function sendLoginCode(Admin $admin): void
    {
        $code = (string) random_int(100000, 999999);

        $admin->update([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($admin->email)->sendNow(new AdminLoginCodeMail($code));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin giris kodu maili gonderilemedi: '.$e->getMessage(), ['admin_id' => $admin->id]);
            \Sentry\captureException($e);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id', 'admin_name']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
