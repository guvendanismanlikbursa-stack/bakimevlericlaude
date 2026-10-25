<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Public\DoktorSitesiController;
use App\Http\Controllers\Public\KurumSitesiController;
use App\Models\ExpertWebsite;
use App\Models\FacilityWebsite;
use Closure;
use Illuminate\Http\Request;

// 23 Ekim 2026: kullanıcının talebi - kurum (ve doktor) alan adı alınca site
// oraya taşınır. Alan adı cPanel'de ana sitenin klasörüne yönlendirildiğinde
// istekler bu uygulamaya gelir; platformun kendi alan adlarından biri
// değilse ve yayındaki bir kurum ya da doktor sitesine tanımlıysa, ana
// sayfada o site gösterilir, iletişim formu işlenir, diğer adresler ana
// sayfaya döner (küçük resim uçları gibi gereken adresler hariç).
class ServeFacilityWebsiteDomain
{
    private const GECIS = ['kucuk-resim/*', 'storage/*', 'build/*', 'up', '_ops/*', '_internal/*'];

    public function handle(Request $request, Closure $next)
    {
        $host = FacilityWebsite::normalizeDomain($request->getHost());
        if (! $host || $this->isPlatformHost($host)) {
            return $next($request);
        }

        $kurumId = FacilityWebsite::liveDomainMap()[$host] ?? null;
        $doktorId = $kurumId ? null : (ExpertWebsite::liveDomainMap()[$host] ?? null);
        if (! $kurumId && ! $doktorId) {
            return $next($request);
        }
        foreach (self::GECIS as $desen) {
            if ($request->is($desen)) {
                return $next($request);
            }
        }

        if ($kurumId) {
            $site = FacilityWebsite::with('facility')->find($kurumId);
            $hazir = $site && $site->facility && $site->isLive();
            $controller = KurumSitesiController::class;
        } else {
            $site = ExpertWebsite::with('expert')->find($doktorId);
            $hazir = $site && $site->expert && $site->isLive();
            $controller = DoktorSitesiController::class;
        }
        if (! $hazir) {
            return $next($request);
        }
        $controller = app($controller);

        if ($request->isMethod('GET') && $request->path() === '/') {
            return app()->call([$controller, 'showForDomain'], ['site' => $site]);
        }
        // 25 Ekim 2026: kendi alan adlı sitenin arama motoru dosyaları.
        if ($request->isMethod('GET') && $request->path() === 'robots.txt') {
            return response("User-agent: *\nAllow: /\n\nSitemap: https://".$host."/sitemap.xml\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }
        if ($request->isMethod('GET') && $request->path() === 'sitemap.xml') {
            $sayfalar = $kurumId ? $controller->sayfaListesi($site) : [];
            $tarih = optional($site->updated_at)->toAtomString() ?: now()->toAtomString();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach (array_merge([''], $sayfalar) as $s) {
                $xml .= '<url><loc>https://'.$host.'/'.$s.'</loc><lastmod>'.$tarih.'</lastmod><changefreq>weekly</changefreq><priority>'.($s === '' ? '1.0' : '0.7').'</priority></url>';
            }

            return response($xml.'</urlset>', 200)->header('Content-Type', 'application/xml; charset=UTF-8');
        }
        // Web grubunun sonuna eklendiği için oturum ve CSRF doğrulaması bu noktada yapılmış olur.
        if ($request->isMethod('POST') && $request->is('iletisim')) {
            return app()->call([$controller, 'contactForDomain'], ['request' => $request, 'site' => $site]);
        }
        // Kurum sitesi: Profesyonel Site iç sayfaları ve tıklama sayacı.
        if ($kurumId && $request->isMethod('GET') && array_key_exists($request->path(), FacilityWebsite::PRO_PAGES)) {
            return app()->call([$controller, 'showForDomain'], ['request' => $request, 'site' => $site, 'sayfa' => $request->path()]);
        }
        if (! $kurumId && $request->isMethod('POST') && $request->is('olay')) {
            return app()->call([$controller, 'eventForDomain'], ['request' => $request, 'site' => $site]);
        }
        if ($kurumId && $request->isMethod('POST') && $request->is('olay')) {
            return app()->call([$controller, 'eventForDomain'], ['request' => $request, 'site' => $site]);
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
