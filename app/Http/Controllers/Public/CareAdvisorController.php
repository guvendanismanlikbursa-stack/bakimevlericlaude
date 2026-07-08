<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FacilityCategory;
use App\Models\Setting;
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

        $defaults = config('platform.default_price_tiers');
        $priceTiers = [
            ['value' => Setting::get('price_tier_standart_min', $defaults['standart_min']), 'label' => '🟢 Ekonomik ve altı'],
            ['value' => Setting::get('price_tier_premium_min', $defaults['premium_min']), 'label' => '🔵 Standart ve altı'],
            ['value' => Setting::get('price_tier_ultra_min', $defaults['ultra_min']), 'label' => '🟣 Premium ve altı'],
        ];

        return view("themes.{$brand['theme']}.care-advisor.form", compact('brand', 'sections', 'activeSection', 'cities', 'categories', 'priceTiers'));
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
            'concerns' => 'nullable|array',
            'concerns.*' => 'string',
        ]);

        $city = $data['city'] ?? null ? City::where('slug', $data['city'])->first() : null;
        $category = $data['category'] ?? null ? FacilityCategory::where('slug', $data['category'])->first() : null;
        $selectedConcerns = $data['concerns'] ?? [];

        $criteria = [
            'patient_age' => $data['patient_age'] ?? null,
            'gender' => $data['gender'] ?? null,
            'condition' => $data['condition'] ?? null,
            'city_id' => $city?->id,
            'facility_category_id' => $category?->id,
            'budget_max' => $data['budget_max'] ?? null,
        ];

        // Bölüme (Yaşlı Bakım/Çocuk/Rehabilitasyon) özel "durum" secenekleri
        // config/brands.php > advisor_concerns icinde tanimli; her biri kurum
        // 'services' alaninda aranacak anahtar kelime->etiket eslesmesi tasir.
        $keywordWeights = [];
        foreach ($activeSection['advisor_concerns'] ?? [] as $concern) {
            if (in_array($concern['key'], $selectedConcerns, true)) {
                $keywordWeights = array_merge($keywordWeights, $concern['keywords']);
            }
        }
        if (! empty($data['condition'])) {
            $keywordWeights[mb_strtolower($data['condition'])] = $data['condition'];
        }

        $results = $matcher->match($criteria, $activeSection['scopes'], $keywordWeights)->take(12);

        return view("themes.{$brand['theme']}.care-advisor.results", compact('brand', 'activeSection', 'results', 'criteria', 'city', 'category'));
    }
}
