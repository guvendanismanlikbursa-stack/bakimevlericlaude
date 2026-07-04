<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Mail\FacilityEmailVerificationMail;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $user = FacilityUser::findOrFail((int) $request->route('id'));

        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->email)), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->update(['email_verified_at' => now()]);
        }

        if ($user->must_change_password) {
            return redirect(brand_route('facility.password.change'))->with('success', 'E-posta adresiniz doğrulandı. Lütfen şifrenizi güncelleyin.');
        }

        return redirect(brand_route('facility.dashboard'))->with('success', 'E-posta adresiniz doğrulandı.');
    }

    public function notice(Request $request)
    {
        $user = FacilityUser::find(session('facility_user_id'));

        if (! $user) {
            return redirect(brand_route('facility.login'))
                ->withErrors(['email' => 'E-posta doğrulama sayfasına erişmek için giriş yapmalısınız.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(brand_route('facility.dashboard'))
                ->with('success', 'E-posta adresiniz zaten doğrulandı.');
        }

        return view('themes.' . app('currentBrand')['theme'] . '.facility.verify-email-notice', [
            'user' => $user,
        ]);
    }

    public function resend(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'E-posta adresiniz zaten doğrulanmış.');
        }

        static::send($user, app('currentBrand'));

        return back()->with('success', 'Doğrulama e-postası tekrar gönderildi.');
    }

    public static function send(FacilityUser $user, array $brand): void
    {
        $params = ['id' => $user->id, 'hash' => sha1($user->email)];
        $routeName = 'facility.verify-email';

        if (request()->route('brand')) {
            $routeName = 'brand.facility.verify-email';
            $params['brand'] = request()->route('brand');
        }

        $verificationUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);

        Mail::to($user->email)->queue(new FacilityEmailVerificationMail($user, $verificationUrl, $brand['name']));
    }
}
