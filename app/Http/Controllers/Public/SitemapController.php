<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ContentPage;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $brand = current_brand();

        $xml = Cache::remember("sitemap:{$brand['slug']}", now()->addHours(6), function () use ($brand) {
            return $this->buildXml($brand);
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function buildXml(array $brand): string
    {
        $urls = collect();
        $brandSlug = $brand['slug'];
        $prefix = $this->brandBaseUrl($brandSlug);
        $sections = service_sections();
        $cities = City::orderBy('slug')->get(['id', 'slug', 'name', 'updated_at']);

        // 12 Agustos 2026: kullanicinin talebi - "3 farkli bolum 3 farkli
        // siteden toplansin, birebir ayni SEO/kopya tehlikesi olmasin". 3
        // marka AYNI envanteri (category_scope hepsinde full) paylastigi
        // icin, bir markanin KENDI bolumu DISINDAKI il/ilce rehberi, fiyat
        // rehberi, vitrin ve kurum sayfalarini sitemap'e eklemek digerinde
        // BIREBIR AYNI icerigi iki kez Google'a bildirmek anlamina gelirdi.
        // Bu yuzden sitemap'teki bolum-bazli URL'ler SADECE markanin kendi
        // varsayilan bolumunu kapsar (digerleri hala sitede gezilebilir,
        // sadece Google'a ayrica bildirilmez - ayrica bkz.
        // layouts/brand.blade.php'deki noindex korumasi, otoriter katman
        // odur, bu sadece kesif/oncelik katmanidir).
        $ownSection = $sections[$brand['default_section']] ?? null;
        $sectionsForSitemap = $ownSection ? [$ownSection] : [];
        $ownScopes = $ownSection['scopes'] ?? $brand['category_scope'];
        $categories = FacilityCategory::whereIn('brand_scope', $ownScopes)->get();
        $bolumQuery = $ownSection ? '?bolum='.$ownSection['slug'] : '';

        $urls->push($this->url($prefix, 'daily', '1.0'));
        $urls->push($this->url($prefix.'/kurumlar', 'daily', '0.9'));
        $urls->push($this->url($prefix.'/karar-sihirbazi', 'weekly', '0.8'));
        $urls->push($this->url($prefix.'/bakim-danismani', 'weekly', '0.8'));
        $urls->push($this->url($prefix.'/karsilastir', 'weekly', '0.7'));
        $urls->push($this->url($prefix.'/favoriler', 'weekly', '0.7'));
        $urls->push($this->url($prefix.'/sss', 'monthly', '0.6'));
        $urls->push($this->url($prefix.'/bakim-rehberi', 'weekly', '0.7'));
        $urls->push($this->url($prefix.'/fiyat-rehberi', 'weekly', '0.7'));
        $urls->push($this->url($prefix.'/istatistikler', 'weekly', '0.5'));
        // Vitrin (kesif) sayfalari: bolumsuz hali TUM kategorileri karistirip
        // 3 markada da ayni cikardigi icin artik markanin KENDI bolumune
        // qualifiy edilmis halde sitemap'e giriyor (bkz. grid-page.blade.php
        // ve recent-photos.blade.php'deki noindex esleseni).
        $urls->push($this->url($prefix.'/dogrulanmis-kurumlar'.$bolumQuery, 'daily', '0.6'));
        $urls->push($this->url($prefix.'/son-guncellenen-kurumlar'.$bolumQuery, 'daily', '0.6'));
        $urls->push($this->url($prefix.'/yeni-eklenen-kurumlar'.$bolumQuery, 'daily', '0.6'));
        $urls->push($this->url($prefix.'/son-sahiplenilen-kurumlar'.$bolumQuery, 'daily', '0.5'));
        $urls->push($this->url($prefix.'/en-cok-goruntulenen-kurumlar'.$bolumQuery, 'daily', '0.5'));
        $urls->push($this->url($prefix.'/son-eklenen-fotograflar'.$bolumQuery, 'daily', '0.4'));

        $districtCombos = $this->districtCombosBySection();
        $categoryCityCombos = $this->categoryCityCombos();
        $categoryDistrictCombos = $this->categoryCityDistrictCombos();

        foreach ($sectionsForSitemap as $section) {
            $urls->push($this->url($prefix.'?bolum='.$section['slug'], 'daily', '0.9'));
            $urls->push($this->url($prefix.'/kurumlar?bolum='.$section['slug'], 'daily', '0.9'));
            $urls->push($this->url($prefix.'/rehber/'.$section['slug'], 'weekly', '0.7'));

            foreach ($cities as $city) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));
                $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));

                foreach ($districtCombos[$section['slug']][$city->slug] ?? [] as $district) {
                    $urls->push($this->url(
                        $prefix.'/rehber/'.$section['slug'].'/'.$city->slug.'/'.$district['slug'],
                        $district['has_facilities'] ? 'weekly' : 'monthly',
                        $district['has_facilities'] ? '0.55' : '0.35',
                        $city->updated_at
                    ));
                }
            }

            foreach (config("site_content.brands.{$brandSlug}.pages", []) as $slug => $page) {
                if (str_starts_with($slug, $section['slug'].'-')) {
                    $urls->push($this->url($prefix.'/sayfa/'.$slug, 'monthly', '0.6'));
                }
            }
        }

        foreach ($categories as $category) {
            $section = service_section_for_scope($category->brand_scope);
            if (! $section || $section['slug'] !== ($ownSection['slug'] ?? null)) {
                continue;
            }

            foreach ($categoryCityCombos[$category->slug] ?? [] as $citySlug) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$citySlug.'/kategori/'.$category->slug, 'weekly', '0.6'));
                $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$citySlug.'/kategori/'.$category->slug, 'weekly', '0.6'));
            }

            foreach ($categoryDistrictCombos[$category->slug] ?? [] as $combo) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$combo['city_slug'].'/kategori/'.$category->slug.'/'.$combo['district_slug'], 'weekly', '0.5'));
                $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$combo['city_slug'].'/kategori/'.$category->slug.'/'.$combo['district_slug'], 'weekly', '0.5'));
            }
        }

        ContentPage::where('brand', $brandSlug)->get(['slug', 'updated_at'])->each(function ($page) use ($urls, $prefix) {
            $urls->push($this->url($prefix.'/sayfa/'.$page->slug, 'monthly', '0.6', $page->updated_at));
        });

        // 12 Agustos 2026: kullanicinin talebi - kopya icerik korumasi kurum
        // detay sayfalarina da genisletildi: sitemap artik markanin KENDI
        // bolumune ait kurumlari bildirir (digerleri hala calisir/gezilebilir,
        // sadece noindex - bkz. facilities/show.blade.php).
        Facility::discoverable()->forBrand($ownScopes)->orderBy('updated_at', 'desc')
            ->chunk(200, function ($facilities) use ($urls, $prefix) {
                foreach ($facilities as $facility) {
                    $urls->push($this->url($prefix.'/kurumlar/'.$facility->slug, 'weekly', '0.8', $facility->updated_at));
                }
            });

        return view('sitemap', ['urls' => $urls->unique('loc')->values()])->render();
    }

    /**
     * Il+ilce (bolum bazli) kombinasyonlari.
     *
     * 12 Agustos 2026: kullanicinin acik talebi - "81 il ve ilcelerinde
     * gecerli olacak": eskiden SADECE gercek kurumu (>=3) olan ilceler
     * sitemap'e giriyordu, geri kalan ~973 ilcenin buyuk cogunlugu Google'a
     * hic bildirilmiyordu (LocationGuideController::show() sayfayi 0 kurumla
     * da render eder, ama sitemap'te olmayan bir sayfayi Google kolay kolay
     * kesfetmez). Artik HER (bolum x il x gercek ilce) kombinasyonu sitemap'e
     * giriyor - gercek kurumu olanlar onceki gibi 'weekly'/0.55 ile, kurumu
     * olmayanlar (henuz) daha dusuk sinyalli 'monthly'/0.35 ile. Boylece
     * mevcut guclu sayfalarin onceligi ASLA dusurulmez, sadece eksik il/ilce
     * kapsami tamamlanir (bkz. buildXml() cagrisi).
     *
     * @return array<string, array<string, array<int, array{slug: string, has_facilities: bool}>>> [sectionSlug => [citySlug => [{slug, has_facilities}, ...]]]
     */
    private function districtCombosBySection(): array
    {
        $rows = DB::table('facilities')
            ->join('facility_categories', 'facilities.facility_category_id', '=', 'facility_categories.id')
            ->join('cities', 'facilities.city_id', '=', 'cities.id')
            ->where('facilities.is_published', true)
            ->whereNotNull('facilities.district')
            ->where('facilities.district', '!=', '')
            ->select('cities.slug as city_slug', 'cities.name as city_name', 'facilities.district', 'facility_categories.brand_scope', DB::raw('count(*) as cnt'))
            ->groupBy('cities.slug', 'cities.name', 'facilities.district', 'facility_categories.brand_scope')
            ->get();

        $bucketed = [];
        foreach ($rows as $row) {
            $section = service_section_for_scope($row->brand_scope);
            if (! $section) {
                continue;
            }

            $key = $section['slug'].'|'.$row->city_slug;
            $bucketed[$key]['city_name'] = $row->city_name;
            $bucketed[$key]['districts'][$row->district] = ($bucketed[$key]['districts'][$row->district] ?? 0) + $row->cnt;
        }

        // Gercek kurumu (>=3) olan ilceler: [sectionSlug|citySlug][districtSlug] = true
        $withFacilities = [];
        foreach ($bucketed as $key => $data) {
            $validDistricts = collect(districts_for_city($data['city_name']));

            foreach ($data['districts'] as $districtName => $count) {
                if ($count < 3) {
                    continue;
                }

                $slug = Str::slug($districtName);
                if (! $validDistricts->first(fn ($d) => Str::slug($d) === $slug)) {
                    continue;
                }

                $withFacilities[$key][$slug] = true;
            }
        }

        $sections = service_sections();
        $cities = City::orderBy('slug')->get(['slug', 'name']);

        $result = [];
        foreach ($sections as $section) {
            foreach ($cities as $city) {
                $key = $section['slug'].'|'.$city->slug;

                foreach (districts_for_city($city->name) as $districtName) {
                    $slug = Str::slug($districtName);
                    $result[$section['slug']][$city->slug][] = [
                        'slug' => $slug,
                        'has_facilities' => isset($withFacilities[$key][$slug]),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Il+kategori kombinasyonlari (>= 3 kurum).
     *
     * @return array<string, array<int, string>> [categorySlug => [citySlug, ...]]
     */
    private function categoryCityCombos(): array
    {
        $rows = DB::table('facilities')
            ->join('facility_categories', 'facilities.facility_category_id', '=', 'facility_categories.id')
            ->join('cities', 'facilities.city_id', '=', 'cities.id')
            ->where('facilities.is_published', true)
            ->select('cities.slug as city_slug', 'facility_categories.slug as category_slug', DB::raw('count(*) as cnt'))
            ->groupBy('cities.slug', 'facility_categories.slug')
            ->havingRaw('count(*) >= 3')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->category_slug][] = $row->city_slug;
        }

        return $result;
    }

    /**
     * Il+ilce+kategori kombinasyonlari (>= 3 kurum, en uzun-kuyruk katman).
     *
     * @return array<string, array<int, array{city_slug: string, district_slug: string}>>
     */
    private function categoryCityDistrictCombos(): array
    {
        $rows = DB::table('facilities')
            ->join('facility_categories', 'facilities.facility_category_id', '=', 'facility_categories.id')
            ->join('cities', 'facilities.city_id', '=', 'cities.id')
            ->where('facilities.is_published', true)
            ->whereNotNull('facilities.district')
            ->where('facilities.district', '!=', '')
            ->select('cities.slug as city_slug', 'cities.name as city_name', 'facilities.district', 'facility_categories.slug as category_slug', DB::raw('count(*) as cnt'))
            ->groupBy('cities.slug', 'cities.name', 'facilities.district', 'facility_categories.slug')
            ->havingRaw('count(*) >= 3')
            ->get();

        $result = [];
        $cityDistrictCache = [];
        foreach ($rows as $row) {
            if (! array_key_exists($row->city_name, $cityDistrictCache)) {
                $cityDistrictCache[$row->city_name] = collect(districts_for_city($row->city_name));
            }

            $slug = Str::slug($row->district);
            if (! $cityDistrictCache[$row->city_name]->first(fn ($d) => Str::slug($d) === $slug)) {
                continue;
            }

            $result[$row->category_slug][] = ['city_slug' => $row->city_slug, 'district_slug' => $slug];
        }

        return $result;
    }

    private function url(string $loc, string $changefreq, string $priority, $lastmod = null): array
    {
        return [
            'loc' => $loc,
            'lastmod' => optional($lastmod)->toAtomString() ?: now()->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    private function brandBaseUrl(string $brandSlug): string
    {
        $brand = config("brands.brands.{$brandSlug}", []);
        $domain = $brand['domains'][0] ?? null;

        if (! $domain) {
            return url('/site/'.$brandSlug);
        }

        if (preg_match('#^https?://#i', $domain)) {
            return rtrim($domain, '/');
        }

        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: request()->getScheme();
        return $scheme.'://'.rtrim($domain, '/');
    }
}
