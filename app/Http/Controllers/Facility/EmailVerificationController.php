<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Mail\FacilityEmailVerificationMail;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * $brand normalde current_brand() dizisidir (kurum panelinden tetiklenen
     * bir istekte "brand" route parametresi guvenilir baglam verir). Ancak
     * admin panelinden (Admin\FacilityClaimController::approve gibi) tetiklendiginde
     * mevcut istegin KENDI host'u/route'u hedef markayla ilgisiz olabilir; bu
     * yuzden $brand['slug'] doluysa o TEK gecerli kaynak olarak kullanilir,
     * request()->route('brand') sadece slug verilmemisse (geriye donuk
     * uyumluluk icin) baglam olarak kullanilir.
     */
    public static function send(FacilityUser $user, array $brand): void
    {
        $params = ['id' => $user->id, 'hash' => sha1($user->email)];
        $slug = $brand['slug'] ?? request()->route('brand');
        $domain = $slug ? config("brands.brands.{$slug}.domains.0") : null;
        $brandName = $brand['name'] ?? ($slug ? (config("brands.brands.{$slug}.name") ?? $slug) : 'Platform');

        if ($slug && ! app()->environment(['local', 'testing']) && $domain && str_ends_with($domain, '.com')) {
            // Hedef markanin gercek domain'ini KULLANARAK imzali link uretiyoruz;
            // aksi halde admin baska bir domain'den onayladiginda link o
            // (yanlis) domain'e cikardi.
            $previousRoot = URL::to('/');
            URL::forceRootUrl('https://'.$domain);
            $verificationUrl = URL::temporarySignedRoute('facility.verify-email', now()->addMinutes(60), $params);
            URL::forceRootUrl($previousRoot);
        } else {
            $routeName = 'facility.verify-email';
            if ($slug) {
                $routeName = 'brand.facility.verify-email';
                $params['brand'] = $slug;
            }
            $verificationUrl = URL::temporarySignedRoute($routeName, now()->addMinutes(60), $params);
        }

        try {
            Mail::to($user->email)->queue(new FacilityEmailVerificationMail($user, $verificationUrl, $brandName));
        } catch (\Throwable $e) {
            Log::warning('Kurum e-posta dogrulama maili gonderilemedi: ' . $e->getMessage(), ['facility_user_id' => $user->id]);
        }
    }
}
