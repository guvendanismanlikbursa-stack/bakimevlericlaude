<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Mail\FamilyPasswordResetMail;
use App\Models\FamilyUser;
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

        return view("themes.{$brand['theme']}.family.password-request");
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $family = FamilyUser::where('email', $data['email'])->first();

        if ($family) {
            static::send($family, app('currentBrand'));
        }

        return back()->with('success', 'Bu e-posta kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.');
    }

    public function showReset(Request $request)
    {
        $family = FamilyUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), self::hashFor($family)), 403);

        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.password-reset", compact('family'));
    }

    public function reset(Request $request)
    {
        $family = FamilyUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), self::hashFor($family)), 403);

        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $family->update(['password' => Hash::make($data['password'])]);

        return redirect(brand_route('family.login'))->with('success', 'Şifreniz güncellendi, şimdi giriş yapabilirsiniz.');
    }

    public static function send(FamilyUser $family, array $brand): void
    {
        $params = ['id' => $family->id, 'hash' => self::hashFor($family)];
        $routeName = 'family.password.reset';

        if (request()->route('brand')) {
            $routeName = 'brand.family.password.reset';
            $params['brand'] = request()->route('brand');
        }

        $resetUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);

        try {
            Mail::to($family->email)->sendNow(new FamilyPasswordResetMail($family, $resetUrl, $brand['name']));
        } catch (\Throwable $e) {
            Log::warning('Aile sifre sifirlama maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
        }
    }

    // 21 Temmuz 2026: hash sadece e-postaya bagliydi (sha1($email)) - sifre
    // degismedigi surece SABIT kaldigi icin, imzali URL'nin 60 dakikalik
    // suresi icinde link BIRDEN FAZLA kez kullanilabiliyordu (kullanici
    // sifreyi degistirdikten SONRA bile eski linki ele gecirmis biri tekrar
    // sifirlayabilirdi). Mevcut sifre hash'ini de karisima katarak, sifre
    // degisir degismez eski TUM linkler otomatik gecersiz olur - ayri bir
    // "kullanildi" tablosu gerekmeden tek-kullanimlik hale gelir.
    private static function hashFor(FamilyUser $family): string
    {
        return sha1($family->email.$family->password);
    }
}
