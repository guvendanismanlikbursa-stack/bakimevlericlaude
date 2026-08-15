<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.facility.login");
    }

    public function login(Request $request)
    {
        $brand = app('currentBrand');

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = FacilityUser::where('email', $credentials['email'])->first();

        // 21 Temmuz 2026: Google ile kayit olan hesaba rastgele bir sifre
        // atanir (kullanici hic bilmez) - normal sifre girisi deneyince
        // hep "hatali" cikip kullanici neden giremedigini anlayamiyordu.
        if ($user && $user->google_id && ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Bu hesap Google ile oluşturulmuş. Lütfen "Google ile giriş yap" seçeneğini kullanın.'])->onlyInput('email');
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.']);
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        // 30 Temmuz 2026: bkz. Admin\AuthController::login() ayni yorum -
        // baska bir rolden kalma session anahtarlari burada da temizlenir.
        $request->session()->forget(['admin_id', 'admin_name', 'family_user_id', 'family_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        session(['facility_user_id' => $user->id, 'facility_user_name' => $user->name]);

        // 30 Temmuz 2026: sifre degistirme, e-posta dogrulamasindan ONCE
        // kontrol edilir - bkz. FacilityUserAuth middleware'indeki ayni
        // gerekce (gecici sifreyle acilan hesap once kendi sifresini
        // belirlemeli, dogrulama linkine tiklamamis olsa bile).
        if ($user->must_change_password) {
            return redirect(brand_route('facility.password.change'));
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect(brand_route('facility.verify-email.notice'));
        }

        return redirect(brand_route('facility.dashboard'));
    }

    public function logout(Request $request)
    {
        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan denetimde bulundu - admin "Panelde Gor" ile bu paneli
        // goruntulerken kullanici ust bardaki "Admin Paneline Don" yerine
        // panelin KENDI normal cikis linkine tiklarsa, impersonator_admin_id/
        // name session'da KALMAYA devam ediyordu. brand.blade.php'deki
        // turuncu banner SADECE bu iki anahtara baktigi icin (facility_user_id'ye
        // degil) banner ve "geri don" butonu gorunmeye devam ediyordu - o
        // butona basan HERKES (ayni tarayiciyi/cihazi paylasan baska biri)
        // sifresiz dogrudan admin oturumuna donebiliyordu.
        $request->session()->forget(['facility_user_id', 'facility_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect(brand_route('home'));
    }

    public function showChangePassword()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.facility.change-password");
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $user->update(['password' => Hash::make($data['password']), 'must_change_password' => false]);

        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan denetimde bulundu - sifre degisirken mevcut oturumun
        // session ID'si hic yenilenmiyordu (session fixation riski). Diger
        // cihazlardaki ESKI oturumlar zaten dogal sure sonuna kadar gecerli
        // kalmaya devam eder (bu platform Laravel'in Auth:: facade'ini degil
        // ham session anahtarlarini kullandigi icin "tum cihazlardan cikis"
        // ozelligi mevcut degil - bu ayri, daha buyuk bir mimari karar).
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect(brand_route('facility.dashboard'))->with('success', 'Şifreniz güncellendi.');
    }
}
