<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ContentPage;
use App\Models\Facility;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect();
        $brands = config('brands.brands');
        $sections = service_sections();
        $cities = City::orderBy('slug')->get(['slug', 'updated_at']);

        foreach ($brands as $brandSlug => $brand) {
            $prefix = $this->brandBaseUrl($brandSlug);
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

            foreach ($sections as $section) {
                $urls->push($this->url($prefix.'?bolum='.$section['slug'], 'daily', '0.9'));
                $urls->push($this->url($prefix.'/kurumlar?bolum='.$section['slug'], 'daily', '0.9'));

                foreach ($cities as $city) {
                    $urls->push($this->url($prefix.'/rehber/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));
                    $urls->push($this->url($prefix.'/fiyat-rehberi/'.$section['slug'].'/'.$city->slug, 'weekly', '0.6', $city->updated_at));
                }

                // Config'ten uretilen bolum bazli rehber/soru-cevap makaleleri
                // (bkz. config/site_content.php - $output['brands'][marka]['pages']).
                foreach (config("site_content.brands.{$brandSlug}.pages", []) as $slug => $page) {
                    if (str_starts_with($slug, $section['slug'].'-')) {
                        $urls->push($this->url($prefix.'/sayfa/'.$slug, 'monthly', '0.6'));
                    }
                }
            }

            // Admin panelden eklenen statik sayfalar (Hakkimizda, KVKK) ve
            // Bakim Rehberi makaleleri (content_pages tablosu).
            ContentPage::where('brand', $brandSlug)->get(['slug', 'updated_at'])->each(function ($page) use ($urls, $prefix) {
                $urls->push($this->url($prefix.'/sayfa/'.$page->slug, 'monthly', '0.6', $page->updated_at));
            });
        }

        Facility::published()->with('city')->orderBy('updated_at', 'desc')->chunk(200, function ($facilities) use ($urls, $brands) {
            foreach ($facilities as $facility) {
                foreach ($brands as $brandSlug => $brand) {
                    if ($facility->isInBrandScope($brand['category_scope'])) {
                        $urls->push($this->url(url('/site/'.$brandSlug.'/kurumlar/'.$facility->slug), 'weekly', '0.8', $facility->updated_at));
                    }
                }
            }
        });

        return response()
            ->view('sitemap', ['urls' => $urls->unique('loc')->values()], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
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
