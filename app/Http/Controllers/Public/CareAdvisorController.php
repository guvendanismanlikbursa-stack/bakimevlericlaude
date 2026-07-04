<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FacilityCategory;
use App\Services\CareAdvisorMatchService;
use Illuminate\Http\Request;

// "Bakim Danismani": orijinal yol haritasinin "eksik olan EN BUYUK ozellik"
// dedigi modul. Hasta yasi, durum, sehir, butce, cinsiyet, yatalak/demans
// bilgisi alinir; puanlanmis "Size uygun N kurum bulundu" sonucu doner.
class CareAdvisorController extends Controller
{
    public function form(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::whereIn('brand_scope', $activeSection['scopes'])->orderBy('name')->get();

        return view("themes.{$brand['theme']}.care-advisor.form", compact('brand', 'sections', 'activeSection', 'cities', 'categories'));
    }

    public function results(Request $request, CareAdvisorMatchService $matcher)
    {
        $brand = current_brand();
        $activeSection = active_service_section($request->query('bolum'), $brand);

        $data = $request->validate([
            'patient_age' => 'nullable|integer|min:0|max:120',
            'gender' => 'nullable|in:kadın,erkek,belirtmek_istemiyorum',
            'condition' => 'nullable|string|max:120',
            'city' => 'nullable|string|exists:cities,slug',
            'category' => 'nullable|string|exists:facility_categories,slug',
            'budget_max' => 'nullable|integer|min:0',
            'is_bedridden' => 'nullable|boolean',
            'has_dementia' => 'nullable|boolean',
            'needs_physio' => 'nullable|boolean',
        ]);

        $city = $data['city'] ?? null ? City::where('slug', $data['city'])->first() : null;
        $category = $data['category'] ?? null ? FacilityCategory::where('slug', $data['category'])->first() : null;

        $criteria = [
            'patient_age' => $data['patient_age'] ?? null,
            'gender' => $data['gender'] ?? null,
            'condition' => $data['condition'] ?? null,
            'city_id' => $city?->id,
            'facility_category_id' => $category?->id,
            'budget_max' => $data['budget_max'] ?? null,
            'is_bedridden' => $request->boolean('is_bedridden'),
            'has_dementia' => $request->boolean('has_dementia'),
            'needs_physio' => $request->boolean('needs_physio'),
        ];

        $results = $matcher->match($criteria, $activeSection['scopes'])->take(12);

        return view("themes.{$brand['theme']}.care-advisor.results", compact('brand', 'activeSection', 'results', 'criteria', 'city', 'category'));
    }
}
