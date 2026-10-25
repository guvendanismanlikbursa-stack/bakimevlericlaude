<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ExpertWebsite;
use Illuminate\Http\Request;

// 23 Ekim 2026: kullanıcının talebi - doktorun internet sitesi.
// Adres: bakimevleri.com/dr/{slug}; alan adı tanımlanıp site yayına
// alınmışsa o adrese kalıcı yönlendirilir (alan adından açılışı
// ServeFacilityWebsiteDomain ara katmanı yapar). Yayında olmayan site
// yalnızca admin tarafından önizlenebilir. Hasta yorumu gösterilmez.
class DoktorSitesiController extends Controller
{
    public function show(Request $request)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        $preview = (bool) session('admin_id');

        if (! $site->isLive() && ! $preview) {
            abort(404);
        }
        if ($site->isLive() && $site->custom_domain && ! $request->boolean('onizleme')) {
            return redirect()->away($site->publicUrl(), 301);
        }

        return $this->render($site, ! $site->isLive(), route('doktor-sitesi.iletisim', ['slug' => $site->slug], false), $preview ? (string) $request->query('tasarim') : '');
    }

    public function showForDomain(ExpertWebsite $site)
    {
        return $this->render($site, false, '/iletisim');
    }

    public function contact(Request $request)
    {
        $site = $this->findBySlug((string) $request->route('slug'));
        abort_unless($site->isLive() || session('admin_id'), 404);

        return $this->handleContact($request, $site, route('doktor-sitesi.show', ['slug' => $site->slug]));
    }

    public function contactForDomain(Request $request, ExpertWebsite $site)
    {
        return $this->handleContact($request, $site, '/');
    }

    /**
     * Doktorların paneli olmadığı için mesaj admin "İletişim Mesajları"na düşer.
     */
    private function handleContact(Request $request, ExpertWebsite $site, string $back)
    {
        $request->validate(['website' => 'max:0']);
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'message' => 'nullable|string|max:2000',
        ]);
        $expert = $site->expert;
        $message = ContactMessage::create([
            'brand' => 'bakimevleri',
            'name' => $data['full_name'],
            'email' => '',
            'subject' => 'Doktor sitesi: '.$site->fullName(),
            'message' => 'Telefon: '.$data['phone']."\n\n".trim((string) ($data['message'] ?? ''))
                ."\n\n(Doktorun internet sitesindeki iletişim formundan gönderildi. Doktor kaydı #".$expert->id.')',
        ]);
        if ($site->isLive()) {
            $site->recordStat('form');
        }
        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'contact_message_submitted',
            'Doktor sitesinden yeni mesaj',
            $message->name.' — '.$site->fullName().' sitesinden yazdı.',
        ));

        // 23 Ekim 2026: kullanıcının talebi - form, doktorun WhatsApp (cep)
        // hattına yönlendirir; mesaj hazır yazılı gelir. Kayıt yine tutulur.
        $wa = classify_phone_type($expert->phone) === 'mobile' ? normalize_whatsapp_number($expert->phone) : '';
        if ($wa) {
            $metin = 'Merhaba, internet sitenizden yazıyorum.'."\nAdım: ".$data['full_name']."\nTelefonum: ".$data['phone']
                .(trim((string) ($data['message'] ?? '')) !== '' ? "\n".trim($data['message']) : '');

            return redirect()->away('https://wa.me/'.$wa.'?text='.rawurlencode($metin));
        }

        return redirect($back.'#iletisim')->with('doktor_sitesi_basarili', 'Mesajınız iletildi, en kısa sürede sizi arayacağız.');
    }

    /** 25 Ekim 2026: WhatsApp / telefon tıklaması (sayfadaki betik gönderir). */
    public function event(Request $request)
    {
        return $this->recordEvent($request, $this->findBySlug((string) $request->route('slug')));
    }

    public function eventForDomain(Request $request, ExpertWebsite $site)
    {
        return $this->recordEvent($request, $site);
    }

    private function recordEvent(Request $request, ExpertWebsite $site)
    {
        $tur = (string) $request->input('tur');
        if ($site->isLive() && in_array($tur, ['whatsapp', 'arama'], true) && ! $this->isBot($request) && ! session('admin_id')) {
            $site->recordStat($tur);
        }

        return response()->noContent();
    }

    private function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless/i', (string) $request->userAgent());
    }

    private function findBySlug(string $slug): ExpertWebsite
    {
        $site = ExpertWebsite::where('slug', $slug)->first();
        abort_unless($site && $site->expert, 404);

        return $site;
    }

    private function render(ExpertWebsite $site, bool $isPreview, string $formAction, string $tasarim = '')
    {
        $expert = $site->expert->load('city');
        // Ziyaret sayacı: yayındaki site, admin ve botlar hariç.
        if ($site->isLive() && ! $isPreview && ! session('admin_id') && ! $this->isBot(request())) {
            $site->recordStat('ziyaret');
        }
        view()->share('olayAdresi', str_starts_with($formAction, '/dr/') ? '/dr/'.$site->slug.'/olay' : '/olay');
        // 25 Ekim 2026: Profesyonel Site paketi etkinse premium şablon; admin
        // önizlemede ?tasarim=pro / ?tasarim=standart ile ikisini de görebilir.
        $onizlemePro = in_array($tasarim, ['pro', 'klasik', 'hareketli'], true);
        $demoPro = $onizlemePro && ! $site->isPro();
        $pro = ($site->isPro() && $tasarim !== 'standart') || $onizlemePro;
        // 25 Ekim 2026: admin'in seçtiği Profesyonel tasarım (Klasik / Hareketli - doktorlarda sade sürüm).
        $hareketli = $pro && ($tasarim === 'hareketli' || ($site->pro_tasarim === 'hareketli' && $tasarim !== 'klasik'));
        if ($pro) {
            return response()->view('themes._shared.doktor-sitesi.pro', [
                'site' => $site,
                'expert' => $expert,
                'renk' => $site->proColors(),
                'isPreview' => $isPreview,
                'demoPro' => $demoPro,
                'hareketli' => $hareketli,
                'hareketliDemo' => $tasarim === 'hareketli',
                'formAction' => $formAction,
                'whatsapp' => classify_phone_type($expert->phone) === 'mobile' ? normalize_whatsapp_number($expert->phone) : '',
            ])->header('X-Robots-Tag', ($isPreview || $demoPro) ? 'noindex, nofollow' : 'index, follow');
        }

        return response()->view('themes._shared.doktor-sitesi.show', [
            'site' => $site,
            'expert' => $expert,
            'renk' => $site->themeColors(),
            'isPreview' => $isPreview,
            'formAction' => $formAction,
            'whatsapp' => classify_phone_type($expert->phone) === 'mobile' ? normalize_whatsapp_number($expert->phone) : '',
        ])->header('X-Robots-Tag', $isPreview ? 'noindex, nofollow' : 'index, follow');
    }
}
