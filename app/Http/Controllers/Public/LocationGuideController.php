<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationGuideController extends Controller
{
    /**
     * "Tum iller" rehber hub sayfasi: /rehber/{sectionSlug}/{citySlug} sayfalari
     * sitemap'te olsa da, siteyi gezen gercek bir ziyaretci (ve normal crawl
     * yapan Googlebot) onlara TIKLAYARAK ulasabilecegi bir sayfa yoktu -
     * sadece sitemap.xml'den bilinebiliyorlardi. 12 Agustos 2026: kullanicinin
     * talebi uzerine, 81 ilin TAMAMINA gercek <a href> linki veren bu hub
     * eklendi (footer'daki "Kesfet" bolumunden her sayfadan erisilebilir).
     */
    public function index(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');

        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        $cities = City::orderBy('name')->get(['slug', 'name']);
        $sections = service_sections();

        return view("themes.{$brand['theme']}.location-guide-index", compact('brand', 'section', 'sections', 'cities'));
    }

    public function show(Request $request)
    {
        $brand = current_brand();
        $sectionSlug = $request->route('sectionSlug');
        $citySlug = $request->route('citySlug');
        $districtSlug = $request->route('districtSlug');

        $section = active_service_section($sectionSlug, $brand);
        abort_if(($section['slug'] ?? null) !== $sectionSlug, 404);

        $city = City::where('slug', $citySlug)->firstOrFail();
        $districts = districts_for_city($city->name);
        $districtName = $this->resolveDistrict($districts, $districtSlug);

        $query = Facility::discoverable()
            ->forBrand($section['scopes'])
            ->where('city_id', $city->id)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating');

        if ($districtName) {
            $query->where('district', $districtName);
        }

        $facilityCount = (clone $query)->count();
        $facilities = $query->limit(12)->get();
        $nearDistricts = collect($districts)->take(18)->values();
        $content = site_section_content($brand['slug'], $section['slug']);
        $sectionCategories = FacilityCategory::whereIn('brand_scope', $section['scopes'])->orderBy('name')->get();
        $category = null;
        $topicTitle = $section['seo_title'] ?? $section['title'];
        $guideContent = guide_page_content($brand, $city->name, $districtName, $section['title'].' kurumları', $facilityCount);
        $priceRange = $this->priceRangeFor($section['scopes'], $city->id, $districtName);
        $content['faq_preview'] = $this->withLocalFaq($content['faq_preview'] ?? [], $city->name, $districtName, $topicTitle, $priceRange, $facilityCount);

        return view("themes.{$brand['theme']}.location-guide", compact(
            'brand',
            'section',
            'city',
            'districtName',
            'nearDistricts',
            'facilities',
            'facilityCount',
            'content',
            'category',
            'sectionCategories',
            'guideContent',
            'priceRange'
        ));
    }

    /**
     * Kategori-ozel rehber sayfasi: "Istanbul Huzurevi Rehberi" gibi,
     * genis bolum yerine tek bir alt-kategoriye daralan sayfa.
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
        $districts = districts_for_city($city->name);
        $districtName = $this->resolveDistrict($districts, $districtSlug);

        $query = Facility::discoverable()
            ->where('facility_category_id', $category->id)
            ->where('city_id', $city->id)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating');

        if ($districtName) {
            $query->where('district', $districtName);
        }

        $facilityCount = (clone $query)->count();
        $facilities = $query->limit(12)->get();
        $nearDistricts = collect($districts)->take(18)->values();
        $content = site_section_content($brand['slug'], $section['slug']);
        $sectionCategories = FacilityCategory::whereIn('brand_scope', $section['scopes'])->orderBy('name')->get();
        $guideContent = guide_page_content($brand, $city->name, $districtName, $category->name, $facilityCount);
        $priceRange = $this->priceRangeFor($section['scopes'], $city->id, $districtName, $category->id);
        $content['faq_preview'] = $this->withLocalFaq($content['faq_preview'] ?? [], $city->name, $districtName, $category->name, $priceRange, $facilityCount);

        return view("themes.{$brand['theme']}.location-guide", compact(
            'brand',
            'section',
            'city',
            'districtName',
            'nearDistricts',
            'facilities',
            'facilityCount',
            'content',
            'category',
            'sectionCategories',
            'guideContent',
            'priceRange'
        ));
    }

    /**
     * 26 Agustos 2026: kullanicinin talebi - "il+kategori" Google
     * aramalarinda gorunmememizin bir nedeni de bu sayfanin govde
     * metninin (kontrol listesi + SSS) TAMAMEN bolum-bazli (config/
     * site_content.php) olup, binlerce il/ilce sayfasinda BIREBIR ayni
     * olmasiydi - sadece baslik/sehir adi degisiyordu. Google bu turde
     * "govde metni neredeyse ayni, sadece degisken yer tutucu farkli"
     * sayfalari dusuk kaliteli/kopya icerik olarak degerlendirebilir.
     * Bu metot GERCEK veriden (o sehir/ilcedeki kurumlarin gercek
     * fiyatlari) sayfaya OZGU, gercek bir fiyat araligi hesaplar - hicbir
     * iki sayfa (farkli il/ilce/kategori kombinasyonu oldugu surece) ayni
     * degeri gostermez.
     */
    private function priceRangeFor(array $scopes, int $cityId, ?string $districtName, ?int $categoryId = null): ?array
    {
        $query = Facility::discoverable()
            ->where('city_id', $cityId)
            ->whereNotNull('price_min')
            ->where('price_min', '>', 0);

        if ($categoryId) {
            $query->where('facility_category_id', $categoryId);
        } else {
            $query->forBrand($scopes);
        }

        if ($districtName) {
            $query->where('district', $districtName);
        }

        $stats = $query->selectRaw('MIN(price_min) as min_price, MAX(COALESCE(price_max, price_min)) as max_price, COUNT(*) as priced_count')->first();

        if (! $stats || ! $stats->priced_count || ! $stats->min_price) {
            return null;
        }

        return [
            'min' => (float) $stats->min_price,
            'max' => (float) $stats->max_price,
            'priced_count' => (int) $stats->priced_count,
        ];
    }

    /**
     * bkz. priceRangeFor() ayni tarihli yorum - bolum-bazli sabit SSS
     * listesinin BASINA, o sayfaya ozgu (gercek fiyat/kurum sayisi
     * iceren) 1-2 soru-cevap ekler. Fiyat verisi yoksa (bu bolgede henuz
     * fiyat girilmis kurum yoksa) sadece kurum sayisina dayanan tek bir
     * soru eklenir - hicbir zaman uydurma/genellenmis bir rakam kullanilmaz.
     */
    private function withLocalFaq(array $baseFaq, string $cityName, ?string $districtName, string $topicTitle, ?array $priceRange, int $facilityCount): array
    {
        $location = trim(($districtName ? $districtName.', ' : '').$cityName);
        $local = [];

        if ($priceRange) {
            $min = number_format($priceRange['min'], 0, ',', '.');
            $max = number_format($priceRange['max'], 0, ',', '.');
            $answer = $priceRange['min'] === $priceRange['max']
                ? "{$location} bölgesinde {$topicTitle} fiyatları platformumuzdaki güncel verilere göre aylık {$min} TL civarındadır."
                : "{$location} bölgesinde {$topicTitle} fiyatları platformumuzdaki güncel verilere göre aylık {$min} TL ile {$max} TL arasında değişmektedir. Kesin fiyat için kuruma doğrudan teklif talebi göndermenizi öneririz.";
            $local[] = ["{$location} bölgesinde {$topicTitle} fiyatları ne kadar?", $answer];
        }

        if ($facilityCount > 0) {
            $local[] = [
                "{$location} bölgesinde kaç {$topicTitle} kurumu var?",
                "Şu anda platformumuzda {$location} bölgesinde {$facilityCount} adet {$topicTitle} kurumu listelenmektedir, güncel listeye sayfadaki kurum kartlarından ulaşabilirsiniz.",
            ];
        }

        return [...$local, ...$baseFaq];
    }

    private function resolveDistrict(array $districts, ?string $districtSlug): ?string
    {
        if (! $districtSlug) {
            return null;
        }

        foreach ($districts as $candidate) {
            if (Str::slug($candidate) === $districtSlug) {
                return $candidate;
            }
        }

        abort(404);
    }
}
