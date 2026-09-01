<?php

namespace App\Http\Controllers\Facility;

use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

// Kurum paneli icin "Google ile giris" - aile tarafindan farkli olarak
// SADECE mevcut, admin tarafindan onaylanmis bir kurum hesabiyla e-postasi
// eslesirse giris yapar. Google ile YENI kurum hesabi OLUSTURULMAZ - kurum
// hesaplari sadece admin'in bir basvuruyu/sahiplenmeyi onaylamasiyla
// dogar (bkz. FacilityClaimController/FacilityRegistrationController), bu
// akisin bozulmamasi icin Google burada kayit degil sadece giris yolu.
class GoogleAuthController extends AuthController
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirectUrl(brand_route('facility.google-callback'))
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(brand_route('facility.google-callback'))
                ->user();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kurum Google girisi basarisiz: ' . $e->getMessage(), ['exception' => $e]);
            \Sentry\captureException($e);

            return redirect(brand_route('facility.login'))->with('error', 'Google ile giriş başarısız oldu, lütfen tekrar deneyin.');
        }

        $user = FacilityUser::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            return redirect(brand_route('facility.login'))->with('error', 'Bu Google hesabıyla eşleşen bir kurum hesabı bulunamadı. Henüz başvurmadıysanız kurum kaydı sayfasından başvurabilirsiniz.');
        }

        if ($user->status !== 'active') {
            return redirect(brand_route('facility.login'))->with('error', 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.');
        }

        $user->update([
            'google_id' => $user->google_id ?: $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar() ?: $user->avatar_url,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->forget(['admin_id', 'admin_name', 'family_user_id', 'family_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        session(['facility_user_id' => $user->id, 'facility_user_name' => $user->name]);

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bkz.
        // AuthController::login() ayni gerekce (gecici sifreyle acilan
        // hesap once kendi sifresini belirlemeli) - bu kontrol SADECE
        // normal (e-posta/sifre) giriste vardi, Google ile giris yolunda
        // hic yoktu. Admin onayiyla acilan, e-posta/WhatsApp'ta DUZ METIN
        // gecici sifre gonderilen bir hesap, sahibi Google ile giris
        // yaparsa bu sifreyi asla degistirmiyordu - o gecici sifre
        // suresiz gecerli kalmaya devam ediyordu.
        if ($user->must_change_password) {
            return redirect(brand_route('facility.password.change'));
        }

        return redirect(brand_route('facility.dashboard'));
    }
}
