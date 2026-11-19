<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * 19 Kasım 2026: kullanıcının kararı - kendi çerezsiz giriş sayacımız.
 * Ziyaretçi siteye DIŞARIDAN girdiği anda (arama motoru, doğrudan adres, başka site) sayfadaki küçük betik
 * bir kez haber verir. Çerez/yerel kayıt kullanılmaz, kişi tanınmaz; yalnızca saat + site + kaynak sayısı tutulur.
 * Analytics çerez reddi ve 3 sn gecikmeli yükleme yüzünden bu girişlerin bir kısmını göremiyor.
 */
class GirisSayaci
{
    public const ARAMA_MOTORLARI = '/(^|\\.)(google|bing|yandex|duckduckgo|yahoo|ecosia|qwant|brave|search\\.yahoo|msn)\\./i';

    public static function kaynak(?string $referansHost): string
    {
        $h = strtolower(trim((string) $referansHost));
        if ($h === '') {
            return 'dogrudan';
        }

        return preg_match(self::ARAMA_MOTORLARI, $h) ? 'arama' : 'diger';
    }

    public static function kaydet(string $site, string $kaynak): void
    {
        DB::table('giris_sayaci')->upsert(
            [['saat' => now()->format('YmdH'), 'site' => $site, 'kaynak' => $kaynak, 'adet' => 1]],
            ['saat', 'site', 'kaynak'],
            ['adet' => DB::raw('adet + 1')]
        );
    }

    /** @return array<string, array{toplam:int, arama:int, dogrudan:int, diger:int}> site => sayılar */
    public static function aralik(string $bas, string $son): array
    {
        $sonuc = [];
        foreach (DB::table('giris_sayaci')->whereBetween('saat', [$bas, $son])->get() as $r) {
            $sonuc[$r->site] ??= ['toplam' => 0, 'arama' => 0, 'dogrudan' => 0, 'diger' => 0];
            $sonuc[$r->site][$r->kaynak] = ($sonuc[$r->site][$r->kaynak] ?? 0) + $r->adet;
            $sonuc[$r->site]['toplam'] += $r->adet;
        }

        return $sonuc;
    }

    public static function ilkSaat(): ?string
    {
        return DB::table('giris_sayaci')->min('saat');
    }
}
