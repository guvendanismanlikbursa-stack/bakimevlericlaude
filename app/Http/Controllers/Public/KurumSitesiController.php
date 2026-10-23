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
// "Profesyonel Site" paketinde çok sayfalı premium şablon kullanılır;
// önizlemede ?tasarim=pro ile paketi olmayan kurum da bu şablonu görebilir.
class KurumSitesiController extends Controller
{
    public function show(Request $request)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        $preview = $this->canPreview($site);

        if (! $site->isLive() && ! $preview) {
            abort(404);
        }
        $onizleme = $request->boolean('onizleme');
        if ($site->isLive() && $site->custom_domain && ! $onizleme) {
            $sayfa = (string) $request->route('sayfa');

            return redirect()->away($site->publicUrl().($sayfa !== '' ? '/'.$sayfa : ''), 301);
        }

        return $this->render($request, $site, ! $site->isLive(), $preview, '/siteler/'.$site->slug, (string) $request->route('sayfa'));
    }

    /**
     * Alan adından gelen istek (bkz. ServeFacilityWebsiteDomain).
     */
    public function showForDomain(Request $request, FacilityWebsite $site, string $sayfa = '')
    {
        return $this->render($request, $site, false, false, '', $sayfa);
    }

    public function contact(Request $request, OfferRequestNotificationService $notifier)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        abort_unless($site->isLive() || $this->canPreview($site), 404);

        $geri = route('kurum-sitesi.show', ['slug' => $site->slug]);
        if ($request->input('kaynak') === 'iletisim') {
            $geri .= '/iletisim';
        }

        return $this->handleContact($request, $site, $notifier, $geri);
    }

    public function contactForDomain(Request $request, FacilityWebsite $site, OfferRequestNotificationService $notifier)
    {
        return $this->handleContact($request, $site, $notifier, $request->input('kaynak') === 'iletisim' ? '/iletisim' : '/');
    }

    /**
     * WhatsApp / telefon tıklamasını sayar (sayfadaki betik gönderir).
     */
    public function event(Request $request)
    {
        $site = $this->findBySlug((string) $request->route('slug'));

        return $this->recordEvent($request, $site);
    }

    public function eventForDomain(Request $request, FacilityWebsite $site)
    {
        return $this->recordEvent($request, $site);
    }

    private function recordEvent(Request $request, FacilityWebsite $site)
    {
        $tur = (string) $request->input('tur');
        if ($site->isLive() && in_array($tur, ['whatsapp', 'arama'], true) && ! $this->isBot($request)) {
            $site->recordStat($tur);
        }

        return response()->noContent();
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
        if ($site->isLive()) {
            $site->recordStat('form');
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

    private function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless/i', (string) $request->userAgent());
    }

    private function render(Request $request, FacilityWebsite $site, bool $isPreview, bool $canPreview, string $base, string $sayfa)
    {
        $facility = $site->facility->load(['city', 'category', 'images', 'googleReviews', 'approvedReviews']);
        // Gerçek fotoğraf yoksa örnek görseller "temsili" notuyla gösterilir.
        [$gallery, $temsili] = FacilityWebsite::siteGallery($facility);
        $hero = $gallery->firstWhere('id', $site->hero_image_id)
            ?? $gallery->firstWhere('is_primary', true)
            ?? $gallery->first();
        $reviews = FacilityWebsite::siteReviews($facility);
        $whatsapp = classify_phone_type($facility->phone) === 'mobile' ? normalize_whatsapp_number($facility->phone) : '';

        // Profesyonel şablon: paket etkinse, ya da önizlemede ?tasarim=pro ile.
        $demoPro = $canPreview && $request->input('tasarim') === 'pro';
        $pro = $site->isPro() || $demoPro;
        if ($sayfa !== '' && ! $pro) {
            return redirect($base === '' ? '/' : $base.($isPreview ? '?onizleme=1' : ''));
        }

        $ortak = [
            'site' => $site,
            'facility' => $facility,
            'gallery' => $gallery,
            'hero' => $hero,
            'temsili' => $temsili,
            'reviews' => $reviews,
            'isPreview' => $isPreview,
            'whatsapp' => $whatsapp,
        ];

        // Kurumun kendi ve adminin bakışları sayılmaz.
        if (! $isPreview && ! $canPreview && ! $this->isBot($request)) {
            $site->recordStat('ziyaret');
        }

        if (! $pro) {
            return response()->view('themes._shared.kurum-sitesi.show', $ortak + [
                'renk' => $site->themeColors(),
                'formAction' => $base === '' ? '/iletisim' : route('kurum-sitesi.iletisim', ['slug' => $site->slug], false),
            ])->header('X-Robots-Tag', $isPreview ? 'noindex, nofollow' : 'index, follow');
        }

        $sayfalar = $this->proPages($site, $facility, $gallery, $reviews);
        if ($sayfa !== '' && ! isset($sayfalar[$sayfa])) {
            abort(404);
        }
        $sorgu = $demoPro ? '?onizleme=1&tasarim=pro' : (($isPreview || $request->boolean('onizleme')) ? '?onizleme=1' : '');

        return response()->view('themes._shared.kurum-sitesi.pro.sayfa', $ortak + [
            'renk' => $site->proColors(),
            'sayfa' => $sayfa,
            'sayfalar' => $sayfalar,
            'link' => fn (string $s = '') => ($base === '' ? '/'.$s : $base.($s !== '' ? '/'.$s : '')).$sorgu,
            'formAction' => ($base === '' ? '' : $base).'/iletisim',
            'olayAdresi' => ($base === '' ? '' : $base).'/olay',
            'demoPro' => $demoPro,
        ])->header('X-Robots-Tag', $isPreview ? 'noindex, nofollow' : 'index, follow');
    }

    /**
     * İçeriği olan iç sayfalar (menüde yalnızca bunlar çıkar).
     *
     * @return array<string, string>
     */
    private function proPages(FacilityWebsite $site, $facility, $gallery, $reviews): array
    {
        $var = [
            'hakkimizda' => $site->sectionEnabled('hakkimizda') && trim($site->aboutText()) !== '',
            'hizmetler' => $site->sectionEnabled('hizmetler') && collect($facility->services ?? [])->filter()->isNotEmpty(),
            'galeri' => $site->sectionEnabled('galeri') && $gallery->isNotEmpty(),
            'ekibimiz' => (bool) $site->listOf('team'),
            'duyurular' => (bool) $site->listOf('announcements'),
            'sss' => (bool) $site->listOf('faqs'),
            'iletisim' => true,
        ];

        return array_filter(FacilityWebsite::PRO_PAGES, fn ($k) => $var[$k] ?? false, ARRAY_FILTER_USE_KEY);
    }
}
