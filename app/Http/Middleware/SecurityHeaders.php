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

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
