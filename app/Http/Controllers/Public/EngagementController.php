<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class EngagementController extends Controller
{
    public function wizard(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $cities = City::orderBy('name')->get();
        $scope = $activeSection['scopes'] ?: $brand['category_scope'];
        $categories = FacilityCategory::whereIn('brand_scope', $scope)->orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $sectionServices = $activeSection['features'] ?? [];

        return view("themes.{$brand['theme']}.engagement.wizard", compact(
            'brand',
            'sections',
            'activeSection',
            'cities',
            'categories',
            'districtMap',
            'sectionServices'
        ));
    }

    public function compare(Request $request)
    {
        return $this->board('compare');
    }

    public function favorites(Request $request)
    {
        return $this->board('favorites');
    }

    public function bulkQuote(Request $request)
    {
        return $this->board('bulk-quote');
    }

    /**
     * Favoriler tarayicida localStorage'da tutulur (kisisel liste), ama
     * "Kurum Performans Sayfasi"nda goruntulenecek toplam favori sayisi icin
     * bu sayac kullanilir. Ayni tarayicinin ayni kurumu birden fazla kez
     * favoriye ekleyip cikarmasi sayaci sismesin diye uzun omurlu bir
     * cerez ile "bu tarayici bu kurumu daha once favoriledi mi" kontrolu
     * yapilir: ilk eklemede +1 sayilir, sonraki ekleme/cikarmalar sayaci
     * degistirmez (kac FARKLI tarayicinin ilgi gosterdiginin yaklasik
     * bir olcusudur, "su an favoride olan sayisi" degildir).
     */
    public function toggleFavoriteCount(Request $request, string $slug)
    {
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $slug)->firstOrFail();

        if ($request->input('action') !== 'remove') {
            $cookieName = "fav_seen_{$facility->id}";

            if (! $request->cookie($cookieName)) {
                $facility->increment('favorites_count');
                Cookie::queue(Cookie::forever($cookieName, '1'));
            }
        }

        return response()->json(['ok' => true]);
    }

    private function board(string $mode)
    {
        $brand = current_brand();
        $facilities = Facility::published()
            ->forBrand($brand['category_scope'])
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->limit(80)
            ->get();

        $facilitiesForJs = $facilities->map(function (Facility $facility) {
            $section = service_section_for_scope($facility->category?->brand_scope);
            $image = $facility->images->first();

            return [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'city' => $facility->city?->name,
                'district' => $facility->district,
                'category' => $facility->category?->name,
                'section' => $section['title'] ?? null,
                'rating' => number_format((float) $facility->rating, 1),
                'price_min' => $facility->price_min ? number_format($facility->price_min, 0, ',', '.') . ' TL' : 'Fiyat iste',
                'capacity' => $facility->capacity ?: '-',
                'services' => array_slice($facility->services ?? [], 0, 5),
                'description' => $facility->description,
                'image' => $image ? asset('storage/' . $image->path) : null,
                'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
            ];
        })->values();

        return view("themes.{$brand['theme']}.engagement.{$mode}", compact('brand', 'facilitiesForJs'));
    }
}