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

        abort_unless(hash_equals((string) $request->route('hash'), sha1($family->email)), 403);

        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.password-reset", compact('family'));
    }

    public function reset(Request $request)
    {
        $family = FamilyUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), sha1($family->email)), 403);

        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $family->update(['password' => Hash::make($data['password'])]);

        return redirect(brand_route('family.login'))->with('success', 'Şifreniz güncellendi, şimdi giriş yapabilirsiniz.');
    }

    public static function send(FamilyUser $family, array $brand): void
    {
        $params = ['id' => $family->id, 'hash' => sha1($family->email)];
        $routeName = 'family.password.reset';

        if (request()->route('brand')) {
            $routeName = 'brand.family.password.reset';
            $params['brand'] = request()->route('brand');
        }

        $resetUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);

        try {
            Mail::to($family->email)->queue(new FamilyPasswordResetMail($family, $resetUrl, $brand['name']));
        } catch (\Throwable $e) {
            Log::warning('Aile sifre sifirlama maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
        }
    }
}
