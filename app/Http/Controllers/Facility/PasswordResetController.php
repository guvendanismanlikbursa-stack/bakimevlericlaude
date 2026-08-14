<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Mail\FacilityPasswordResetMail;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class PasswordResetController extends Controller
{
    public function showRequest()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.facility.password-request");
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = FacilityUser::where('email', $data['email'])->first();

        if ($user) {
            static::send($user, app('currentBrand'));
        }

        // Hesabin var olup olmadigini disari sizdirmamak icin: e-posta
        // kayitli olmasa da ayni genel basari mesaji gosterilir.
        return back()->with('success', 'Bu e-posta kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.');
    }

    public function showReset(Request $request)
    {
        $user = FacilityUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), self::hashFor($user)), 403);

        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.facility.password-reset", compact('user'));
    }

    public function reset(Request $request)
    {
        $user = FacilityUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), self::hashFor($user)), 403);

        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return redirect(brand_route('facility.login'))->with('success', 'Şifreniz güncellendi, şimdi giriş yapabilirsiniz.');
    }

    public static function send(FacilityUser $user, array $brand): void
    {
        $params = ['id' => $user->id, 'hash' => self::hashFor($user)];
        $routeName = 'facility.password.reset';

        if (request()->route('brand')) {
            $routeName = 'brand.facility.password.reset';
            $params['brand'] = request()->route('brand');
        }

        $resetUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);

        try {
            Mail::to($user->email)->sendNow(new FacilityPasswordResetMail($user, $resetUrl, $brand['name']));
        } catch (\Throwable $e) {
            Log::warning('Kurum sifre sifirlama maili gonderilemedi: ' . $e->getMessage(), ['facility_user_id' => $user->id]);
            notify_admin_of_exception($e);
        }
    }

    // 21 Temmuz 2026: Family/PasswordResetController ile ayni fix - hash
    // artik mevcut sifre hash'ine de bagli, sifre degisir degismez eski
    // TUM linkler otomatik gecersiz olur (tek-kullanimlik hale gelir).
    private static function hashFor(FacilityUser $user): string
    {
        return sha1($user->email.$user->password);
    }
}
