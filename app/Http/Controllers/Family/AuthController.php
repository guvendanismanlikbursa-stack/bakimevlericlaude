<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\OfferRequest;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.register");
    }

    public function register(Request $request, GeoLookupService $geo)
    {
        $brand = app('currentBrand');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:family_users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'consent' => 'required|accepted',
            'signup_lat' => 'nullable|numeric|between:-90,90',
            'signup_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
        ]);

        $signupCityName = null;
        if ($request->filled('signup_lat') && $request->filled('signup_lng')) {
            $nearest = $geo->nearestCity((float) $data['signup_lat'], (float) $data['signup_lng']);
            $signupCityName = $nearest['city'] ?? null;
        }

        $family = FamilyUser::create([
            'registered_brand' => $brand['slug'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'consent_accepted_at' => now(),
            'consent_ip' => $request->ip(),
            'signup_lat' => $data['signup_lat'] ?? null,
            'signup_lng' => $data['signup_lng'] ?? null,
            'signup_city_name' => $signupCityName,
        ]);

        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

        EmailVerificationController::send($family, $brand);

        return $this->afterLogin($brand);
    }

    public function showLogin()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.login");
    }

    public function login(Request $request)
    {
        $brand = app('currentBrand');

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $family = FamilyUser::where('email', $credentials['email'])->first();

        if (! $family || ! Hash::check($credentials['password'], $family->password)) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

        return $this->afterLogin($brand);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['family_user_id', 'family_user_name']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect(brand_route('home'));
    }

    /**
     * Giriş/kayıt sonrası: eğer oturumda yarım kalmış bir "ücret talebi" varsa
     * onu simdi olusturup aile panele oyle yonlendirir; yoksa direkt panel.
     */
    private function afterLogin(array $brand)
    {
        if ($pending = session('pending_offer_request')) {
            $pending['family_user_id'] = session('family_user_id');
            OfferRequest::create($pending);
            session()->forget('pending_offer_request');

            return redirect(brand_route('family.dashboard'))->with('success', 'Hesabınız oluşturuldu ve talebiniz iletildi. Uygun kurumlardan teklif gelmeye başlayacak.');
        }

        if ($intended = session('intended_url')) {
            session()->forget('intended_url');

            return redirect($intended);
        }

        return redirect(brand_route('family.dashboard'));
    }
}
