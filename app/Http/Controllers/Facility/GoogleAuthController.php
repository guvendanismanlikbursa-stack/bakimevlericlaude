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
        session(['facility_user_id' => $user->id, 'facility_user_name' => $user->name]);

        return redirect(brand_route('facility.dashboard'));
    }
}
