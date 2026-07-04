<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $sectionScopes = $activeSection['scopes'];

        $featured = Facility::published()
            ->forBrand($sectionScopes)
            ->where('is_featured', true)
            ->with(['city', 'category', 'images'])
            ->limit(6)
            ->get();

        $preRegistered = Facility::published()
            ->forBrand($sectionScopes)
            ->where('is_claimed', false)
            ->where('source', 'google_maps_veri_cekici')
            ->with(['city', 'category', 'images'])
            ->latest()
            ->limit(12)
            ->get();

        $categories = FacilityCategory::whereIn('brand_scope', $sectionScopes)->orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection['features'];

        return view("themes.{$brand['theme']}.home", compact(
            'featured',
            'preRegistered',
            'categories',
            'cities',
            'sections',
            'activeSection',
            'districtsByCity',
            'sectionServices'
        ));
    }
}