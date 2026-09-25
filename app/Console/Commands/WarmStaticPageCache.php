<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Sabit sayfalarin (rehber, fiyat rehberi, SSS...) onbellek/son-iyi-kopyasini onceden doldurur:
// her marka icin sitemap.xml'deki URL'leri kucuk partiler halinde (imlec ile donerek) ziyaret eder.
// Kurum detay/liste sayfalari ATLANIR (goruntulenme sayaclarini sisirmesin diye).
class WarmStaticPageCache extends Command
{
    protected $signature = 'pages:warm-cache {--per-brand=40}';

    protected $description = 'Sabit sayfalarin onbellegini sitemap uzerinden partiler halinde onceden doldurur';

    private const DOMAINS = ['bakimevleri.com', 'bakimevibul.com', 'bakimeviara.com'];

    private const ALLOWED_PREFIXES = ['/rehber', '/fiyat-rehberi', '/bakim-rehberi', '/sayfa', '/sss', '/istatistikler', '/dogrulanmis-kurumlar', '/yeni-eklenen-kurumlar', '/son-guncellenen-kurumlar', '/son-sahiplenilen-kurumlar', '/en-cok-goruntulenen-kurumlar', '/en-cok-aranan-bolgeler', '/son-eklenen-fotograflar'];

    public function handle(): int
    {
        $perBrand = max(1, (int) $this->option('per-brand'));
        $deadline = time() + 150;
        $done = 0;

        // Her site kurulumu (3 ayri kod klasoru) kendi gorevini calistirir: sadece KENDI alan adini isitir.
        $own = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $own = preg_replace('/^www\./', '', $own);
        $domains = in_array($own, self::DOMAINS, true) ? [$own] : self::DOMAINS;

        foreach ($domains as $domain) {
            $listKey = 'warm-cache-urls:'.$domain;
            $urls = Cache::get($listKey);
            if (! is_array($urls)) {
                try {
                    $sitemap = Http::withUserAgent('BakimCacheWarm')->connectTimeout(15)->timeout(45)->get("https://{$domain}/sitemap.xml");
                } catch (\Throwable $e) {
                    $this->warn("{$domain}: sitemap alinamadi ({$e->getMessage()}) - bir sonraki turda tekrar denenecek");
                    continue;
                }
                if (! $sitemap->successful()) {
                    $this->warn("{$domain}: sitemap alinamadi ({$sitemap->status()})");
                    continue;
                }
                preg_match_all('#<loc>([^<]+)</loc>#', $sitemap->body(), $m);
                $urls = array_values(array_filter($m[1], function ($u) {
                    $path = (string) parse_url(html_entity_decode($u), PHP_URL_PATH);
                    foreach (self::ALLOWED_PREFIXES as $prefix) {
                        if (str_starts_with($path, $prefix)) {
                            return true;
                        }
                    }

                    return $path === '' || $path === '/';
                }));
                if ($urls !== []) {
                    Cache::put($listKey, $urls, now()->addHours(6));
                }
            }
            if ($urls === []) {
                continue;
            }

            $cursorKey = 'warm-cache-cursor:'.$domain;
            $cursor = (int) Cache::get($cursorKey, 0) % count($urls);
            for ($i = 0; $i < $perBrand; $i++) {
                if (time() > $deadline) {
                    break 2;
                }
                $url = html_entity_decode($urls[($cursor + $i) % count($urls)]);
                try {
                    $r = Http::withUserAgent('BakimCacheWarm')->connectTimeout(15)->timeout(30)->get($url);
                } catch (\Throwable $e) {
                    $this->warn('Istek basarisiz, tur durduruldu: '.$e->getMessage());
                    break 2;
                }
                if ($r->serverError()) {
                    $this->warn("{$url} -> {$r->status()}, tur durduruldu (veritabani sorunu olabilir)");
                    break 2;
                }
                $done++;
                usleep(300000);
            }
            Cache::forever($cursorKey, $cursor + $perBrand);
        }

        $this->info("Isitilan sayfa: {$done}");

        return self::SUCCESS;
    }
}
