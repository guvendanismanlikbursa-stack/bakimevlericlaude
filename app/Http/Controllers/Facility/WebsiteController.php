<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\FacilityWebsite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 23 Ekim 2026: kullanıcının talebi - kurum panelinde "Web Sitem".
// Sitesi olmayan kurum, kendi bilgilerinden hazırlanmış sitesinin
// önizlemesini görür; sitesi olan kurum "ikinci siteniz" teşvikini görür.
// Kurum başlık, alt yazı, hakkımızda, öne çıkan fotoğraf, renk ve görünen
// bölümleri kendisi düzenler. Yayına alma yalnızca admin'dedir; alan adını
// (30 Ekim 2026'dan beri) yayındaki sitesine kurum da bağlayabilir.
class WebsiteController extends Controller
{
    public function index()
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility->load(['images', 'subscriptionPackage']);
        $site = FacilityWebsite::forFacility($facility);
        $images = $facility->images->reject(fn ($img) => str_starts_with((string) $img->path, 'facilities/demo/'))->values();
        $hasSitePackage = $facility->isMember() && (bool) $facility->subscriptionPackage?->includesWebsite();

        $proFiyat = \App\Models\Setting::get('kurum_sitesi_pro_fiyat', '14.900 ₺ / yıl');

        return view('themes._shared.facility.website', compact('user', 'facility', 'site', 'images', 'hasSitePackage', 'proFiyat'));
    }

    public function update(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;
        $site = FacilityWebsite::forFacility($facility);

        $data = self::validateContent($request, $facility->id);
        // Profesyonel Site içeriği yalnızca paketi etkin kurumdan kabul edilir.
        if ($site->isPro() && \App\Services\KurumSitesiProForm::submitted($request)) {
            $data = array_merge($data, app(\App\Services\KurumSitesiProForm::class)->validated($request, $site));
        }
        $changed = $site->applyChanges($data, 'kurum', $user->id, $user->name ?: $user->phone);

        return redirect(brand_route('facility.website.index'))
            ->with('success', $changed ? 'Siteniz güncellendi.' : 'Değişiklik yapılmadı.');
    }

    /**
     * 30 Ekim 2026: kullanıcının talebi - site satışı yapılmış (yayındaki) kurum kendi
     * alan adını yazar. Alan adı sunucumuza bağlı ve sertifikası hazır değilse
     * kaydedilmez (yoksa site çalışmayan adrese yönlenirdi). Boş gönderilirse kaldırılır.
     */
    public function domain(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $site = FacilityWebsite::forFacility($user->facility);
        abort_unless($site->isLive(), 403);
        $request->validate(['custom_domain' => 'nullable|string|max:190']);
        $domain = FacilityWebsite::normalizeDomain($request->input('custom_domain'));
        $geri = redirect(brand_route('facility.website.index'));

        if (! $domain) {
            if ($site->custom_domain) {
                $site->update(['custom_domain' => null]);
            }

            return $geri->with('success', 'Alan adı kaldırıldı; siteniz platform adresinden yayında.');
        }
        if ($domain === $site->custom_domain) {
            return $geri->with('success', 'Değişiklik yapılmadı.');
        }
        if ($hata = FacilityWebsite::domainError($domain, $site->id)) {
            return $geri->withErrors(['custom_domain' => $hata])->withInput();
        }
        if (! \App\Http\Controllers\Public\AlanAdiDogrulamaController::bizeBagli($domain)) {
            \Illuminate\Support\Facades\Log::info('Kurum alan adı doğrulanamadı', ['facility_id' => $site->facility_id, 'domain' => $domain]);

            return $geri->withErrors(['custom_domain' => 'Bu alan adı henüz sitenize bağlanmamış. Alan adınızın sitenize yönlendirilmesi ve güvenlik sertifikası için bize yazın; bağlantı tamamlanınca buradan tekrar kaydedebilirsiniz.'])->withInput();
        }
        $site->update(['custom_domain' => $domain]);

        return $geri->with('success', 'Alan adınız kaydedildi: '.$domain);
    }

    /**
     * Kurum paneli ve admin panelinde ortak içerik doğrulaması.
     */
    public static function validateContent(Request $request, int $facilityId): array
    {
        $data = $request->validate([
            'hero_title' => 'nullable|string|max:150',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:5000',
            'hero_image_id' => ['nullable', 'integer', Rule::exists('facility_images', 'id')->where('facility_id', $facilityId)],
            'theme' => ['required', Rule::in(array_keys(FacilityWebsite::THEMES))],
            'sections' => 'nullable|array',
            'sections.*' => 'in:1',
        ]);
        $acik = array_keys($data['sections'] ?? []);
        $data['sections'] = collect(array_keys(FacilityWebsite::SECTIONS))->mapWithKeys(fn ($k) => [$k => in_array($k, $acik, true)])->all();
        foreach (['hero_title', 'hero_subtitle', 'about_text'] as $alan) {
            $data[$alan] = isset($data[$alan]) && trim((string) $data[$alan]) !== '' ? trim((string) $data[$alan]) : null;
        }
        $data['hero_image_id'] = $data['hero_image_id'] ?? null;

        return $data;
    }
}
