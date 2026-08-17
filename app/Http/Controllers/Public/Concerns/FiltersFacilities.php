<?php

namespace App\Http\Controllers\Public\Concerns;

use App\Models\Facility;
use Illuminate\Http\Request;

// 12 Agustos 2026: FacilityController::index()'teki filtre mantigi
// HomeController tarafindan da kullanilabilsin diye (ana sayfadaki filtre
// formu artik ayni sayfada gercek sonuc gosteriyor, bkz. home.blade.php)
// ortak bir trait'e cikarildi. Ayni oturumda kurum ismiyle arama (`q`)
// filtresi de buraya eklendi.
//
// 12 Agustos 2026 (2): kullanicinin talebi - kurum ismiyle arama (`q`)
// artik o an secili "bolum" (yasli-bakim/cocuk/vb.) ile sinirli kalmiyor,
// markanin TUM bolumlerinde ariyor. Diger her filtre (il/ilce/kategori/
// hizmet/fiyat) degismeden, hala secili bolume gore calismaya devam
// ediyor - sadece isim aramasi bolumden bagimsizlastirildi.
trait FiltersFacilities
{
    protected function filteredQuery(Request $request, array $scope, ?array $allBrandScopes = null)
    {
        $effectiveScope = ($request->filled('q') && $allBrandScopes) ? $allBrandScopes : $scope;
        $query = Facility::discoverable()->forBrand($effectiveScope);

        if ($request->filled('q')) {
            // facility_categories tablosunda da bir "name" kolonu var; bu
            // trait'teki sectionBreakdown() facilities'i facility_categories
            // ile JOIN ettiginde nitelenmemis "name" SQL'de belirsiz hale
            // gelip "Column 'name' in WHERE is ambiguous" hatasi veriyordu.
            $query->where('facilities.name', 'like', '%'.trim($request->q).'%');
        }

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('service')) {
            $query->whereJsonContains('services', $request->service);
        }

        if ($request->boolean('pre_registered')) {
            $query->where('is_claimed', false)->where('source', 'google_maps_veri_cekici');
        }

        if ($request->filled('price_tier') || $request->filled('budget')) {
            [$standartMin, $premiumMin, $ultraMin] = $this->priceTierThresholds();

            $tierKey = $request->filled('price_tier')
                ? $request->price_tier
                : $this->tierForBudget((float) $request->budget, $standartMin, $premiumMin, $ultraMin);

            $query->whereNotNull('price_min')->where(function ($qq) use ($tierKey, $standartMin, $premiumMin, $ultraMin) {
                match ($tierKey) {
                    'ekonomik' => $qq->where('price_min', '<', $standartMin),
                    'standart' => $qq->where('price_min', '>=', $standartMin)->where('price_min', '<', $premiumMin),
                    'premium' => $qq->where('price_min', '>=', $premiumMin)->where('price_min', '<', $ultraMin),
                    'ultra_premium' => $qq->where('price_min', '>=', $ultraMin),
                    default => null,
                };
            });
        }

        return $query;
    }

    /**
     * Kurum ismiyle arama (`q`) TUM bolumlerde arandigi icin, kullanicinin
     * "kac kurum hangi bolumden" gorebilmesi amaciyla eslesen kayitlarin
     * bolumlere gore dagilimini doner. Sadece `q` doluyken anlamlidir.
     *
     * @return array<int, array{slug: string, title: string, icon: ?string, total: int}>
     */
    protected function sectionBreakdown(Request $request, array $allBrandScopes): array
    {
        if (! $request->filled('q')) {
            return [];
        }

        $counts = $this->filteredQuery($request, $allBrandScopes, $allBrandScopes)
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->selectRaw('facility_categories.brand_scope as scope, count(*) as total')
            ->groupBy('facility_categories.brand_scope')
            ->pluck('total', 'scope');

        $breakdown = [];
        foreach (service_sections() as $section) {
            $total = 0;
            foreach ($section['scopes'] as $scopeKey) {
                $total += (int) ($counts[$scopeKey] ?? 0);
            }
            if ($total > 0) {
                $breakdown[] = [
                    'slug' => $section['slug'],
                    'title' => $section['title'],
                    'icon' => $section['icon'] ?? null,
                    'total' => $total,
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Bir istekte kullanicinin gercekten filtreleme yaptigini (sadece
     * bolum/sayfalama disinda en az bir kritere deger girdigini) anlamak
     * icin - HomeController bunu "sonuc mu, yoksa tanitim mi gosterilsin"
     * kararinda kullanir.
     */
    protected function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('city')
            || $request->filled('district')
            || $request->filled('category')
            || $request->filled('service')
            || $request->filled('price_tier')
            || $request->filled('budget');
    }

    /**
     * Facility::priceTier() ile ayni esikler -- kullanicinin serbest metin
     * girdigi "bütçe" rakaminin hangi segmente (Ekonomik/Standart/Premium/
     * Ultra Premium) denk geldigini bulmak icin kullanilir.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    protected function priceTierThresholds(): array
    {
        $defaults = config('platform.default_price_tiers');

        return [
            (float) \App\Models\Setting::get('price_tier_standart_min', $defaults['standart_min']),
            (float) \App\Models\Setting::get('price_tier_premium_min', $defaults['premium_min']),
            (float) \App\Models\Setting::get('price_tier_ultra_min', $defaults['ultra_min']),
        ];
    }

    protected function tierForBudget(float $budget, float $standartMin, float $premiumMin, float $ultraMin): string
    {
        return match (true) {
            $budget >= $ultraMin => 'ultra_premium',
            $budget >= $premiumMin => 'premium',
            $budget >= $standartMin => 'standart',
            default => 'ekonomik',
        };
    }
}
