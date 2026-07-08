<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\SearchQuery;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request, GeoLookupService $geo)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;
        $scope = $activeSection ? $activeSection['scopes'] : $brand['category_scope'];

        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::whereIn('brand_scope', $scope)->orderBy('name')->get();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection
            ? $activeSection['features']
            : collect($sections)->flatMap(fn ($section) => $section['features'])->unique()->values()->all();

        // Bolum secilmeden bu sayfaya gelinirse (ana nav'daki "Kurumları Bul"
        // linki gibi) tum kurumlari tek listede gostermek istemiyoruz;
        // kullanici once bir bolum secmeli. Agir sorguyu calistirmadan
        // erken donuyoruz, view sadece bolum secim ekranini render eder.
        if (! $activeSection) {
            return view("themes.{$brand['theme']}.facilities.index", compact(
                'sections',
                'activeSection',
                'cities',
                'categories',
                'districtsByCity',
                'sectionServices'
            ));
        }

        $query = $this->filteredQuery($request, $scope);

        $perPage = $request->boolean('pre_registered') ? 12 : 9;
        $facilities = $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate($perPage)->withQueryString();

        // "Harita mantiginda bolgesel dagilim" TUM filtrelenmis sonuclar
        // uzerinden hesaplanmali, sadece o anki sayfadaki 9 kayittan degil;
        // bu yuzden aggregate icin filtreyi tekrar (sayfalama olmadan) kurup
        // sehir+ilce bazinda ayri bir count sorgusu calistiriyoruz.
        $regionGroups = $this->filteredQuery($request, $scope)
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->selectRaw('cities.name as city_name, cities.slug as city_slug, facilities.district, count(*) as total')
            ->groupBy('cities.name', 'cities.slug', 'facilities.district')
            ->orderByDesc('total')
            ->limit(9)
            ->get();

        // "En cok aranan kurumlar/bolgeler" icin: site serbest metin arama
        // kutusu sunmuyor, bu yuzden gercek "arama" burada il/kategori/hizmet
        // filtresi secilmis olmasidir. Bos filtreyle sadece goz atma
        // (pre_registered, sayfalama vb.) loglanmaz.
        if ($request->filled('city') || $request->filled('category') || $request->filled('service')) {
            SearchQuery::record(
                $brand['slug'],
                $request->filled('city') ? $cities->firstWhere('slug', $request->city)?->id : null,
                $request->filled('category') ? $categories->firstWhere('slug', $request->category)?->id : null,
                $request->filled('service') ? $request->service : null,
            );
        }

        $nearbyFacilities = [];
        if ($request->filled('lat') && $request->filled('lng') && is_numeric($request->lat) && is_numeric($request->lng)) {
            $candidates = Facility::published()
                ->forBrand($scope)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->with(['city', 'category', 'images'])
                ->get();

            $nearbyFacilities = $geo->nearestFacilities($candidates, (float) $request->lat, (float) $request->lng);
        }

        return view("themes.{$brand['theme']}.facilities.index", compact(
            'facilities',
            'cities',
            'categories',
            'sections',
            'activeSection',
            'districtsByCity',
            'sectionServices',
            'nearbyFacilities',
            'regionGroups'
        ));
    }

    /**
     * Ana sayfa ve kurum listesindeki filtre formlari, secim yapildikca
     * (Filtrele'ye basmadan) kac kurum eslestigini gostermek icin bu
     * endpoint'i cagirir. Sorgu mantigi index() ile birebir aynidir.
     */
    public function count(Request $request)
    {
        $brand = current_brand();
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;
        $scope = $activeSection ? $activeSection['scopes'] : $brand['category_scope'];

        return response()->json([
            'count' => $this->filteredQuery($request, $scope)->count(),
        ]);
    }

    private function filteredQuery(Request $request, array $scope)
    {
        $query = Facility::published()->forBrand($scope);

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
     * Facility::priceTier() ile ayni esikler -- kullanicinin serbest metin
     * girdigi "bütçe" rakaminin hangi segmente (Ekonomik/Standart/Premium/
     * Ultra Premium) denk geldigini bulmak icin kullanilir.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function priceTierThresholds(): array
    {
        $defaults = config('platform.default_price_tiers');

        return [
            (float) \App\Models\Setting::get('price_tier_standart_min', $defaults['standart_min']),
            (float) \App\Models\Setting::get('price_tier_premium_min', $defaults['premium_min']),
            (float) \App\Models\Setting::get('price_tier_ultra_min', $defaults['ultra_min']),
        ];
    }

    private function tierForBudget(float $budget, float $standartMin, float $premiumMin, float $ultraMin): string
    {
        return match (true) {
            $budget >= $ultraMin => 'ultra_premium',
            $budget >= $premiumMin => 'premium',
            $budget >= $standartMin => 'standart',
            default => 'ekonomik',
        };
    }

    public function show(Request $request)
    {
        $brand = current_brand();
        $slug = $request->route('slug');

        $facility = Facility::published()
            ->forBrand($brand['category_scope'])
            ->where('slug', $slug)
            ->with(['city', 'category', 'images', 'approvedReviews', 'answeredQuestions'])
            ->withAvg('approvedReviews', 'rating')
            ->firstOrFail();

        // "En cok goruntulenen kurumlar" ve performans sayfasi icin sayac.
        // Admin kendi kurumlarina bakarken ya da aynı ziyaretci sayfayi
        // sik sik yeniledigin de sayac sismesin diye: admin oturumunda hic
        // sayilmaz, digerlerinde ayni tarayici oturumunda ayni kurum 24
        // saatte yalnizca bir kez sayilir.
        if (! session('admin_id')) {
            $viewedKey = 'viewed_facility_'.$facility->id;
            $lastViewedAt = session($viewedKey);

            if (! $lastViewedAt || now()->diffInHours($lastViewedAt) >= 24) {
                $facility->increment('views_count');
                session([$viewedKey => now()]);
            }
        }

        $serviceSection = service_section_for_scope($facility->category?->brand_scope);

        $related = Facility::published()
            ->forBrand($serviceSection['scopes'] ?? $brand['category_scope'])
            ->where('facility_category_id', $facility->facility_category_id)
            ->where('id', '!=', $facility->id)
            ->limit(3)
            ->get();

        return view("themes.{$brand['theme']}.facilities.show", compact('facility', 'related', 'serviceSection'));
    }
}