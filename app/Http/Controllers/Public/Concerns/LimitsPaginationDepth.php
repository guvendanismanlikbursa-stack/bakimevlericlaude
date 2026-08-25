<?php

namespace App\Http\Controllers\Public\Concerns;

use Illuminate\Http\Request;

/**
 * 25 Agustos 2026: kullanicinin bildirdigi gercek hata - erisim loglarinda
 * Googlebot'un kurum listeleme sayfalarinda 400+ sayfa derinligine
 * (offset 6000+) indigi goruldu, bu da MySQL'in buyuk gecici siralama
 * tablolari olusturmasina ve sunucunun /tmp alaninin dolup "No space left
 * on device" hatasi vermesine sebep oluyordu (ayni gun 2 kez canli hata
 * olarak yasandi). Gercek bir ziyaretci asla bu kadar derine gitmez.
 *
 * MAX_PAGE=300 kullanicinin acik talebi - "cok fazla sinirlandirip SEO'yu
 * dusurmeyelim" - cok daha dusuk bir sinir (ör. 50) daha guvenli olurdu
 * ama gercek icerigi kesme riskini azaltmak icin bilerek yuksek tutuldu.
 * Sinirin otesindeki sayfalar icin 404 doner - bu, Google'a "burada daha
 * fazla icerik yok" net sinyali verir, cezalandirici bir durum degildir.
 */
trait LimitsPaginationDepth
{
    private function abortIfPageTooDeep(Request $request, int $maxPage = 300): void
    {
        $page = (int) $request->query('page', 1);

        abort_if($page > $maxPage, 404);
    }
}
