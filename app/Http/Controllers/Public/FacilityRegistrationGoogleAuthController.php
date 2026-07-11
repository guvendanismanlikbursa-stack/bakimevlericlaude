<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

// Kurum kayit basvuru formunda "Google ile devam et" - hesap OLUSTURMAZ,
// sadece "Yetkili Bilgileri" alanindaki ad/e-postayi otomatik doldurur
// (bkz. GoogleChatAuthController'daki ayni desen). Gercek kurum hesabi
// admin onayindan sonra ayrica olusturuluyor, bu form sadece basvuru.
class FacilityRegistrationGoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirectUrl(brand_route('facility-registration.google-callback'))
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(brand_route('facility-registration.google-callback'))
                ->user();
        } catch (\Throwable $e) {
            return redirect(brand_route('facility-registration.create'))->with('error', 'Google ile giriş başarısız oldu, lütfen tekrar deneyin.');
        }

        return redirect(brand_route('facility-registration.create', array_filter([
            'applicant_google_name' => $googleUser->getName() ?: $googleUser->getNickname(),
            'applicant_google_email' => $googleUser->getEmail(),
        ])));
    }
}
