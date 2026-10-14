<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CareAdvisorLead;
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
        $categories = FacilityCategory::cachedAll()->whereIn('brand_scope', $activeSection['scopes'])->values();

        $defaults = config('platform.default_price_tiers');
        $priceTiers = [
            ['value' => Setting::get('price_tier_standart_min', $defaults['standart_min']), 'label' => '🟢 Ekonomik ve altı'],
            ['value' => Setting::get('price_tier_premium_min', $defaults['premium_min']), 'label' => '🔵 Standart ve altı'],
            ['value' => Setting::get('price_tier_ultra_min', $defaults['ultra_min']), 'label' => '🟣 Premium ve altı'],
        ];

        // 13 Ekim 2026 (4): kullanıcının talebi - "sorular açılır Pusula
        // panelinde de ilerleyecek". Kayan widget paneli bu AYNI action'ı
        // ?widget=1 ile AJAX çağırır ve sadece soru/adım HTML parçasını
        // (tam sayfa iskeleti olmadan) alır - tek kaynak, kopya form yok.
        //
        // 13 Ekim 2026 (7): kullanıcının talebi - "hiçbir şey kaydırma
        // (scroll) gerektirmeden görünmeli, ilk açılış hangi bölümdeyse o
        // bölümün soruları gelsin, tekrar seçtirmek anlamsız ve yer
        // kaplıyor". $isWidget=true iken _quiz-core.blade.php bölüm
        // seçim ızgarasını GÖSTERMEZ (zaten aktif bölüm otomatik
        // seçilmiş durumda) - tam sayfada (false) bu ızgara kalmaya devam
        // eder, orada gerçek bir gezinme özelliği.
        if ($request->boolean('widget')) {
            $isWidget = true;

            return view('themes._shared.care-advisor._quiz-core', compact('sections', 'activeSection', 'cities', 'categories', 'priceTiers', 'isWidget'));
        }

        return view("themes.{$brand['theme']}.care-advisor.form", compact('brand', 'sections', 'activeSection', 'cities', 'categories', 'priceTiers'));
    }

    /**
     * 13 Ekim 2026 (4): kullanıcının talebi - "sorulan sorulardan en az 1
     * tanesi işaretlenmesi şartı da gerekli" adımına geçerken quiz
     * panelinde/sayfasında JS "Devam Et" tetiklendikçe bu action'a (fire-
     * and-forget) istek atar; aynı progress_token ile AYNI satır güncellenir
     * - her adımda yeni satır açılmaz. Kullanıcı formu hiç bitirmese bile
     * (panel kapatılsa/sekme kapansa bile) o ana kadar girilenler admin
     * "Pusula Anketleri" ekranında "⏳ Yarıda kaldı" rozetiyle görünür.
     */
    public function progress(Request $request)
    {
        $brand = current_brand();
        $data = $request->validate([
            'progress_token' => 'required|string|max:64',
            'bolum' => 'nullable|string|max:60',
            'patient_age' => 'nullable|integer|min:0|max:120',
            'gender' => 'nullable|in:kadın,erkek,belirtmek_istemiyorum',
            'condition' => 'nullable|string|max:120',
            'city' => 'nullable|string|exists:cities,slug',
            'district' => 'nullable|string|max:120',
            'category' => 'nullable|string|exists:facility_categories,slug',
            'budget_max' => 'nullable|integer|min:0',
            'concerns' => 'nullable|array',
            'concerns.*' => 'string',
            'name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
        ]);

        $attrs = $this->leadAttributesFromRequestData($data, $brand['slug']);
        // Daha önce tamamlanmış (completed_at dolu) bir kaydı yarım
        // sanıp üzerine yazmamak için - pratikte neredeyse hiç olmaz ama
        // ucuz bir güvenlik.
        $existing = CareAdvisorLead::where('progress_token', $data['progress_token'])->whereNull('completed_at')->first();
        if ($existing) {
            $existing->update($attrs);
        } else {
            CareAdvisorLead::create(array_merge($attrs, ['progress_token' => $data['progress_token']]));
        }

        return response()->json(['ok' => true]);
    }

    private function leadAttributesFromRequestData(array $data, string $brandSlug): array
    {
        $city = $data['city'] ?? null ? City::where('slug', $data['city'])->first() : null;
        $category = $data['category'] ?? null ? FacilityCategory::where('slug', $data['category'])->first() : null;

        return [
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'patient_age' => $data['patient_age'] ?? null,
            'gender' => $data['gender'] ?? null,
            'condition' => $data['condition'] ?? null,
            'city_id' => $city?->id,
            'district' => $data['district'] ?? null,
            'facility_category_id' => $category?->id,
            'budget_max' => $data['budget_max'] ?? null,
            'concerns' => ($data['concerns'] ?? []) ?: null,
            'brand' => $brandSlug,
            // 13 Ekim 2026 (9): admin filtresi icin - form zaten gizli bir
            // "bolum" alani tasiyor (bkz. _quiz-core.blade.php), kategori
            // secilmemisse ("Farketmez") bolumu guvenilir baska hicbir
            // yerden cikaramiyorduk.
            'section' => $data['bolum'] ?? null,
        ];
    }

    public function results(Request $request, CareAdvisorMatchService $matcher)
    {
        $brand = current_brand();
        $activeSection = active_service_section($request->query('bolum'), $brand);

        $data = $request->validate([
            'progress_token' => 'nullable|string|max:64',
            'bolum' => 'nullable|string|max:60',
            'patient_age' => 'nullable|integer|min:0|max:120',
            'gender' => 'nullable|in:kadın,erkek,belirtmek_istemiyorum',
            'condition' => 'nullable|string|max:120',
            'city' => 'nullable|string|exists:cities,slug',
            'district' => 'nullable|string|max:120',
            'category' => 'nullable|string|exists:facility_categories,slug',
            'budget_max' => 'nullable|integer|min:0',
            'concerns' => 'nullable|array',
            'concerns.*' => 'string',
            'name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
        ]);

        $city = $data['city'] ?? null ? City::where('slug', $data['city'])->first() : null;
        $category = $data['category'] ?? null ? FacilityCategory::where('slug', $data['category'])->first() : null;
        $selectedConcerns = $data['concerns'] ?? [];

        // 13 Ekim 2026 (3): kullanicinin talebi - "isim/telefon yazmazsa da
        // girilen bilgiler anket verisi olarak kaydedilsin, amacimiz
        // iletisim olmasa da bolgedeki ihtiyaci gorebilmek". Artik telefon
        // SART DEGIL - tek sart, formun gercekten anlamli bir sekilde
        // doldurulmus olmasi (bos/bot istek gurultusunu disarida tutmak
        // icin il/yas/durum/secenekten en az biri). Telefon varsa kayit
        // AYNI ZAMANDA Firsat Havuzu'nda kurumlara gorunur hale gelir (bkz.
        // Facility\CareAdvisorLeadController::index() - orada whereNotNull('phone')
        // filtresi, sadece ulasilabilir kayitlari kurumlara gosterir).
        //
        // 13 Ekim 2026 (4): "yarıda kalanlar da anketler sayfasında
        // listelenecek" talebinin diğer ayağı - formu TAMAMLAYAN (buraya
        // ulaşan) kullanıcının progress_token'ı varsa, adım adım kaydedilen
        // AYNI satır tamamlanmış (completed_at) olarak güncellenir; yeni
        // bir mükerrer satır açılmaz.
        $hasMeaningfulData = $city || $category || ! empty($data['patient_age']) || ! empty($data['condition']) || ! empty($selectedConcerns);
        if ($hasMeaningfulData || ! empty($data['progress_token'])) {
            $attrs = $this->leadAttributesFromRequestData($data, $brand['slug']);
            $attrs['completed_at'] = now();
            if (! empty($data['progress_token'])) {
                $lead = CareAdvisorLead::updateOrCreate(['progress_token' => $data['progress_token']], $attrs);
            } else {
                $lead = CareAdvisorLead::create($attrs);
            }

            $this->notifyAboutCareAdvisorLead($lead);
        }

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

        // 13 Ekim 2026 (4): kullanıcının talebi - "isim telefon almadan sonuç
        // gösterilmemeli veya sadece 2 kurum gösterilmeli, tam sonuç için
        // isim ve telefon girilme şartı aranmalı". İletişim bilgisi
        // bırakmayan ziyaretçiye sadece 2 kurum (teaser) gösterilir; tam
        // listeyi görmek için isim+telefon ile aynı kriterleri tekrar
        // göndermesi istenir (bkz. results.blade.php'deki kilit banner'ı).
        $isUnlocked = ! empty($data['name']) && ! empty($data['phone']);
        $allResults = $matcher->match($criteria, $activeSection['scopes'], $keywordWeights);
        $totalMatches = $allResults->count();
        $results = $allResults->take($isUnlocked ? 12 : 2);

        return view("themes.{$brand['theme']}.care-advisor.results", compact('brand', 'activeSection', 'results', 'criteria', 'city', 'category', 'isUnlocked', 'totalMatches', 'data'));
    }

    /**
     * 14 Ekim 2026: kullanıcının talebi - "Pusula Anketleri için admin ve
     * ilgili kurum bildirimi eksikti, tamamla". Admin'e İLK tamamlamada BİR
     * KEZ (notified_admin_at damgası - sayfa yenileme/aynı token'la tekrar
     * tamamlama mükerrer bildirim göndermez). Eşleşen PREMIUM kurumlara ise
     * telefon İLK kez eklendiğinde bir kez - bu, ilk doldurmada telefon
     * verilmişse hemen, "Tam Listeyi Gör" ile sonradan eklenmişse o an olur.
     */
    private function notifyAboutCareAdvisorLead(CareAdvisorLead $lead): void
    {
        if (! $lead->notified_admin_at) {
            \App\Models\Admin::all()->each(fn ($admin) => notify_user(
                $admin, 'care_advisor_lead', 'Yeni Pusula talebi',
                trim(($lead->city->name ?? 'Belirtilmemiş il').($lead->district ? ' / '.$lead->district : '').' bölgesinden yeni bir Pusula anketi dolduruldu.'),
                ['care_advisor_lead_id' => $lead->id]
            ));
            $lead->notified_admin_at = now();
        }

        if (! $lead->notified_facility_at && ! empty($lead->phone) && $lead->city_id) {
            \App\Models\Facility::where('city_id', $lead->city_id)
                ->when($lead->facility_category_id, fn ($q) => $q->where('facility_category_id', $lead->facility_category_id))
                ->get()
                ->filter(fn ($f) => $f->isActiveSubscriber())
                ->each(function ($facility) use ($lead) {
                    \App\Models\FacilityUser::where('facility_id', $facility->id)->get()->each(fn ($u) => notify_user(
                        $u, 'care_advisor_lead_facility', 'Yeni Pusula talebi',
                        'Bölgenizde ve kurum türünüzde yeni bir Pusula talebi var, Fırsat Havuzu\'ndan inceleyip ulaşabilirsiniz.',
                        ['care_advisor_lead_id' => $lead->id]
                    ));
                });
            $lead->notified_facility_at = now();
        }

        if ($lead->isDirty(['notified_admin_at', 'notified_facility_at'])) {
            $lead->saveQuietly();
        }
    }
}
