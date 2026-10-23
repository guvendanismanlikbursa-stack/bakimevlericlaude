<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FacilityInterestLead;
use App\Models\FacilityUser;
use App\Models\FacilityWebsite;
use App\Models\OfferRequest;
use App\Services\OfferRequestNotificationService;
use Illuminate\Http\Request;

// 23 Ekim 2026: kullanıcının talebi - kurumun internet sitesi.
// Adres: bakimevleri.com/siteler/{slug}; alan adı tanımlanıp site yayına
// alınmışsa o adrese kalıcı yönlendirilir (alan adından açılışı
// ServeFacilityWebsiteDomain ara katmanı yapar). Yayında olmayan site
// yalnızca kurumun kendi yetkilisi ve admin tarafından önizlenebilir.
class KurumSitesiController extends Controller
{
    public function show(Request $request)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        $preview = $this->canPreview($site);

        if (! $site->isLive() && ! $preview) {
            abort(404);
        }
        if ($site->isLive() && $site->custom_domain && ! $request->boolean('onizleme')) {
            return redirect()->away($site->publicUrl(), 301);
        }

        return $this->render($site, ! $site->isLive(), route('kurum-sitesi.iletisim', ['slug' => $site->slug], false));
    }

    /**
     * Alan adından gelen istek (bkz. ServeFacilityWebsiteDomain).
     */
    public function showForDomain(FacilityWebsite $site)
    {
        return $this->render($site, false, '/iletisim');
    }

    public function contact(Request $request, OfferRequestNotificationService $notifier)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        abort_unless($site->isLive() || $this->canPreview($site), 404);

        return $this->handleContact($request, $site, $notifier, route('kurum-sitesi.show', ['slug' => $site->slug]));
    }

    public function contactForDomain(Request $request, FacilityWebsite $site, OfferRequestNotificationService $notifier)
    {
        return $this->handleContact($request, $site, $notifier, '/');
    }

    private function handleContact(Request $request, FacilityWebsite $site, OfferRequestNotificationService $notifier, string $back)
    {
        // Honeypot (bkz. partials/honeypot.blade.php).
        $request->validate(['website' => 'max:0']);
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'message' => 'nullable|string|max:2000',
        ]);
        $facility = $site->facility;
        $message = trim(($data['message'] ?? '')."\n\n(Kurumun internet sitesindeki iletişim formundan gönderildi.)");

        // Talep, panel hesabı olan kurumda kurum paneline (doğrudan talep)
        // düşer; olmayan kurumda admin "İlgi ve Ücret Talebi" listesine.
        if ($facility->is_claimed || $facility->is_broker_managed) {
            $offer = OfferRequest::create([
                'facility_id' => $facility->id,
                'city_id' => $facility->city_id,
                'facility_category_id' => $facility->facility_category_id,
                'brand' => 'bakimevleri',
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'message' => $message,
            ]);
            $notifier->notify($offer);
        } else {
            FacilityInterestLead::create([
                'facility_id' => $facility->id,
                'brand' => 'bakimevleri',
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'message' => $message,
            ]);
        }

        // 23 Ekim 2026: kullanıcının talebi - form, kurumun WhatsApp (cep)
        // hattına yönlendirir; mesaj hazır yazılı gelir. Talep yukarıda yine
        // kaydedilir. Cep hattı yoksa eskisi gibi teşekkür mesajı gösterilir.
        $wa = classify_phone_type($facility->phone) === 'mobile' ? normalize_whatsapp_number($facility->phone) : '';
        if ($wa) {
            $metin = 'Merhaba, internet sitenizden yazıyorum.'."\nAdım: ".$data['full_name']."\nTelefonum: ".$data['phone']
                .(trim((string) ($data['message'] ?? '')) !== '' ? "\n".trim($data['message']) : '');

            return redirect()->away('https://wa.me/'.$wa.'?text='.rawurlencode($metin));
        }

        return redirect($back.'#iletisim')->with('kurum_sitesi_basarili', 'Mesajınız kuruma iletildi, en kısa sürede sizi arayacaklar.');
    }

    private function findBySlug(string $slug): FacilityWebsite
    {
        $site = FacilityWebsite::where('slug', $slug)->first();
        abort_unless($site && $site->facility, 404);

        return $site;
    }

    private function canPreview(FacilityWebsite $site): bool
    {
        if (session('admin_id')) {
            return true;
        }
        $userId = session('facility_user_id');

        return $userId && (int) FacilityUser::whereKey($userId)->value('facility_id') === (int) $site->facility_id;
    }

    private function render(FacilityWebsite $site, bool $isPreview, string $formAction)
    {
        $facility = $site->facility->load(['city', 'category', 'images', 'googleReviews', 'approvedReviews']);
        // Gerçek fotoğraf yoksa örnek görseller "temsili" notuyla gösterilir.
        [$gallery, $temsili] = FacilityWebsite::siteGallery($facility);
        $hero = $gallery->firstWhere('id', $site->hero_image_id)
            ?? $gallery->firstWhere('is_primary', true)
            ?? $gallery->first();
        $reviews = FacilityWebsite::siteReviews($facility);

        return response()->view('themes._shared.kurum-sitesi.show', [
            'site' => $site,
            'facility' => $facility,
            'gallery' => $gallery,
            'hero' => $hero,
            'temsili' => $temsili,
            'reviews' => $reviews,
            'renk' => $site->themeColors(),
            'isPreview' => $isPreview,
            'formAction' => $formAction,
            'whatsapp' => classify_phone_type($facility->phone) === 'mobile' ? normalize_whatsapp_number($facility->phone) : '',
        ])->header('X-Robots-Tag', $isPreview ? 'noindex, nofollow' : 'index, follow');
    }
}
