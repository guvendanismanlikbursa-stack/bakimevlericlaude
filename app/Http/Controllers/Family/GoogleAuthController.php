<?php

namespace App\Http\Controllers\Family;

use App\Models\FamilyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

// Aile hesabi icin "Google ile devam et" - hem kayit hem giris tek akistir:
// e-postasi eslesen bir hesap varsa direkt giris yapilir (yoksa Google
// hesabina baglanir), yoksa telefon + KVKK onayi icin kisa bir ek adima
// (googleCompleteForm/googleCompleteStore) yonlendirilir, cunku bu ikisi
// Google'dan gelmiyor ama hesap olusturmak icin zorunlu.
class GoogleAuthController extends AuthController
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirectUrl(brand_route('family.google-callback'))
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $brand = app('currentBrand');

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(brand_route('family.google-callback'))
                ->user();
        } catch (\Throwable $e) {
            return redirect(brand_route('family.login'))->with('error', 'Google ile giriş başarısız oldu, lütfen tekrar deneyin.');
        }

        $family = FamilyUser::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($family) {
            if ($family->status !== 'active') {
                return redirect(brand_route('family.login'))->with('error', 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.');
            }

            $family->update([
                'google_id' => $family->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?: $family->avatar_url,
                'email_verified_at' => $family->email_verified_at ?: now(),
            ]);

            $request->session()->regenerate();
            $request->session()->regenerateToken();
            session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

            return $this->afterLogin($brand);
        }

        session(['family_google_pending' => [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?: $googleUser->getNickname(),
            'email' => $googleUser->getEmail(),
            'avatar_url' => $googleUser->getAvatar(),
        ]]);

        return redirect(brand_route('family.google-complete'));
    }

    public function completeForm()
    {
        $brand = app('currentBrand');
        $pending = session('family_google_pending');

        if (! $pending) {
            return redirect(brand_route('family.register'));
        }

        return view("themes.{$brand['theme']}.family.google-complete", ['pending' => $pending]);
    }

    public function completeStore(Request $request)
    {
        $brand = app('currentBrand');
        $pending = session('family_google_pending');

        if (! $pending) {
            return redirect(brand_route('family.register'));
        }

        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'consent' => 'required|accepted',
            'signup_lat' => 'nullable|numeric|between:-90,90',
            'signup_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
        ]);

        if (FamilyUser::where('email', $pending['email'])->exists()) {
            session()->forget('family_google_pending');

            return redirect(brand_route('family.login'))->with('error', 'Bu e-posta ile zaten bir hesap var, lütfen giriş yapın.');
        }

        $signupCityName = null;
        if ($request->filled('signup_lat') && $request->filled('signup_lng')) {
            $nearest = app(\App\Services\GeoLookupService::class)->nearestCity((float) $data['signup_lat'], (float) $data['signup_lng']);
            $signupCityName = $nearest['city'] ?? null;
        }

        $family = FamilyUser::create([
            'registered_brand' => $brand['slug'],
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $data['phone'],
            'password' => Hash::make(Str::random(32)),
            'google_id' => $pending['google_id'],
            'avatar_url' => $pending['avatar_url'],
            'email_verified_at' => now(),
            'consent_accepted_at' => now(),
            'consent_ip' => $request->ip(),
            'signup_lat' => $data['signup_lat'] ?? null,
            'signup_lng' => $data['signup_lng'] ?? null,
            'signup_city_name' => $signupCityName,
        ]);

        session()->forget('family_google_pending');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

        try {
            \Illuminate\Support\Facades\Mail::to($family->email)->queue(new \App\Mail\FamilyWelcomeMail($family, $brand['name'], brand_route('family.dashboard')));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Aile hos geldin maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
        }

        return $this->afterLogin($brand);
    }
}
