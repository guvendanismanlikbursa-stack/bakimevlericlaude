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
        $categories = FacilityCategory::whereIn('brand_scope', $brand['category_scope'])->get();

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
        $urls->push($this->url($prefix.'/dogrulanmis-kurumlar', 'daily', '0.6'));
        $urls->push($this->url($prefix.'/son-guncellenen-kurumlar', 'daily', '0.6'));
        $urls->push($this->url($prefix.'/yeni-eklenen-kurumlar', 'daily', '0.6'));
        $urls->push($this->url($prefix.'/son-sahiplenilen-kurumlar', 'daily', '0.5'));
        $urls->push($this->url($prefix.'/en-cok-goruntulenen-kurumlar', 'daily', '0.5'));
        $urls->push($this->url($prefix.'/son-eklenen-fotograflar', 'daily', '0.4'));

        $districtCombos = $this->districtCombosBySection();
        $categoryCityCombos = $this->categoryCityCombos();
        $categoryDistrictCombos = $this->categoryCityDistrictCombos();

        foreach ($sections as $section) {
            $urls->push($this->url($prefix.'?bolum='.$section['slug'], 'daily', '0.9'));
            $urls->push($this->url($prefix.'/kurumlar?bolum='.$section['slug'], 'daily', '0.9'));

            foreach ($cities as $city) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));
                $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));

                foreach ($districtCombos[$section['slug']][$city->slug] ?? [] as $districtSlug) {
                    $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$city->slug.'/'.$districtSlug, 'weekly', '0.55', $city->updated_at));
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
            if (! $section) {
                continue;
            }

            foreach ($categoryCityCombos[$category->slug] ?? [] as $citySlug) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$citySlug.'/kategori/'.$category->slug, 'weekly', '0.6'));
                $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$citySlug.'/kategori/'.$category->slug, 'weekly', '0.6'));
            }

            foreach ($categoryDistrictCombos[$category->slug] ?? [] as $combo) {
                $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$combo['city_slug'].'/kategori/'.$category->slug.'/'.$combo['district_slug'], 'weekly', '0.5'));
            }
        }

        ContentPage::where('brand', $brandSlug)->get(['slug', 'updated_at'])->each(function ($page) use ($urls, $prefix) {
            $urls->push($this->url($prefix.'/sayfa/'.$page->slug, 'monthly', '0.6', $page->updated_at));
        });

        Facility::published()->forBrand($brand['category_scope'])->orderBy('updated_at', 'desc')
            ->chunk(200, function ($facilities) use ($urls, $prefix) {
                foreach ($facilities as $facility) {
                    $urls->push($this->url($prefix.'/kurumlar/'.$facility->slug, 'weekly', '0.8', $facility->updated_at));
                }
            });

        return view('sitemap', ['urls' => $urls->unique('loc')->values()])->render();
    }

    /**
     * Il+ilce (bolum bazli) kombinasyonlari: bir bolumun kapsadigi tum
     * kategorilerin (brand_scope) o il+ilcedeki toplam kurum sayisi >= 3 ise.
     * LocationGuideController::show() zaten {districtSlug?} destekliyor,
     * burada sadece sitemap'e eklenecek gercek/eslenebilir kombinasyonlar hesaplanir.
     *
     * @return array<string, array<string, array<int, string>>> [sectionSlug => [citySlug => [districtSlug, ...]]]
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

        $result = [];
        foreach ($bucketed as $key => $data) {
            [$sectionSlug, $citySlug] = explode('|', $key, 2);
            $validDistricts = collect(districts_for_city($data['city_name']));

            foreach ($data['districts'] as $districtName => $count) {
                if ($count < 3) {
                    continue;
                }

                $slug = Str::slug($districtName);
                if (! $validDistricts->first(fn ($d) => Str::slug($d) === $slug)) {
                    continue;
                }

                $result[$sectionSlug][$citySlug][] = $slug;
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
