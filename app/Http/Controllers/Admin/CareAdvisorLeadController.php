<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareAdvisorLead;
use App\Models\City;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;

// 13 Ekim 2026 (3): kullanicinin talebi - Pusula'yi (eski "Bakim Danismani")
// isim/telefon yazmadan dolduran aileler de artik kaydediliyor (bkz.
// CareAdvisorController::results()); amac iletisim olmasa da bolgesel
// ihtiyaci gorebilmek.
//
// 13 Ekim 2026 (4): kullanıcının talebi - "yarıda bırakılanlar da anketler
// sayfasında listelenecek, admin detaylı inceleyebilecek, gerekirse
// düzenleme ve silme yapabilecek". Bu yüzden artık SADECE okuma değil,
// show()/update()/destroy() de var.
//
// 13 Ekim 2026 (9): kullanıcının talebi - "zamanla bölüm/il/durum gibi
// segmentlerde filtrelemeye ihtiyaç olacak". Admin dashboard'daki il/bölüm
// filtresiyle AYNI desen (native GET + onchange submit + noscript yedek).
class CareAdvisorLeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = CareAdvisorLead::with(['city', 'category'])
            ->when($request->filled('il'), fn ($q) => $q->where('city_id', $request->integer('il')))
            ->when($request->filled('bolum'), fn ($q) => $q->where('section', $request->string('bolum')))
            ->when($request->input('durum') === 'tamamlandi', fn ($q) => $q->whereNotNull('completed_at'))
            ->when($request->input('durum') === 'yarida', fn ($q) => $q->whereNull('completed_at'))
            ->when($request->input('iletisim') === 'var', fn ($q) => $q->whereNotNull('phone'))
            ->when($request->input('iletisim') === 'yok', fn ($q) => $q->whereNull('phone'))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $concernLabels = $this->concernLabels();
        $filterCities = City::orderBy('name')->get();
        $filterSections = config('brands.service_sections');

        return view('admin.care-advisor-leads.index', compact('leads', 'concernLabels', 'filterCities', 'filterSections'));
    }

    public function show(CareAdvisorLead $careAdvisorLead)
    {
        $careAdvisorLead->load(['city', 'category']);
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::cachedAll();

        return view('admin.care-advisor-leads.show', [
            'lead' => $careAdvisorLead,
            'cities' => $cities,
            'categories' => $categories,
            'concernLabels' => $this->concernLabels(),
        ]);
    }

    public function update(Request $request, CareAdvisorLead $careAdvisorLead)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'patient_age' => 'nullable|integer|min:0|max:120',
            'gender' => 'nullable|string|max:30',
            'condition' => 'nullable|string|max:120',
            'city_id' => 'nullable|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'facility_category_id' => 'nullable|exists:facility_categories,id',
            'budget_max' => 'nullable|integer|min:0',
            'section' => 'nullable|string|in:'.implode(',', array_keys(config('brands.service_sections'))),
        ]);

        $careAdvisorLead->update($data);

        return redirect()->route('admin.care-advisor-leads.show', $careAdvisorLead)->with('success', 'Kayıt güncellendi.');
    }

    public function destroy(CareAdvisorLead $careAdvisorLead)
    {
        $careAdvisorLead->delete();

        return redirect()->route('admin.care-advisor-leads.index')->with('success', 'Kayıt silindi.');
    }

    // concerns[] sütununda sadece anahtar (örn. 'needs_physio') saklanır -
    // 3 bölümün advisor_concerns listesini tek bir anahtar->etiket
    // haritasına birleştirip görüntülemede okunabilir etiket gösteriyoruz.
    private function concernLabels(): array
    {
        $concernLabels = [];
        foreach (config('brands.service_sections') as $section) {
            foreach ($section['advisor_concerns'] ?? [] as $concern) {
                $concernLabels[$concern['key']] = $concern['label'];
            }
        }

        return $concernLabels;
    }
}
