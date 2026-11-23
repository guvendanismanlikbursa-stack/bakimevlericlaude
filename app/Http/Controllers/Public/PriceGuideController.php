<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $city = (City::findBySlugCached($citySlug) ?? abort(404));
        $districtName = $this->resolveDistrict($city, $districtSlug);

        // 23 Kasım 2026: bkz. config/brands.php seo_scopes.
        $baseQuery = Facility::discoverable()
            ->forBrand($section['seo_scopes'] ?? $section['scopes'])
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

        $category = FacilityCategory::findBySlugCached($categorySlug, $brand['category_scope']) ?? abort(404);
        abort_if((service_section_for_scope($category->brand_scope)['slug'] ?? null) !== $sectionSlug, 404);

        $city = (City::findBySlugCached($citySlug) ?? abort(404));
        $districtName = $this->resolveDistrict($city, $districtSlug);

        $baseQuery = Facility::discoverable()
            ->where('facility_category_id', $category->id)
            ->where('city_id', $city->id);

        if ($districtName) {
            $baseQuery->where('district', $districtName);
        }

        return $this->render($brand, $section, $city, $districtName, $category, $baseQuery);
    }

    /**
     * 18 Kasım 2026: kullanıcının talebi - "her aramada en az ilk sayfada çıkmalıyız". İl belirtilmeyen
     * "kreş fiyatları", "bakımevi fiyatları", "fizik tedavi fiyatları" aramaları için ülke geneli sayfa yoktu
     * (yalnız ?bolum= parametreli seçim ekranı vardı). Bu sayfa 81 ilin kurum sayısı, girilmiş ücret ve puan
     * özetini tek tabloda verir; her il, il fiyat sayfasına bağlanır. Uydurma fiyat yok: yalnız kurumların
     * kendi girdiği ücretler ve config/resmi_ucretler.php'deki resmi rakamlar.
     */
    public function national(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        return $this->renderNational($brand, $section, null);
    }

    public function nationalCategory(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);
        $category = FacilityCategory::findBySlugCached($request->route('categorySlug'), $brand['category_scope']) ?? abort(404);
        abort_if((service_section_for_scope($category->brand_scope)['slug'] ?? null) !== $sectionSlug, 404);

        return $this->renderNational($brand, $section, $category);
    }

    private function renderNational(array $brand, array $section, ?FacilityCategory $category)
    {
        $veri = Cache::remember('price-guide:ulke:v2:'.$section['slug'].':'.($category->id ?? '-'), now()->addHours(6), function () use ($section, $category) {
            $q = Facility::discoverable()->when($category,
                fn ($q) => $q->where('facility_category_id', $category->id),
                fn ($q) => $q->forBrand($section['seo_scopes'] ?? $section['scopes']));
            $sehirAd = City::cachedAll()->keyBy('id');
            $iller = (clone $q)->selectRaw('city_id, COUNT(*) as kurum, SUM(CASE WHEN price_min > 0 THEN 1 ELSE 0 END) as fiyatli,
                    AVG(CASE WHEN price_min > 0 THEN price_min END) as ort_fiyat, AVG(CASE WHEN rating > 0 THEN rating END) as puan')
                ->groupBy('city_id')->orderByDesc('kurum')->get()
                ->filter(fn ($r) => isset($sehirAd[$r->city_id]))
                ->map(fn ($r) => ['ad' => $sehirAd[$r->city_id]->name, 'slug' => $sehirAd[$r->city_id]->slug, 'kurum' => (int) $r->kurum,
                    'fiyatli' => (int) $r->fiyatli, 'ort_fiyat' => $r->ort_fiyat ? (int) round($r->ort_fiyat) : null, 'puan' => $r->puan ? round((float) $r->puan, 1) : null])
                ->values()->all();
            $fiyat = (clone $q)->where('price_min', '>', 0)->selectRaw('COUNT(*) as n, AVG(price_min) as ort, MIN(price_min) as en_az, MAX(price_max) as en_cok')->first();

            return [
                'iller' => $iller,
                'toplam' => array_sum(array_column($iller, 'kurum')),
                'fiyat' => ['n' => (int) $fiyat->n, 'ort' => $fiyat->ort ? (int) round($fiyat->ort) : null, 'en_az' => $fiyat->en_az, 'en_cok' => $fiyat->en_cok],
                'kategoriler' => FacilityCategory::cachedPublic()->whereIn('brand_scope', $section['scopes'])->values()->map(fn ($c) => ['ad' => $c->name, 'slug' => $c->slug])->all(),
            ];
        });

        return view('themes._shared.price-guide-national', ['brand' => $brand, 'section' => $section, 'category' => $category] + $veri);
    }

    /**
     * Bolum + il secim ekrani (hangi sehir/bolum icin rehber gormek istedigini secer).
     */
    public function index(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $cities = City::cachedAll();

        return view("themes.{$brand['theme']}.price-guide-index", compact('brand', 'sections', 'activeSection', 'cities'));
    }

    private function render(array $brand, array $section, City $city, ?string $districtName, ?FacilityCategory $category, $baseQuery)
    {
        // 11 Eylul 2026: kullanicinin bildirdigi tekrarlayan "Too many
        // connections" hatasi - bu sayfa tek basina 8-9 ayri sorgu
        // calistiriyordu (5 ayri istatistik sorgusu + rozet sayimi + 2
        // sayfalama sorgusu + kategori listesi). Once istatistik sorgulari
        // TEK bir sorguya birlestirildi (COUNT/AVG/MIN/MAX SQL'in kendisi
        // NULL'lari zaten atliyor, sonuc matematiksel olarak birebir ayni).
        // Sonra tum hesaplama 15 dakikalik kisa sureli onbellege alindi -
        // Turkiye capinda binlerce il/ilce/kategori kombinasyonu oldugu
        // icin ayni sayfa tekrar ziyaret edildiginde veritabanina hic
        // gidilmez.
        $page = (int) request()->query('page', 1);
        $cacheKey = 'price-guide:v3:'.md5(implode('|', [
            $brand['slug'], $section['slug'], $city->id, $districtName ?? '', $category->id ?? '', $page,
        ]));

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($baseQuery, $city, $districtName, $section, $brand, $category) {
            // 9 Kasım 2026: fiyatı 0 girilmiş kurum ortalamaya katılmaz (sayfa "0 TL" gösteriyordu).
            $priced = (clone $baseQuery)->whereNotNull('price_min')->where('price_min', '>', 0);

            $priceStats = (clone $priced)->selectRaw(
                'COUNT(*) as priced_count, AVG(price_min) as avg_min, MIN(price_min) as min_price, MAX(price_max) as max_price'
            )->first();

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'priced_count' => (int) $priceStats->priced_count,
                'avg_min' => $priceStats->avg_min,
                'min' => $priceStats->min_price,
                'max' => $priceStats->max_price,
            ];

            $tierCounts = [];
            if ($stats['priced_count'] > 0) {
                // 14 Agustos 2026: kullanicinin talebi uzerine yapilan genis
                // denetimde bulunan N+1 - 'facility_category_id' secilmedigi
                // icin priceTier()'in $this->category erisimi HER kurum icin
                // ayri bir sorgu tetikliyordu (VE facility_category_id secilmedigi
                // icin bu iliski hep null donup segment hesabi kategoriye ozel
                // esikler yerine hep JENERIK varsayilanlara duşuyordu - sessiz
                // bir dogruluk hatasi da vardi). ->with('category') + FK'nin de
                // secilmesiyle ikisi birden duzeldi.
                foreach ((clone $priced)->with('category')->get(['id', 'price_min', 'facility_category_id']) as $facility) {
                    $tier = $facility->priceTier();
                    if ($tier) {
                        $tierCounts[$tier['key']] = ($tierCounts[$tier['key']] ?? 0) + 1;
                    }
                }
            }

            // 30 Eylul 2026: paylasilan scope'a tasindi (bkz. Facility::
            // scopeOrderByListingPriority ayni tarihli yorum).
            $facilities = (clone $baseQuery)
                ->with(['city', 'category', 'images'])->withCount(['approvedReviews', 'googleReviews'])
                ->orderByListingPriority()
                ->paginate(12)
                ->withQueryString();

            $sectionCategories = FacilityCategory::cachedPublic()->whereIn('brand_scope', $section['scopes'])->values();
            $nearDistricts = collect(districts_for_city($city->name))->take(18)->values();
            $categoryLabel = $category->name ?? ($section['title'].' kurumları');
            $guideContent = guide_page_content($brand, $city->name, $districtName, $categoryLabel, $stats['total']);

            // 18 Kasım 2026: il sayfasına her ile özgü gerçek veri - ilçelere göre kurum sayısı, girilmiş ücret ve puan.
            $ilceDagilim = $districtName ? [] : (clone $baseQuery)->whereNotNull('district')->where('district', '!=', '')
                ->selectRaw('district, COUNT(*) as kurum, AVG(CASE WHEN price_min > 0 THEN price_min END) as ort_fiyat, AVG(CASE WHEN rating > 0 THEN rating END) as puan')
                ->groupBy('district')->orderByDesc('kurum')->limit(40)->get()
                ->map(fn ($r) => ['ad' => $r->district, 'kurum' => (int) $r->kurum, 'ort_fiyat' => $r->ort_fiyat ? (int) round($r->ort_fiyat) : null, 'puan' => $r->puan ? round((float) $r->puan, 1) : null])
                ->all();
            $puanOzet = (clone $baseQuery)->where('rating', '>', 0)->selectRaw('COUNT(*) as n, AVG(rating) as ort')->first();
            $stats['puanli'] = (int) $puanOzet->n;
            $stats['ort_puan'] = $puanOzet->ort ? round((float) $puanOzet->ort, 1) : null;

            return compact('stats', 'tierCounts', 'facilities', 'sectionCategories', 'nearDistricts', 'guideContent', 'ilceDagilim');
        });

        ['stats' => $stats, 'tierCounts' => $tierCounts, 'facilities' => $facilities,
            'sectionCategories' => $sectionCategories, 'nearDistricts' => $nearDistricts, 'guideContent' => $guideContent, 'ilceDagilim' => $ilceDagilim] = $data;

        // 14 Agustos 2026: bkz. Public\FacilityController::index ayni yorum -
        // menzil disi sayfaya gidilince yanlis "sonuc yok" mesaji yerine
        // son gecerli sayfaya yonlendiriyoruz.
        if ($facilities->isEmpty() && $facilities->total() > 0 && $facilities->currentPage() > $facilities->lastPage()) {
            return redirect(request()->fullUrlWithQuery(['page' => $facilities->lastPage()]));
        }

        return view("themes.{$brand['theme']}.price-guide", compact(
            'brand', 'section', 'city', 'districtName', 'category', 'stats', 'tierCounts', 'facilities', 'sectionCategories', 'nearDistricts', 'guideContent', 'ilceDagilim'
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
