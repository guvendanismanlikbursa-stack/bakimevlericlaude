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

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.']);
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['facility_user_id' => $user->id, 'facility_user_name' => $user->name]);

        if (! $user->hasVerifiedEmail()) {
            return redirect(brand_route('facility.verify-email.notice'));
        }

        if ($user->must_change_password) {
            return redirect(brand_route('facility.password.change'));
        }

        return redirect(brand_route('facility.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['facility_user_id', 'facility_user_name']);
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

        return redirect(brand_route('facility.dashboard'))->with('success', 'Şifreniz güncellendi.');
    }
}
