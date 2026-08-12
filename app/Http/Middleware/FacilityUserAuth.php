<?php

namespace App\Http\Middleware;

use App\Models\FacilityUser;
use Closure;
use Illuminate\Http\Request;

class FacilityUserAuth
{
    public function handle(Request $request, Closure $next)
    {
        $userId = session('facility_user_id');
        $user = $userId ? FacilityUser::find($userId) : null;

        if (! $user || $user->status !== 'active') {
            $request->session()->forget(['facility_user_id', 'facility_user_name']);

            return redirect(brand_route('facility.login'))
                ->withErrors(['email' => 'Kurum panelini yalnızca admin tarafından onaylanmış kayıtlı kurum yetkilileri görebilir.']);
        }

        // 30 Temmuz 2026: kurumu silinmis/ön kayıtlıya döndürülmüş ama kendisi
        // (eski bir veri tutarsizligi yuzunden) hala 'active' kalmis bir
        // kurum yetkilisi buraya kadar gelebiliyordu - $user->facility null
        // donunce DashboardController 500 ile cokuyordu ("isInBrandScope()
        // on null"). Boyle bir hesabi burada yakalayip temiz bir mesajla
        // disari cikariyoruz, ayrica bir daha ayni hatayi vermemesi icin
        // kalici olarak askiya aliyoruz (destroy()'daki suspend deseniyle
        // ayni mantik).
        if (! $user->facility) {
            $user->update(['status' => 'suspended']);
            $request->session()->forget(['facility_user_id', 'facility_user_name']);

            return redirect(brand_route('facility.login'))
                ->withErrors(['email' => 'Bu hesaba bağlı kurum artık mevcut değil, lütfen yönetici ile iletişime geçin.']);
        }

        // 30 Temmuz 2026: sahiplenme/kayit onayinda gecici sifreyle acilan
        // hesap hem must_change_password=true HEM email_verified_at=null
        // olarak olusuyor (bkz. Admin\FacilityClaimController::approve()).
        // Bu iki route'u (sifre-degistir) e-posta dogrulama zorunlulugunun
        // DISINDA tutmazsak, dogrulama linkine (ayri bir mail, kolayca
        // gozden kacabilir) henuz tiklamamis bir kullanici "yeni sifre
        // olustur" ekranina ASLA ulasamiyor - giris yapiyor, dogrudan
        // e-posta dogrulama sayfasina dusuyor, hesabina hic giremiyor.
        $exemptRoutes = ['facility.password.change', 'facility.password.update', 'brand.facility.password.change', 'brand.facility.password.update'];
        if (! $user->hasVerifiedEmail() && ! in_array($request->route()?->getName(), $exemptRoutes, true)) {
            return redirect(brand_route('facility.verify-email.notice'));
        }

        return $next($request);
    }
}