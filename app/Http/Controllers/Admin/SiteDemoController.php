<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertWebsite;
use App\Models\Facility;
use App\Models\FacilityWebsite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * 29 Ekim 2026: kullanıcının talebi - demo siteyi doktora / kuruma WhatsApp'tan
 * gönderme. Admin 3 tasarımdan (Standart, Profesyonel Klasik, Profesyonel
 * Hareketli) birini, birkaçını ya da hepsini seçer; seçilenlerin linkleri tek
 * mesajda gider. Cep numarası varsa WhatsApp sohbeti hazır mesajla açılır,
 * yoksa linkler kopyalanabilir sayfada gösterilir.
 */
class SiteDemoController extends Controller
{
    public function expert(Request $request, Expert $expert)
    {
        return $this->handle($request, ExpertWebsite::forExpert($expert), $expert, 'doktor');
    }

    public function facility(Request $request, Facility $facility)
    {
        return $this->handle($request, FacilityWebsite::forFacility($facility), $facility, 'kurum');
    }

    public function renewExpert(Expert $expert)
    {
        ExpertWebsite::forExpert($expert)->demoToken(true);
        log_admin_event('website_demo_renewed', $expert);

        return back()->with('success', 'Yeni demo linki oluşturuldu; eski link artık açılmaz.');
    }

    public function renewFacility(Facility $facility)
    {
        FacilityWebsite::forFacility($facility)->demoToken(true);
        log_admin_event('website_demo_renewed', $facility);

        return back()->with('success', 'Yeni demo linki oluşturuldu; eski link artık açılmaz.');
    }

    private function handle(Request $request, Model $site, Model $kayit, string $tur)
    {
        $tumu = array_keys(ExpertWebsite::DEMO_TASARIMLAR);
        $secili = array_values(array_intersect($tumu, (array) $request->input('tasarim', $tumu)));
        if ($secili === []) {
            $secili = $tumu;
        }
        $linkler = [];
        foreach ($secili as $t) {
            $linkler[$t] = $site->demoUrl($t);
        }
        $ad = $tur === 'doktor' ? $site->fullName() : $kayit->name;
        $mesaj = $this->message($ad, $linkler, $site->demo_until, $tur);
        $wa = classify_phone_type($kayit->phone) === 'mobile' ? normalize_whatsapp_number($kayit->phone) : '';
        $waLink = $wa ? 'https://wa.me/'.$wa.'?text='.rawurlencode($mesaj) : '';

        if ($request->input('islem') === 'whatsapp' && $waLink) {
            log_admin_event('website_demo_sent', $kayit, ['tasarim' => $secili]);

            return redirect()->away($waLink);
        }

        return view('admin.site-demo', [
            'tur' => $tur,
            'kayit' => $kayit,
            'site' => $site,
            'ad' => $ad,
            'linkler' => $linkler,
            'secili' => $secili,
            'mesaj' => $mesaj,
            'waLink' => $waLink,
            'telefon' => $kayit->phone,
            'yenileAdresi' => $tur === 'doktor' ? route('admin.expert-websites.demo.renew', $kayit) : route('admin.facility-websites.demo.renew', $kayit),
            'formAdresi' => $tur === 'doktor' ? route('admin.expert-websites.demo', $kayit) : route('admin.facility-websites.demo', $kayit),
        ]);
    }

    private function message(string $ad, array $linkler, $bitis, string $tur): string
    {
        $tek = count($linkler) === 1;
        $satirlar = ['Merhaba,', '', $ad.' için hazırladığımız internet sitesi '.($tek ? 'örneğini aşağıdaki bağlantıdan' : 'örneklerini aşağıdaki bağlantılardan').' inceleyebilirsiniz:', ''];
        $i = 1;
        foreach ($linkler as $t => $link) {
            $satirlar[] = ($tek ? '' : $i++.') ').ExpertWebsite::DEMO_TASARIMLAR[$t].':';
            $satirlar[] = $link;
            $satirlar[] = '';
        }
        $satirlar[] = ($tek ? 'Bağlantı ' : 'Bağlantılar ').$bitis->format('d.m.Y').' tarihine kadar açıktır. Beğendiğiniz tasarımı bu numaradan bize yazabilirsiniz.';
        $satirlar[] = 'bakimevleri.com';

        return implode("\n", $satirlar);
    }
}
