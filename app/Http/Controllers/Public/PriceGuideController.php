<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// "Ucret Rehberi": "Bursa huzurevi fiyatlari" gibi yuksek SEO degerli,
// sehir+bolum bazli otomatik fiyat istatistigi sayfalari.
class PriceGuideController extends Controller
{
    public function show(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $citySlug = $request->route('citySlug');
        $districtSlug = $request->route('districtSlug');

        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        $city = City::where('slug', $citySlug)->firstOrFail();
        $districtName = $this->resolveDistrict($city, $districtSlug);

        $baseQuery = Facility::published()
            ->forBrand($section['scopes'])
            ->where('city_id', $city->id);

        if ($districtName) {
            $baseQuery->where('district', $districtName);
        }

        return $this->render($brand, $section, $city, $districtName, null, $baseQuery);
    }

    /**
     * Kategori-ozel ucret rehberi: "Istanbul Huzurevi Fiyatlari" gibi.
     * Opsiyonel ilce parametresi ile ucuncu eksen (il+ilce+kategori) de
     * desteklenir - LocationGuideController::showCategory() ile ayni
     * granularite, sitemap kapasitesini tam esitlemek icin.
     */
    public function showCategory(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $citySlug = $request->route('citySlug');
        $categorySlug = $request->route('categorySlug');
        $districtSlug = $request->route('districtSlug');

        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        $category = FacilityCategory::where('slug', $categorySlug)
            ->whereIn('brand_scope', $brand['category_scope'])
            ->firstOrFail();
        abort_if((service_section_for_scope($category->brand_scope)['slug'] ?? null) !== $sectionSlug, 404);

        $city = City::where('slug', $citySlug)->firstOrFail();
        $districtName = $this->resolveDistrict($city, $districtSlug);

        $baseQuery = Facility::published()
            ->where('facility_category_id', $category->id)
            ->where('city_id', $city->id);

        if ($districtName) {
            $baseQuery->where('district', $districtName);
        }

        return $this->render($brand, $section, $city, $districtName, $category, $baseQuery);
    }

    /**
     * Bolum + il secim ekrani (hangi sehir/bolum icin rehber gormek istedigini secer).
     */
    public function index(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $cities = City::orderBy('name')->get();

        return view("themes.{$brand['theme']}.price-guide-index", compact('brand', 'sections', 'activeSection', 'cities'));
    }

    private function render(array $brand, array $section, City $city, ?string $districtName, ?FacilityCategory $category, $baseQuery)
    {
        $priced = (clone $baseQuery)->whereNotNull('price_min');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'priced_count' => (clone $priced)->count(),
            'avg_min' => (clone $priced)->avg('price_min'),
            'min' => (clone $priced)->min('price_min'),
            'max' => (clone $priced)->max('price_max'),
        ];

        $tierCounts = [];
        if ($stats['priced_count'] > 0) {
            foreach ((clone $priced)->get(['price_min']) as $facility) {
                $tier = $facility->priceTier();
                if ($tier) {
                    $tierCounts[$tier['key']] = ($tierCounts[$tier['key']] ?? 0) + 1;
                }
            }
        }

        $facilities = (clone $baseQuery)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->paginate(12);

        $sectionCategories = FacilityCategory::whereIn('brand_scope', $section['scopes'])->orderBy('name')->get();
        $nearDistricts = collect(districts_for_city($city->name))->take(18)->values();
        $categoryLabel = $category->name ?? ($section['title'].' kurumları');
        $guideContent = guide_page_content($brand, $city->name, $districtName, $categoryLabel, $stats['total']);

        return view("themes.{$brand['theme']}.price-guide", compact(
            'brand', 'section', 'city', 'districtName', 'category', 'stats', 'tierCounts', 'facilities', 'sectionCategories', 'nearDistricts', 'guideContent'
        ));
    }

    private function resolveDistrict(City $city, ?string $districtSlug): ?string
    {
        if (! $districtSlug) {
            return null;
        }

        foreach (districts_for_city($city->name) as $candidate) {
            if (Str::slug($candidate) === $districtSlug) {
                return $candidate;
            }
        }

        abort(404);
    }
}
