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

        $query = Facility::published()
            ->forBrand($scope)
            ->with(['city', 'category', 'images']);

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

        if ($request->filled('price_max')) {
            $query->where(function ($qq) use ($request) {
                $qq->whereNull('price_min')->orWhere('price_min', '<=', $request->price_max);
            });
        }

        if ($request->filled('price_tier')) {
            $defaults = config('platform.default_price_tiers');
            $standartMin = (float) \App\Models\Setting::get('price_tier_standart_min', $defaults['standart_min']);
            $premiumMin = (float) \App\Models\Setting::get('price_tier_premium_min', $defaults['premium_min']);
            $ultraMin = (float) \App\Models\Setting::get('price_tier_ultra_min', $defaults['ultra_min']);

            $query->whereNotNull('price_min')->where(function ($qq) use ($request, $standartMin, $premiumMin, $ultraMin) {
                match ($request->price_tier) {
                    'ekonomik' => $qq->where('price_min', '<', $standartMin),
                    'standart' => $qq->where('price_min', '>=', $standartMin)->where('price_min', '<', $premiumMin),
                    'premium' => $qq->where('price_min', '>=', $premiumMin)->where('price_min', '<', $ultraMin),
                    'ultra_premium' => $qq->where('price_min', '>=', $ultraMin),
                    default => null,
                };
            });
        }

        $perPage = $request->boolean('pre_registered') ? 12 : 9;
        $facilities = $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate($perPage)->withQueryString();

        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::whereIn('brand_scope', $scope)->orderBy('name')->get();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection
            ? $activeSection['features']
            : collect($sections)->flatMap(fn ($section) => $section['features'])->unique()->values()->all();

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
            'nearbyFacilities'
        ));
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
        // increment() DB'yi gunceller ve modeldeki attribute'u da otomatik senkronlar.
        $facility->increment('views_count');

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