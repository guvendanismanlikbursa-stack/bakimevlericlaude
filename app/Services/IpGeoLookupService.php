<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// IP adresinden izin gerektirmeden (tarayici hicbir sey sormadan) yaklasik
// sehir tahmini yapar - GPS kadar kesin degildir ama sohbet widget'inda
// navigator.geolocation izin ekranina hic ihtiyac birakmaz. Ucretsiz
// ip-api.com ucu kullanilir, kisa timeout + try/catch ile sarilidir -
// servis coker/yavas olursa sohbet akisini asla bloklamaz.
class IpGeoLookupService
{
    public function cityFromIp(?string $ip): ?string
    {
        if (! $ip || $this->isPrivateOrLocal($ip)) {
            return null;
        }

        try {
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,city',
                'lang' => 'tr',
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('city') ?: null;
            }
        } catch (\Throwable $e) {
            Log::warning('IpGeoLookupService basarisiz', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function isPrivateOrLocal(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
