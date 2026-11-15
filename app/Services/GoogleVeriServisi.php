<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * 15 Kasım 2026: Google Analytics (GA4) ve Search Console verilerini admin paneline çeker.
 * Kullanıcının kararı: panelde ziyaret rakamları bundan sonra Analytics'e göre gösterilir.
 * Kimlik: hizmet hesabı (seo-590@bakimplatform...), Analytics mülkünde Görüntüleyici, Search Console'da tam kullanıcı.
 * Sonuçlar gün sonuna kadar önbellekte tutulur (sunucuya ve Google kotasına yük binmesin).
 */
class GoogleVeriServisi
{
    public const SITELER = ['bakimevleri.com', 'bakimevibul.com', 'bakimeviara.com'];

    public function hazirMi(): bool
    {
        return filled(config('services.google_analytics.property_id')) && is_file($this->kimlikYolu());
    }

    private function kimlikYolu(): string
    {
        return config('services.google_analytics.credentials') ?: storage_path('app/private/google/service_account.json');
    }

    private function http()
    {
        $ca = config('services.google_analytics.ca_bundle');

        return Http::timeout(30)->withOptions(['verify' => $ca ?: true]);
    }

    private function token(string $kapsam): string
    {
        return Cache::remember('google_token_'.md5($kapsam), 3000, function () use ($kapsam) {
            $sa = json_decode((string) file_get_contents($this->kimlikYolu()), true);
            $b64 = fn ($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $simdi = time();
            $jwt = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode([
                'iss' => $sa['client_email'], 'scope' => $kapsam, 'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $simdi, 'exp' => $simdi + 3600,
            ]));
            openssl_sign($jwt, $imza, $sa['private_key'], 'sha256');
            $yanit = $this->http()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt.'.'.$b64($imza),
            ]);
            if (! $yanit->ok()) {
                throw new \RuntimeException('Google giriş hatası: '.$yanit->status().' '.mb_substr($yanit->body(), 0, 200));
            }

            return $yanit->json('access_token');
        });
    }

    /** GA4 runReport; satırları [boyutlar..., metrikler...] dizisi olarak döner. */
    public function rapor(array $istek): array
    {
        $yanit = $this->http()->withToken($this->token('https://www.googleapis.com/auth/analytics.readonly'))
            ->post('https://analyticsdata.googleapis.com/v1beta/properties/'.config('services.google_analytics.property_id').':runReport', $istek);
        if (! $yanit->ok()) {
            throw new \RuntimeException('Analytics hatası: '.$yanit->status().' '.mb_substr($yanit->body(), 0, 200));
        }
        $satirlar = [];
        foreach ($yanit->json('rows') ?? [] as $r) {
            $satirlar[] = array_merge(
                array_map(fn ($d) => $d['value'], $r['dimensionValues'] ?? []),
                array_map(fn ($m) => (float) $m['value'], $r['metricValues'] ?? [])
            );
        }

        return $satirlar;
    }

    /** Search Console arama verisi (site: bakimevleri.com gibi). */
    public function aramaKonsolu(string $site, string $bas, string $son, array $boyutlar = [], int $limit = 25): array
    {
        $yanit = $this->http()->withToken($this->token('https://www.googleapis.com/auth/webmasters.readonly'))
            ->post('https://www.googleapis.com/webmasters/v3/sites/'.urlencode('sc-domain:'.$site).'/searchAnalytics/query', array_filter([
                'startDate' => $bas, 'endDate' => $son, 'dimensions' => $boyutlar, 'rowLimit' => $boyutlar ? $limit : null,
            ]));
        if (! $yanit->ok()) {
            throw new \RuntimeException('Search Console hatası: '.$yanit->status().' '.mb_substr($yanit->body(), 0, 200));
        }

        return $yanit->json('rows') ?? [];
    }

    /** Aynı gün içinde tekrar Google'a gitmez; ?yenile ile temizlenir. */
    public function onbellekli(string $anahtar, \Closure $fn, bool $yenile = false)
    {
        $anahtar = 'google_veri_'.now()->toDateString().'_'.md5($anahtar);
        if ($yenile) {
            Cache::forget($anahtar);
        }

        return Cache::remember($anahtar, now()->endOfDay(), $fn);
    }

    /** Site filtresi (hostName); 'hepsi' ise filtre yok. */
    public static function siteFiltresi(string $site): ?array
    {
        if (! in_array($site, self::SITELER, true)) {
            return null;
        }

        return ['filter' => ['fieldName' => 'hostName', 'inListFilter' => ['values' => [$site, 'www.'.$site]]]];
    }
}
