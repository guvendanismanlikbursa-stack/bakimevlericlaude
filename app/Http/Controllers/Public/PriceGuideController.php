<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use Illuminate\Http\Request;

// "Ucret Rehberi": "Bursa huzurevi fiyatlari" gibi yuksek SEO degerli,
// sehir+bolum bazli otomatik fiyat istatistigi sayfalari.
class PriceGuideController extends Controller
{
    public function show(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $citySlug = $request->route('citySlug');

        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        $city = City::where('slug', $citySlug)->firstOrFail();

        $baseQuery = Facility::published()
            ->forBrand($section['scopes'])
            ->where('city_id', $city->id);

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

        return view("themes.{$brand['theme']}.price-guide", compact(
            'brand', 'section', 'city', 'stats', 'tierCounts', 'facilities'
        ));
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
}
