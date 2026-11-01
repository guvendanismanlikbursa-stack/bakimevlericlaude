<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 1 Kasım 2026: kullanıcının talebi - veri kazıyan şüpheli botları siteye sokmamak.
 * Yalnızca herkese açık site sayfalarında çalışır (admin ve kurum paneli etkilenmez):
 *  1) Engelli ağlar (config/bot_kalkani.php): kazıyıcıların kiraladığı sunucu ağları → 403.
 *  2) IP başına hız sınırı: 10 dakikada ip_siniri'nden fazla sayfa → ceza_saniye boyunca 429.
 *  3) Ağ (/24) başına hız sınırı: 10 dakikada ag_siniri'nden fazla sayfa → aynı ceza.
 * Arama motorları ve bağlantı önizleme botları hiç sınırlanmaz. Her engel bot_engel_kayitlari
 * tablosuna yazılır; admin "Site İstatistikleri" sayfasında saldırılar görünmeye devam eder.
 */
class BotKalkani
{
    private const METIN_403 = 'Erişim engellendi.';

    private const METIN_429 = 'Çok fazla istek gönderildi. Lütfen biraz sonra tekrar deneyin.';

    // Ayar dosyası okunamazsa (ör. eski ayar önbelleği) kullanılacak güvenli varsayılanlar.
    private const IYI_BOTLAR = '/Googlebot|Google-InspectionTool|GoogleOther|AdsBot-Google|Storebot-Google|Mediapartners-Google|bingbot|BingPreview|YandexBot|DuckDuckBot|Applebot|facebookexternalhit|meta-externalagent|WhatsApp|Twitterbot|LinkedInBot|TelegramBot|Slackbot|Discordbot|UptimeRobot|BakimCacheWarm/i';

    private const VARSAYILAN = ['ip_siniri' => 150, 'ag_siniri' => 600, 'ceza_saniye' => 3600];

    private static function ayar(string $k): int
    {
        return (int) (config("bot_kalkani.$k") ?: self::VARSAYILAN[$k]);
    }

    public function handle(Request $request, Closure $next)
    {
        if (! config('bot_kalkani.acik', true) || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }
        $ip = (string) $request->ip();
        if ($ip === '' || preg_match((string) (config('bot_kalkani.iyi_botlar') ?: self::IYI_BOTLAR), (string) $request->userAgent())) {
            return $next($request);
        }

        if ($ad = self::engelliAg($ip)) {
            self::kaydet($ip.' · '.$ad, 'liste');

            return response(self::METIN_403, 403);
        }

        $ag = self::ag($ip);
        foreach (['ip' => $ip, 'ag' => $ag] as $tur => $anahtar) {
            if ($neden = Cache::get("bk:ceza:$tur:$anahtar")) {
                self::kaydet($anahtar, $neden);

                return response(self::METIN_429, 429, ['Retry-After' => (string) self::ayar('ceza_saniye')]);
            }
        }

        // Sayfa içi arka plan istekleri (arama önerileri vb.) sayılmaz; yalnızca sayfa açılışları.
        if (! $request->expectsJson() && ! $request->ajax()) {
            $kova = intdiv(time(), 600);
            foreach (['ip' => [$ip, 'ip_siniri', 'hiz-ip'], 'ag' => [$ag, 'ag_siniri', 'hiz-ag']] as $tur => [$anahtar, $sinir, $neden]) {
                if (self::say("bk:$kova:$tur:$anahtar") > self::ayar($sinir)) {
                    Cache::put("bk:ceza:$tur:$anahtar", $neden, self::ayar('ceza_saniye'));
                    self::kaydet($anahtar, $neden);

                    return response(self::METIN_429, 429, ['Retry-After' => (string) self::ayar('ceza_saniye')]);
                }
            }
        }

        return $next($request);
    }

    /** IP engelli ağlardan birindeyse ağın adını döndürür. */
    public static function engelliAg(string $ip): ?string
    {
        $v4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? ip2long($ip) : null;
        $ikili = $v4 === null ? @inet_pton($ip) : null;
        foreach (array_merge((array) config('bot_kalkani.engelli_aglar', []), \App\Support\BotGozcu::dbListesi()) as $grup) {
            if ($v4 !== null) {
                foreach ($grup['v4'] ?? [] as [$bas, $son]) {
                    if ($v4 >= $bas && $v4 <= $son) {
                        return $grup['ad'];
                    }
                }
            } elseif ($ikili !== false && $ikili !== null) {
                foreach ($grup['v6'] ?? [] as $cidr) {
                    [$net, $bit] = explode('/', $cidr);
                    $n = @inet_pton($net);
                    if ($n === false || strlen($n) !== strlen($ikili)) {
                        continue;
                    }
                    $tam = intdiv((int) $bit, 8);
                    $kalan = (int) $bit % 8;
                    if (substr($ikili, 0, $tam) !== substr($n, 0, $tam)) {
                        continue;
                    }
                    if ($kalan === 0 || ((ord($ikili[$tam]) ^ ord($n[$tam])) & (0xFF << (8 - $kalan)) & 0xFF) === 0) {
                        return $grup['ad'];
                    }
                }
            }
        }

        return null;
    }

    /** IPv4 için /24, IPv6 için /64 ağı. */
    public static function ag(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0/24', $ip);
        }
        $b = @inet_pton($ip);

        return $b === false ? $ip : inet_ntop(substr($b, 0, 8).str_repeat("\0", 8)).'/64';
    }

    private static function say(string $anahtar): int
    {
        Cache::add($anahtar, 0, 660);

        return (int) Cache::increment($anahtar);
    }

    /** Engeli günlük özete yazar; yazılamazsa sessizce geçer (sayfa hiçbir zaman bu yüzden bozulmaz). */
    private static function kaydet(string $anahtar, string $neden): void
    {
        try {
            $simdi = now();
            DB::table('bot_engel_kayitlari')->upsert(
                [['tarih' => $simdi->toDateString(), 'marka' => app()->bound('currentBrand') ? (string) app('currentBrand')['slug'] : '',
                    'anahtar' => mb_substr($anahtar, 0, 64), 'neden' => $neden, 'sayi' => 1, 'son_gorulme' => $simdi, 'created_at' => $simdi, 'updated_at' => $simdi]],
                ['tarih', 'marka', 'anahtar', 'neden'],
                ['sayi' => DB::raw('sayi + 1'), 'son_gorulme' => $simdi, 'updated_at' => $simdi]
            );
        } catch (\Throwable $e) {
            // ikincil kayıt; sessizce geç
        }
    }
}
