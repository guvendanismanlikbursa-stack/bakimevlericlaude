<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
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
        $districtName = null;

        if ($districtSlug) {
            foreach ($districts as $candidate) {
                if (Str::slug($candidate) === $districtSlug) {
                    $districtName = $candidate;
                    break;
                }
            }
            abort_if(! $districtName, 404);
        }

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

        return view("themes.{$brand['theme']}.location-guide", compact(
            'brand',
            'section',
            'city',
            'districtName',
            'nearDistricts',
            'facilities',
            'content'
        ));
    }
}