<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Mail\FamilyEmailVerificationMail;
use App\Models\FamilyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $family = FamilyUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), sha1($family->email)), 403);

        if (! $family->hasVerifiedEmail()) {
            $family->update(['email_verified_at' => now()]);
        }

        return redirect(brand_route('family.dashboard'))->with('success', 'E-posta adresiniz doğrulandı.');
    }

    public function notice(Request $request)
    {
        $familyId = session('family_user_id');
        $family = $familyId ? FamilyUser::find($familyId) : null;

        if (! $family) {
            return redirect(brand_route('family.login'))
                ->withErrors(['email' => 'E-posta doğrulama sayfasına erişmek için giriş yapmalısınız.']);
        }

        if ($family->hasVerifiedEmail()) {
            return redirect(brand_route('family.dashboard'))
                ->with('success', 'E-posta adresiniz zaten doğrulandı.');
        }

        return view('themes.' . app('currentBrand')['theme'] . '.family.verify-email-notice', [
            'family' => $family,
        ]);
    }

    public function resend(Request $request)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));

        if ($family->hasVerifiedEmail()) {
            return back()->with('info', 'E-posta adresiniz zaten doğrulanmış.');
        }

        static::send($family, app('currentBrand'));

        return back()->with('success', 'Doğrulama e-postası tekrar gönderildi.');
    }

    public static function send(FamilyUser $family, array $brand): void
    {
        $params = ['id' => $family->id, 'hash' => sha1($family->email)];
        $routeName = 'family.verify-email';

        // brand_route() helper ile ayni mantik: /site/{brand} test modunda
        // isek imzali route de "brand." on ekiyle uretilmeli, yoksa dogrulama
        // linki yanlis moda gidip 404 verir.
        if (request()->route('brand')) {
            $routeName = 'brand.family.verify-email';
            $params['brand'] = request()->route('brand');
        }

        $verificationUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);

        // Mail sunucusu gecici/kalici olarak reddedebilir (ör. SMTP 550); bu durumda
        // kayit/giris gibi kritik islemin 500 hatasiyla cokmesini engellemek icin
        // hata sadece loglanir, kullanici akisi kesintiye ugramaz.
        try {
            Mail::to($family->email)->queue(new FamilyEmailVerificationMail($family, $verificationUrl, $brand['name']));
        } catch (\Throwable $e) {
            Log::warning('Aile e-posta dogrulama maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
        }
    }
}
