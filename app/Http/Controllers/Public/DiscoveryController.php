<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityImage;
use App\Models\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// "Ben olsam eklerdim" onerilerinden kesif/vitrin sayfalari:
// Dogrulanmis Kurumlar, Son Guncellenen, Yeni Eklenen, Son Sahiplenilen,
// En Cok Goruntulenen, Son Eklenen Fotograflar.
class DiscoveryController extends Controller
{
    /**
     * Vitrin sayfalari brand genelinde degil, o an secili "bolum" (yasli-bakim/
     * cocuk/rehabilitasyon) kapsaminda filtrelenir; boylece "Yeni Eklenen
     * Kurumlar" gibi listeler baska bolumlerin kurumlarini karistirmaz.
     */
    private function activeScopes(Request $request, array $brand): array
    {
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;

        return [$activeSection, $activeSection['scopes'] ?? $brand['category_scope']];
    }

    public function verified(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $facilities = Facility::published()->claimed()
            ->forBrand($scopes)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('claimed_at')
            ->paginate(15)->withQueryString();

        return view("themes.{$brand['theme']}.facilities.grid-page", [
            'title' => 'Doğrulanmış Kurumlar',
            'subtitle' => 'Sahiplenme başvurusu admin tarafından onaylanmış, yetkilisi doğrulanmış kurumlar.',
            'facilities' => $facilities,
            'badges' => [],
            'activeSection' => $activeSection,
            'sections' => service_sections(),
        ]);
    }

    public function recentlyUpdated(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $facilities = Facility::published()
            ->forBrand($scopes)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('updated_at')
            ->paginate(15)->withQueryString();

        return view("themes.{$brand['theme']}.facilities.grid-page", [
            'title' => 'Son Güncellenen Kurumlar',
            'subtitle' => 'Bilgileri en yakın zamanda güncellenmiş kurumlar.',
            'facilities' => $facilities,
            'badges' => $facilities->mapWithKeys(fn ($f) => [$f->id => '<span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded-full">'.$f->updated_at->diffForHumans().'</span>']),
            'activeSection' => $activeSection,
            'sections' => service_sections(),
        ]);
    }

    public function newlyAdded(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $facilities = Facility::published()
            ->forBrand($scopes)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('created_at')
            ->paginate(15)->withQueryString();

        return view("themes.{$brand['theme']}.facilities.grid-page", [
            'title' => 'Yeni Eklenen Kurumlar',
            'subtitle' => 'Platforma en son eklenen kurum profilleri.',
            'facilities' => $facilities,
            'badges' => [],
            'activeSection' => $activeSection,
            'sections' => service_sections(),
        ]);
    }

    public function recentlyClaimed(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $facilities = Facility::published()->claimed()
            ->forBrand($scopes)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('claimed_at')
            ->paginate(15)->withQueryString();

        return view("themes.{$brand['theme']}.facilities.grid-page", [
            'title' => 'Son Sahiplenilen Kurumlar',
            'subtitle' => 'Bugün ve son günlerde yetkilisi tarafından sahiplenilip doğrulanan kurumlar.',
            'facilities' => $facilities,
            'badges' => [],
            'activeSection' => $activeSection,
            'sections' => service_sections(),
        ]);
    }

    public function mostViewed(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $facilities = Facility::published()
            ->forBrand($scopes)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('views_count')
            ->paginate(15)->withQueryString();

        return view("themes.{$brand['theme']}.facilities.grid-page", [
            'title' => 'En Çok Görüntülenen Kurumlar',
            'subtitle' => 'Ziyaretçilerin profil sayfasını en çok açtığı kurumlar.',
            'facilities' => $facilities,
            'badges' => $facilities->mapWithKeys(fn ($f) => [$f->id => '<span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">'.number_format($f->views_count).' görüntülenme</span>']),
            'activeSection' => $activeSection,
            'sections' => service_sections(),
        ]);
    }

    /**
     * "En cok aranan kurumlar/bolgeler": sitede serbest metin arama kutusu
     * olmadigi icin (bkz. facilities.index il/kategori/hizmet filtreleri),
     * burada gosterilen "en cok aranan" en cok filtrelenen il+kategori
     * kombinasyonudur — kelime bazli arama loglama degildir. Bu fark
     * sayfada acikca belirtilir.
     */
    public function mostSearched(Request $request)
    {
        $brand = current_brand();

        $rows = SearchQuery::query()
            ->where('brand', $brand['slug'])
            ->where('search_date', '>=', now()->subDays(30)->toDateString())
            ->whereNotNull('city_id')
            ->join('cities', 'cities.id', '=', 'search_queries.city_id')
            ->leftJoin('facility_categories', 'facility_categories.id', '=', 'search_queries.facility_category_id')
            ->select(
                'cities.name as city_name',
                'cities.slug as city_slug',
                'facility_categories.name as category_name',
                'facility_categories.slug as category_slug',
                DB::raw('sum(search_queries.count) as total')
            )
            ->groupBy('cities.name', 'cities.slug', 'facility_categories.name', 'facility_categories.slug')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        return view("themes.{$brand['theme']}.facilities.most-searched", compact('brand', 'rows'));
    }

    public function recentPhotos(Request $request)
    {
        $brand = current_brand();
        [$activeSection, $scopes] = $this->activeScopes($request, $brand);
        $images = FacilityImage::whereHas('facility', function ($q) use ($scopes) {
                $q->published()->forBrand($scopes);
            })
            ->with('facility.city')
            ->latest()
            ->paginate(24)->withQueryString();

        $sections = service_sections();

        return view("themes.{$brand['theme']}.facilities.recent-photos", compact('images', 'brand', 'activeSection', 'sections'));
    }
}
