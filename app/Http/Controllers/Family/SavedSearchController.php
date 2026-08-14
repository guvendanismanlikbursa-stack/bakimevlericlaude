<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FacilityCategory;
use App\Models\FamilySavedSearch;
use App\Models\FamilyUser;
use Illuminate\Http\Request;

// 14 Agustos 2026: kullanicinin talebi - aile bir arama/filtreyi kaydedip,
// o kriterlere uyan YENI bir kurum eklendiginde bildirim alabilsin (bkz.
// App\Console\Commands\NotifyFamilySavedSearches).
class SavedSearchController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        $family = FamilyUser::findOrFail(session('family_user_id'));

        $data = $request->validate([
            'section_slug' => 'required|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'category' => 'nullable|string',
            'service' => 'nullable|string',
            'price_tier' => 'nullable|string',
            'q' => 'nullable|string|max:150',
        ]);

        $section = service_section($data['section_slug']);
        abort_if(empty($section), 404);

        $filters = collect($data)->except('section_slug')->filter()->all();

        FamilySavedSearch::create([
            'family_user_id' => $family->id,
            'brand' => $brand['slug'],
            'section_slug' => $data['section_slug'],
            'filters' => $filters,
            'label' => $this->buildLabel($section, $filters),
            'last_checked_at' => now(),
        ]);

        return back()->with('success', 'Arama kaydedildi. Bu kriterlere uyan yeni bir kurum eklendiğinde size haber vereceğiz.');
    }

    public function destroy(Request $request)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));

        // 14 Agustos 2026: bu route grubu (bkz. routes/web.php $siteRoutes)
        // hem duz hem marka-onekli halde iki kez kaydediliyor - otomatik
        // route-model binding bu kurulumda guvenilir calismiyor (ayni
        // nedenle Family\DashboardController::quoteFromRoute() de elle
        // cozumluyor) - bilerek elle findOrFail kullanildi.
        $savedSearch = FamilySavedSearch::where('id', $request->route('savedSearch'))
            ->where('family_user_id', $family->id)
            ->firstOrFail();

        $savedSearch->delete();

        return back()->with('success', 'Kayıtlı arama silindi.');
    }

    private function buildLabel(array $section, array $filters): string
    {
        $parts = [$section['title']];

        if (! empty($filters['city'])) {
            $city = City::where('slug', $filters['city'])->first();
            if ($city) {
                $parts[] = $city->name;
            }
        }

        if (! empty($filters['category'])) {
            $category = FacilityCategory::where('slug', $filters['category'])->first();
            if ($category) {
                $parts[] = $category->name;
            }
        }

        if (! empty($filters['q'])) {
            $parts[] = '"'.$filters['q'].'"';
        }

        return implode(' · ', $parts);
    }
}
