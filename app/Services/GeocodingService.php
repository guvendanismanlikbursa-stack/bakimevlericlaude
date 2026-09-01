<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 17 Agustos 2026: kullanicinin talebi - daha once kurum koordinatlari
 * SADECE elle calistirilan toplu bir Python script + OpsController
 * geo-apply ucuyla (bkz. o script'in commit gecmisi) dolduruluyordu, bu
 * yuzden 722 kurum aylarca gercek adresi yerine il-merkezi koordinatinda
 * kalmisti. Bu servis, gercek adres bilgisinin oldugu HER an (Veri Cekici
 * onayi, kurum kayit basvurusu onayi, admin panelden elle kurum ekleme/
 * duzenleme) otomatik olarak calisir - artik periyodik toplu duzeltmeye
 * bagimli degil.
 *
 * Nominatim (OpenStreetMap) ucretsiz servisinin kullanim politikasi geregi
 * tanimlayici bir User-Agent zorunlu ve istekler tek tek (bulk degil, her
 * biri ayri bir admin islemine bagli) gonderilir - bu kullanim sekli zaten
 * politikaya uygun sikligin cok altinda kalir.
 */
class GeocodingService
{
    public function geocodeAddress(?string $address, ?string $district, ?string $cityName): ?array
    {
        if (! filled($address) || ! filled($cityName)) {
            return null;
        }

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bazi kurumlarin
        // 'address' alani (ozellikle Google Maps'ten kopyalanmis olanlar)
        // ZATEN ilce/il/posta kodu iceren TAM bir adres (ör. "...16285
        // Görükle, Nilüfer/Nilüfer/Bursa"). Bu durumda ilce+il'i SONUNA
        // TEKRAR eklemek ("...Nilüfer/Nilüfer/Bursa, Nilüfer, Bursa,
        // Türkiye") sorguyu Nominatim'in cozemeyecegi kadar tekrarli/
        // karmasik hale getirip basarisiz oluyordu (canli olayda dogrulandi:
        // HepBahar Huzurevi). Once SADECE adresin kendisi (zaten yeterince
        // tam olabilecegi varsayimiyla) denenir; basarisiz olursa eski
        // davranisa (ilce+il+Türkiye eklenmis hali) geri dusulur.
        foreach ([
            trim($address.', Türkiye'),
            trim(implode(', ', array_filter([$address, $district, $cityName, 'Türkiye']))),
        ] as $query) {
            $coords = $this->tryGeocode($query);
            if ($coords) {
                return $coords;
            }
        }

        return null;
    }

    private function tryGeocode(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name').' facility geocoder ('.config('app.url').', '.config('mail.from.address').')',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'tr',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json(0);
            if (! $result || ! isset($result['lat'], $result['lon'])) {
                return null;
            }

            return [
                'lat' => round((float) $result['lat'], 7),
                'lng' => round((float) $result['lon'], 7),
            ];
        } catch (\Throwable $e) {
            Log::warning('Otomatik adres geocoding basarisiz: '.$e->getMessage(), ['query' => $query]);

            return null;
        }
    }
}
