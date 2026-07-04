<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;

/**
 * Admin panelde "Sitelere Giris Sayilari" icin: her tarayici oturumunda,
 * markaya ilk girişte gunluk sayaci 1 artirir. Sayfa yenilemelerinde veya
 * ayni oturum icinde tekrar tekrar sayilmasini engellemek icin session
 * flag kullanilir (kaba ama makul bir "gunluk benzersiz ziyaret" yaklaşimi).
 */
class TrackSiteVisit
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->bound('currentBrand') && $request->isMethod('get')) {
            $brand = app('currentBrand')['slug'];
            $flagKey = "site_visit_counted:{$brand}:".now()->toDateString();

            if (! session()->has($flagKey)) {
                SiteVisit::recordVisit($brand);
                session([$flagKey => true]);
            }
        }

        return $next($request);
    }
}
