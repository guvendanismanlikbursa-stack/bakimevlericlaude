<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Public\KurumSitesiController;
use App\Models\FacilityWebsite;
use Closure;
use Illuminate\Http\Request;

// 23 Ekim 2026: kullanıcının talebi - kurum alan adı alınca site oraya
// taşınır. Alan adı cPanel'de ana sitenin klasörüne yönlendirildiğinde
// istekler bu uygulamaya gelir; platformun kendi alan adlarından biri
// değilse ve yayındaki bir kurum sitesine tanımlıysa, ana sayfada kurum
// sitesi gösterilir, iletişim formu işlenir, diğer adresler ana sayfaya
// döner (küçük resim uçları gibi gereken adresler hariç).
class ServeFacilityWebsiteDomain
{
    private const GECIS = ['kucuk-resim/*', 'storage/*', 'build/*', 'up', '_ops/*', '_internal/*'];

    public function handle(Request $request, Closure $next)
    {
        $host = FacilityWebsite::normalizeDomain($request->getHost());
        if (! $host || $this->isPlatformHost($host)) {
            return $next($request);
        }

        $siteId = FacilityWebsite::liveDomainMap()[$host] ?? null;
        if (! $siteId) {
            return $next($request);
        }
        foreach (self::GECIS as $desen) {
            if ($request->is($desen)) {
                return $next($request);
            }
        }

        $site = FacilityWebsite::with('facility')->find($siteId);
        if (! $site || ! $site->facility || ! $site->isLive()) {
            return $next($request);
        }
        $controller = app(KurumSitesiController::class);

        if ($request->isMethod('GET') && $request->path() === '/') {
            return app()->call([$controller, 'showForDomain'], ['site' => $site]);
        }
        // Web grubunun sonuna eklendiği için oturum ve CSRF doğrulaması bu noktada yapılmış olur.
        if ($request->isMethod('POST') && $request->is('iletisim')) {
            return app()->call([$controller, 'contactForDomain'], ['request' => $request, 'site' => $site]);
        }

        return redirect('/', 301);
    }

    private function isPlatformHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return true;
        }
        foreach (config('brands.brands', []) as $brand) {
            foreach ($brand['domains'] ?? [] as $d) {
                if (FacilityWebsite::normalizeDomain($d) === $host) {
                    return true;
                }
            }
        }

        return false;
    }
}
