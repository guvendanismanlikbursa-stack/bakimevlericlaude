<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// 28 Temmuz 2026: guvenlik taramasinda tarayici-seviyesi koruma basliklarinin
// (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS) hicbirinin
// gonderilmedigi bulundu. Content-Security-Policy BILEREK eklenmedi - bu proje
// genelinde yogun sekilde inline <script>/style="..." kullaniliyor (nonce'suz),
// siki bir CSP bunlarin tumunu susturup siteyi (filtreler, sohbet widget'i,
// scroll-restore vb.) calismaz hale getirirdi. Permissions-Policy de bilerek
// eklenmedi - "Yakinimdaki Kurumlar" ozelligi tarayici geolocation izni
// istiyor, yanlis yazilmis bir politika o ozelligi sessizce bozabilirdi.
// Burada sadece hicbir mevcut ozelligi riske atmayan, dusuk riskli 4 baslik var.
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 4 Ekim 2026: guvenlik denetimi - PHP'nin kendi gonderdigi
        // "X-Powered-By: PHP/x.y.z" basligi saldirgana tam surum numarasini
        // (hedefli CVE aramasi icin) hediye ediyordu, hicbir islevsel
        // faydasi yok. Bu baslik PHP'nin SAPI katmaninda dogrudan
        // header()/header_remove() ile eklenir, Symfony'nin ResponseHeaderBag'i
        // (kendi EKLEMEDIGI bir basligi) remove() ile silemez - bu yuzden
        // native PHP fonksiyonu sart. php.ini'ye erisim olmadigi icin
        // (paylasimli hosting, expose_php kapatilamiyor) burada kaldiriliyor.
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if (config('app.bekleme')) {
            // 26 Ekim 2026: yedek (bekleme) sunucusu Google'a kapalı - bkz. config('app.bekleme').
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
