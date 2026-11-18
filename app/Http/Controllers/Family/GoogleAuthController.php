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
            \Illuminate\Support\Facades\Log::warning('Aile Google girisi basarisiz: ' . $e->getMessage(), ['exception' => $e]);
            \Sentry\captureException($e);

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
            $request->session()->forget(['admin_id', 'admin_name', 'facility_user_id', 'facility_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
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
            // 18 Kasım 2026: kullanıcının kararı - şehir ve yaş zorunlu.
            'signup_city' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Models\City::cachedAll()->pluck('name')->all())],
            'age' => 'required|integer|between:18,100',
            'consent' => 'required|accepted',
            'signup_lat' => 'nullable|numeric|between:-90,90',
            'signup_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
            'signup_city.required' => 'Lütfen şehrinizi seçin.',
            'signup_city.in' => 'Lütfen listeden bir şehir seçin.',
            'age.required' => 'Lütfen yaşınızı yazın.',
            'age.integer' => 'Yaşınızı rakamla yazın.',
            'age.between' => 'Yaş 18 ile 100 arasında olmalıdır.',
        ]);

        if (FamilyUser::where('email', $pending['email'])->exists()) {
            session()->forget('family_google_pending');

            return redirect(brand_route('family.login'))->with('error', 'Bu e-posta ile zaten bir hesap var, lütfen giriş yapın.');
        }

        if ($error = email_taken_by_other_account_type($pending['email'])) {
            session()->forget('family_google_pending');

            return redirect(brand_route('family.register'))->with('error', $error);
        }

        // 18 Kasım 2026: şehir artık formda zorunlu seçiliyor; konumdan tahmin yerine ailenin seçtiği şehir kaydedilir.
        $signupCityName = $data['signup_city'];

        $family = FamilyUser::create([
            'registered_brand' => $brand['slug'],
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $data['phone'],
            'age' => (int) $data['age'],
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
        $request->session()->forget(['admin_id', 'admin_name', 'facility_user_id', 'facility_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);
        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);
        notify_admins_new_family($family, 'google');

        try {
            \Illuminate\Support\Facades\Mail::to($family->email)->sendNow(new \App\Mail\FamilyWelcomeMail($family, $brand['name'], brand_route('family.dashboard')));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Aile hos geldin maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
            notify_admin_of_exception($e);
        }

        return $this->afterLogin($brand);
    }
}
