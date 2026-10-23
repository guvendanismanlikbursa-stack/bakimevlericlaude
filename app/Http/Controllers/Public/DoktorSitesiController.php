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

        return $this->render($site, ! $site->isLive(), route('doktor-sitesi.iletisim', ['slug' => $site->slug], false));
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

    private function findBySlug(string $slug): ExpertWebsite
    {
        $site = ExpertWebsite::where('slug', $slug)->first();
        abort_unless($site && $site->expert, 404);

        return $site;
    }

    private function render(ExpertWebsite $site, bool $isPreview, string $formAction)
    {
        $expert = $site->expert->load('city');

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
