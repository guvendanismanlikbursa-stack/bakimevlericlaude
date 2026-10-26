<?php

namespace App\Support;

/**
 * 26 Ekim 2026: kullanıcının talebi - "Sitelere giriş sayıları neden arttı? Bot mu insan mı?"
 * Sunucunun erişim kayıtlarından (cPanel access-logs: güncel kayıt + bu ayın arşivi) bir günün
 * SAYFA isteklerini (resim/css/js hariç) her site için ziyaretçi türüne göre ayırır.
 *
 * Türler:
 *  - insan      : tarayıcı kimliği + aynı gün aynı IP sayfanın resim/stil dosyalarını da yüklemiş (muhtemel gerçek kişi)
 *  - supheli    : tarayıcı kimliği ama yalnızca sayfa metnini çekmiş (tarayıcı taklidi yapan bot olabilir)
 *  - googlebot, bing, yz (yapay zekâ botları), diger_bot (diğer bot / araç)
 * Not: kayıt dosyaları kısa süre tutulur; bu yüzden sonuçlar günlük olarak trafik_gunluk tablosuna yazılır.
 */
class TrafikAnaliz
{
    /** Marka => erişim kaydı adı (eklenti alan adları sunucuda alt alan adı olarak kaydedilir). */
    public const SITELER = [
        'bakimevleri' => 'bakimevleri.com',
        'bakimevibul' => 'bakimevibul.bakimevleri.com',
        'bakimeviara' => 'bakimeviara.bakimevleri.com',
    ];

    public const TURLER = [
        'insan' => 'Gerçek kişi (muhtemel)',
        'supheli' => 'Şüpheli tarayıcı',
        'googlebot' => 'Googlebot',
        'bing' => 'Bing',
        'yz' => 'Yapay zekâ botları',
        'diger_bot' => 'Diğer botlar',
    ];

    private const AYLAR = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];

    /** Testlerde örnek kayıt klasörü vermek için. */
    public static ?string $klasor = null;

    public static function kayitKlasoru(): string
    {
        return self::$klasor ?? dirname(base_path()).'/access-logs';
    }

    /**
     * Verilen günler için sonuç: [tarih => [marka => [tür => ['istek' => n, 'ip' => n]]]].
     * Kayıtta hiç satırı olmayan gün sonuçta yer almaz.
     *
     * @param  array<int, string>  $tarihler  Y-m-d
     */
    public static function gunler(array $tarihler): array
    {
        $istenen = array_flip($tarihler);
        $sonuc = [];
        foreach (self::SITELER as $marka => $kayitAdi) {
            $dosyalar = array_values(array_filter(
                array_merge(glob(self::kayitKlasoru().'/'.$kayitAdi.'-ssl_log-*.gz') ?: [], [self::kayitKlasoru().'/'.$kayitAdi.'-ssl_log']),
                fn ($d) => is_file($d) && is_readable($d)
            ));
            $sayfa = [];   // [tarih][ip][tür] = istek
            $varlik = [];  // [tarih][ip] = true (resim/stil/js yükledi)
            $goruldu = []; // aynı satır hem arşivde hem güncel kayıtta olabilir
            foreach ($dosyalar as $dosya) {
                $gz = str_ends_with($dosya, '.gz');
                $fh = $gz ? @gzopen($dosya, 'r') : @fopen($dosya, 'r');
                if (! $fh) {
                    continue;
                }
                while (($satir = $gz ? gzgets($fh) : fgets($fh)) !== false) {
                    if (! preg_match('#^(\S+) \S+ \S+ \[(\d{2})/(\w{3})/(\d{4}):[^\]]+\] "(GET|HEAD) (\S+)[^"]*" (\d{3}) \S+ "[^"]*" "([^"]*)"#', $satir, $m)) {
                        continue;
                    }
                    $tarih = sprintf('%04d-%02d-%02d', $m[4], self::AYLAR[$m[3]] ?? 0, $m[2]);
                    if (! isset($istenen[$tarih])) {
                        continue;
                    }
                    $iz = md5($satir);
                    if (isset($goruldu[$tarih][$iz])) {
                        continue;
                    }
                    $goruldu[$tarih][$iz] = true;
                    $ip = $m[1];
                    if (preg_match('#^/(build|storage|kucuk-resim|images)/|\.(css|js|webp|png|jpe?g|gif|svg|ico|woff2?|ttf)(\?|$)#i', $m[6])) {
                        $varlik[$tarih][$ip] = true;
                        continue;
                    }
                    if (preg_match('#^/(\.well-known|_ops|_internal|up$)|\.(map|txt|xml|json|mp4)(\?|$)#i', $m[6])) {
                        continue;
                    }
                    $tur = self::tur($m[8]);
                    $sayfa[$tarih][$ip][$tur] = ($sayfa[$tarih][$ip][$tur] ?? 0) + 1;
                }
                $gz ? gzclose($fh) : fclose($fh);
            }
            foreach ($sayfa as $tarih => $ipler) {
                $ozet = array_fill_keys(array_keys(self::TURLER), ['istek' => 0, 'ip' => 0]);
                foreach ($ipler as $ip => $turler) {
                    foreach ($turler as $tur => $adet) {
                        if ($tur === 'tarayici') {
                            $tur = isset($varlik[$tarih][$ip]) ? 'insan' : 'supheli';
                        }
                        $ozet[$tur]['istek'] += $adet;
                        $ozet[$tur]['ip']++;
                    }
                }
                $sonuc[$tarih][$marka] = $ozet;
            }
        }
        ksort($sonuc);

        return $sonuc;
    }

    public static function tur(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/Googlebot|Google-InspectionTool|GoogleOther|AdsBot-Google|Storebot-Google|Google-Extended|APIs-Google|Mediapartners-Google/i', $ua) => 'googlebot',
            (bool) preg_match('/bingbot|BingPreview|msnbot/i', $ua) => 'bing',
            (bool) preg_match('/GPTBot|ChatGPT|OAI-SearchBot|ClaudeBot|Claude-|anthropic|PerplexityBot|Perplexity-|Bytespider|CCBot|Amazonbot|meta-externalagent|meta-externalfetcher|Applebot|cohere|Diffbot|YouBot|DuckAssistBot/i', $ua) => 'yz',
            $ua === '' || $ua === '-' || (bool) preg_match('/bot|crawl|spider|slurp|python|curl|wget|Go-http|HeadlessChrome|PhantomJS|Cpanel-HTTP|okhttp|java\/|scrapy|axios|node-fetch|undici|httpclient|libwww|facebookexternalhit|WhatsApp|Telegram|preview|monitor|lighthouse|pagespeed|BakimCacheWarm/i', $ua) => 'diger_bot',
            default => 'tarayici',
        };
    }
}
