<?php

namespace App\Services;

// Enlem/boylamdan en yakin il merkezini bulan ortak servis.
// "Yakinimdaki Kurumlar" (NearbyController) ve aile kaydinda konum
// yakalama (Family\AuthController) tarafindan paylasilir.
class GeoLookupService
{
    /**
     * @return array{city: string, distance_km: float}|null
     */
    public function nearestCity(float $lat, float $lng): ?array
    {
        $centroids = config('turkiye_centroids');
        $nearestCity = null;
        $nearestDistance = null;

        foreach ($centroids as $cityName => [$centroidLat, $centroidLng]) {
            $distance = $this->haversine($lat, $lng, $centroidLat, $centroidLng);
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestCity = $cityName;
            }
        }

        if (! $nearestCity) {
            return null;
        }

        return ['city' => $nearestCity, 'distance_km' => $nearestDistance];
    }

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * $facilities icinden gercek lat/lng'i olanlari mesafeye gore siralar.
     * Koordinati olmayan kurumlar (veri cekici henuz toplamadigi icin
     * cogunlukla bos) bu listeye hic girmez — il-merkezi yaklasikligiyla
     * karistirilmaz, sadece gercek konumu bilinenler "yakinimda" sayilir.
     *
     * @param  iterable<\App\Models\Facility>  $facilities
     * @return array<int, array{facility: \App\Models\Facility, distance_km: float}>
     */
    public function nearestFacilities(iterable $facilities, float $lat, float $lng, int $limit = 6, float $maxKm = 100): array
    {
        $results = [];

        foreach ($facilities as $facility) {
            if ($facility->lat === null || $facility->lng === null) {
                continue;
            }

            $distance = $this->haversine($lat, $lng, (float) $facility->lat, (float) $facility->lng);

            if ($distance <= $maxKm) {
                $results[] = ['facility' => $facility, 'distance_km' => $distance];
            }
        }

        usort($results, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return array_slice($results, 0, $limit);
    }
}
