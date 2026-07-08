<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// "Bakim Danismani": aile bir ihtiyac formu doldurur, sistem uygun kurumlari
// puanlayip siralar. DURUSTLUK NOTU: facilities tablosunda yas araligi,
// cinsiyet veya "yatalak" icin ayri, yapisal alanlar yok (bkz. README >
// Veritabani yapisi). Bu yuzden bu alanlar kurumun 'services' (serbest
// metin ozellik listesi) icindeki anahtar kelime eslesmesiyle puanlanir;
// kesin bir demografik filtre degil, bir agirliklandirma/oncelik sinyalidir.
class CareAdvisorMatchService
{
    public function match(array $criteria, array $categoryScope, array $keywordWeights = []): Collection
    {
        $query = Facility::published()->forBrand($categoryScope)->with(['city', 'category', 'images']);

        if (! empty($criteria['city_id'])) {
            $query->where('city_id', $criteria['city_id']);
        }

        if (! empty($criteria['facility_category_id'])) {
            $query->where('facility_category_id', $criteria['facility_category_id']);
        }

        $facilities = $query->limit(200)->get();

        $scored = $facilities->map(function (Facility $facility) use ($criteria, $keywordWeights) {
            [$score, $reasons] = $this->score($facility, $criteria, $keywordWeights);

            return ['facility' => $facility, 'score' => $score, 'reasons' => $reasons];
        });

        return $scored->sortByDesc('score')->values();
    }

    private function score(Facility $facility, array $criteria, array $keywordWeights): array
    {
        $score = 0;
        $reasons = [];
        $services = collect($facility->services ?? [])->map(fn ($s) => mb_strtolower($s));

        foreach ($keywordWeights as $keyword => $label) {
            if ($services->contains(fn ($s) => str_contains($s, mb_strtolower($keyword)))) {
                $score += 20;
                $reasons[] = $label;
            }
        }

        if (! empty($criteria['budget_max']) && $facility->price_min) {
            if ($facility->price_min <= $criteria['budget_max']) {
                $score += 15;
                $reasons[] = 'Bütçenize uygun fiyat';
            }
        } elseif (empty($criteria['budget_max'])) {
            $score += 3;
        }

        if ($facility->is_claimed) {
            $score += 10;
            $reasons[] = 'Doğrulanmış kurum';
        }

        $score += min(10, (float) $facility->rating * 2);
        $score += $facility->is_featured ? 5 : 0;

        return [$score, array_unique($reasons)];
    }
}
