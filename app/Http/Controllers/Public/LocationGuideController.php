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

        $query = Facility::published()
            ->forBrand($section['scopes'])
            ->where('city_id', $city->id)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating');

        if ($districtName) {
            $query->where('district', $districtName);
        }

        $facilities = $query->limit(12)->get();
        $nearDistricts = collect($districts)->take(18)->values();
        $content = site_section_content($brand['slug'], $section['slug']);
        $sectionCategories = FacilityCategory::whereIn('brand_scope', $section['scopes'])->orderBy('name')->get();
        $category = null;

        return view("themes.{$brand['theme']}.location-guide", compact(
            'brand',
            'section',
            'city',
            'districtName',
            'nearDistricts',
            'facilities',
            'content',
            'category',
            'sectionCategories'
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

        $query = Facility::published()
            ->where('facility_category_id', $category->id)
            ->where('city_id', $city->id)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating');

        if ($districtName) {
            $query->where('district', $districtName);
        }

        $facilities = $query->limit(12)->get();
        $nearDistricts = collect($districts)->take(18)->values();
        $content = site_section_content($brand['slug'], $section['slug']);
        $sectionCategories = FacilityCategory::whereIn('brand_scope', $section['scopes'])->orderBy('name')->get();

        return view("themes.{$brand['theme']}.location-guide", compact(
            'brand',
            'section',
            'city',
            'districtName',
            'nearDistricts',
            'facilities',
            'content',
            'category',
            'sectionCategories'
        ));
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
