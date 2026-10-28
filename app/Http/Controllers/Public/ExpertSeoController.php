<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Expert;
use App\Models\ExpertWebsite;
use App\Support\DoktorIcerik;
use App\Support\DoktorSeo;
use Illuminate\Http\Request;

/**
 * 25 Ekim 2026: kullanıcının talebi - Doktorlar bölümünde "Nilüfer çocuk doktoru" gibi
 * aramalarda görünürlük. İki tür sayfa:
 *  - /uzmanlar/{il}[/{ilçe|branş}[/{branş}]] : il / ilçe / branş açılış sayfaları
 *  - /doktor/{slug}                          : doktorun kendi profil sayfası
 * Liste kısmı mevcut Doktorlar sayfasının aynısıdır (ExpertController::index); bu sınıf
 * yalnızca filtreyi, başlık/açıklama/SSS/bağlantı gibi arama motoru bilgilerini hazırlar.
 */
class ExpertSeoController extends Controller
{
    public function liste(Request $request)
    {
        // Parametreler adla okunur: yerel /site/{brand} ön ekinde sıra kayar (bkz. FacilityController::show).
        $il = (string) $request->route('il');
        $a = $request->route('a');
        $b = $request->route('b');
        $city = City::where('slug', $il)->firstOrFail();
        $dagilim = DoktorSeo::dagilim()->where('il_id', $city->id);
        $bransSlug = null;
        $ilceSlug = null;

        if ($a !== null) {
            if ($b === null && isset(DoktorSeo::BRANSLAR[$a])) {
                $bransSlug = $a;
            } else {
                abort_unless($dagilim->contains('ilce_slug', $a), 404);
                $ilceSlug = $a;
                if ($b !== null) {
                    abort_unless(isset(DoktorSeo::BRANSLAR[$b]), 404);
                    $bransSlug = $b;
                }
            }
        }

        $kapsam = $dagilim
            ->when($ilceSlug, fn ($c) => $c->where('ilce_slug', $ilceSlug))
            ->when($bransSlug, fn ($c) => $c->where('brans_slug', $bransSlug));
        $adet = (int) $kapsam->sum('adet');
        abort_if($adet === 0, 404);

        $ilceAdi = $ilceSlug ? $kapsam->countBy('ilce')->sortDesc()->keys()->first() : null;
        $ilceYazimlari = $ilceSlug ? $kapsam->pluck('ilce')->unique()->values()->all() : [];
        $brans = $bransSlug ? DoktorSeo::BRANSLAR[$bransSlug] : null;

        $request->merge(array_filter([
            'city' => $city->slug,
            'district' => $ilceAdi,
            'branch' => $bransSlug,
        ]));

        $yer = $ilceAdi ? $ilceAdi.' ('.$city->name.')' : $city->name;
        $neYazi = $brans ? $brans['cogul'] : 'Özel Doktorlar';
        $neKucuk = mb_strtolower($brans ? $brans['cogul'] : 'özel doktorlar', 'UTF-8');
        $yol = '/uzmanlar/'.$city->slug.($ilceSlug ? '/'.$ilceSlug : '').($bransSlug ? '/'.$bransSlug : '');
        $sayfa = max(1, (int) $request->query('page', 1));

        $h1 = ($ilceAdi ?: $city->name).' '.$neYazi.($brans ? ' ('.$brans['title'].')' : '');
        $title = $h1.($ilceAdi ? ' – '.$city->name : '').' | '.$adet.' Doktor, Adres ve Telefon'.($sayfa > 1 ? ' – Sayfa '.$sayfa : '');
        $aciklama = $yer.' bölgesindeki '.$adet.' '.$neKucuk.' için klinik, adres ve telefon bilgileri. Liste herkese açık kaynaklardan derlenir ve alfabetiktir.';

        $giris = $yer.' bölgesinde muayenehanesi bulunan '.$adet.' '.$neKucuk.' aşağıda alfabetik sırayla listelenmiştir. '
            .'Her doktorun kartında klinik, adres ve telefon bilgisi yer alır; randevu doğrudan doktorun muayenehanesinden alınır.';
        if (! $ilceSlug) {
            $enCok = $kapsam->whereNotNull('ilce')->groupBy('ilce')->map->sum('adet')->sortDesc()->take(4);
            if ($enCok->count() > 1) {
                $giris .= ' '.$city->name.' içinde en çok kaydın bulunduğu ilçeler: '.$enCok->map(fn ($n, $ad) => $ad.' ('.$n.')')->implode(', ').'.';
            }
        }

        $sss = [
            ['soru' => $yer.' bölgesinde kaç '.$neKucuk.' listeleniyor?', 'cevap' => 'Sitemizde şu an '.$yer.' için '.$adet.' kayıt bulunuyor. Liste düzenli olarak güncellenir.'],
            ['soru' => 'Randevu nasıl alabilirim?', 'cevap' => 'Listede doktorun kartını açıp telefon numarasına dokunarak doğrudan arayabilirsiniz. Randevu, doktorun muayenehanesiyle yapılır.'],
        ];
        // 28 Ekim 2026: "hangi durumlarda başvurulur" sorusu kaldırıldı (TTB md. 14 - uzmanlık dışı anahtar kelime).
        $sss[] = ['soru' => 'Bu liste nasıl hazırlanıyor?', 'cevap' => 'Bilgiler herkese açık güncel kaynaklardan derlenir. Sıralama alfabetiktir; hiçbir doktor öne çıkarılmaz ve liste reklam amacı taşımaz.'];
        $sss[] = ['soru' => 'Siteniz randevu ya da yönlendirme hizmeti veriyor mu?', 'cevap' => 'Hayır. Sitemiz hekimlerle hastalar arasında aracılık yapmaz; randevu, ücret ya da yönlendirme hizmeti sunmaz ve hekimler sitemize üye değildir. İletişim doğrudan muayenehaneyle kurulur.'];

        // Bağlantılar: aynı ildeki ilçeler, aynı ilçedeki branşlar, il geneli
        $link = fn (array $p) => brand_route('experts.landing', $p);
        $ilceLinkleri = $dagilim->whereNotNull('ilce_slug')->when($bransSlug, fn ($c) => $c->where('brans_slug', $bransSlug))
            ->groupBy('ilce_slug')->map(fn ($g) => ['ad' => $g->countBy('ilce')->sortDesc()->keys()->first(), 'adet' => $g->sum('adet'), 'slug' => $g->first()['ilce_slug']])
            ->reject(fn ($x) => $x['slug'] === $ilceSlug)->sortByDesc('adet')->take(24)
            ->map(fn ($x) => ['ad' => $x['ad'].($brans ? ' '.$brans['cogul'] : ' doktorları'), 'adet' => $x['adet'], 'url' => $link(array_filter(['il' => $city->slug, 'a' => $x['slug'], 'b' => $bransSlug]))])->values();
        $bransLinkleri = $dagilim->when($ilceSlug, fn ($c) => $c->where('ilce_slug', $ilceSlug))->whereNotNull('brans_slug')
            ->groupBy('brans_slug')->map(fn ($g, $s) => ['slug' => $s, 'adet' => $g->sum('adet')])
            ->reject(fn ($x) => $x['slug'] === $bransSlug)->sortByDesc('adet')
            ->map(fn ($x) => ['ad' => ($ilceAdi ?: $city->name).' '.DoktorSeo::BRANSLAR[$x['slug']]['cogul'], 'adet' => $x['adet'], 'url' => $ilceSlug ? $link(['il' => $city->slug, 'a' => $ilceSlug, 'b' => $x['slug']]) : $link(['il' => $city->slug, 'a' => $x['slug']])])->values();

        $kirinti = [['ad' => 'Özel Doktorlar', 'url' => brand_route('experts.index'), 'asil' => DoktorSeo::asilAdres('/uzmanlar')],
            ['ad' => $city->name, 'url' => $link(['il' => $city->slug]), 'asil' => DoktorSeo::asilAdres('/uzmanlar/'.$city->slug)]];
        if ($ilceSlug) {
            $kirinti[] = ['ad' => $ilceAdi, 'url' => $link(['il' => $city->slug, 'a' => $ilceSlug]), 'asil' => DoktorSeo::asilAdres('/uzmanlar/'.$city->slug.'/'.$ilceSlug)];
        }
        if ($brans) {
            $kirinti[] = ['ad' => $brans['cogul'], 'url' => $request->url(), 'asil' => DoktorSeo::asilAdres($yol)];
        }

        $seo = [
            'h1' => $h1, 'title' => $title, 'aciklama' => $aciklama, 'giris' => $giris, 'sss' => $sss,
            'kanonik' => DoktorSeo::asilAdres($yol).($sayfa > 1 ? '?page='.$sayfa : ''),
            'kirinti' => $kirinti, 'ilceLinkleri' => $ilceLinkleri, 'bransLinkleri' => $bransLinkleri,
            'ilceYazimlari' => $ilceYazimlari, 'tumIl' => $ilceSlug ? ['ad' => $city->name.' '.$neYazi, 'url' => $link(array_filter(['il' => $city->slug, 'a' => $bransSlug]))] : null,
        ];

        return app(ExpertController::class)->sayfa($request, $seo);
    }

    public function doktor(Request $request)
    {
        $slug = (string) $request->route('slug');
        $expert = Expert::where('slug', $slug)->where('is_published', true)->with('city')->firstOrFail();
        $bransSlug = DoktorSeo::bransSlug($expert->branch, $expert->branch_label);
        $brans = $bransSlug ? DoktorSeo::BRANSLAR[$bransSlug] : null;
        $ilceSlug = $expert->district ? DoktorSeo::ilceSlug($expert->district) : null;
        $city = $expert->city;

        $ayniYer = Expert::query()->where('is_published', true)->where('id', '!=', $expert->id)->where('city_id', $expert->city_id)
            ->when($expert->district, fn ($q) => $q->where('district', $expert->district))
            ->when($bransSlug, fn ($q) => DoktorSeo::bransFiltresi($q, $bransSlug))
            ->orderBy('name')->limit(8)->get(['id', 'name', 'title', 'slug', 'district', 'branch', 'branch_label']);

        $site = ExpertWebsite::where('expert_id', $expert->id)->first();
        $siteAdresi = $site && $site->isLive() ? $site->publicUrl() : null;

        $link = fn (array $p) => brand_route('experts.landing', $p);
        $kirinti = [['ad' => 'Özel Doktorlar', 'url' => brand_route('experts.index'), 'asil' => DoktorSeo::asilAdres('/uzmanlar')]];
        if ($city) {
            $kirinti[] = ['ad' => $city->name, 'url' => $link(['il' => $city->slug]), 'asil' => DoktorSeo::asilAdres('/uzmanlar/'.$city->slug)];
            if ($ilceSlug) {
                $kirinti[] = ['ad' => $expert->district, 'url' => $link(['il' => $city->slug, 'a' => $ilceSlug]), 'asil' => DoktorSeo::asilAdres('/uzmanlar/'.$city->slug.'/'.$ilceSlug)];
                if ($brans) {
                    $kirinti[] = ['ad' => $brans['cogul'], 'url' => $link(['il' => $city->slug, 'a' => $ilceSlug, 'b' => $bransSlug]), 'asil' => DoktorSeo::asilAdres('/uzmanlar/'.$city->slug.'/'.$ilceSlug.'/'.$bransSlug)];
                }
            }
        }
        $kirinti[] = ['ad' => trim($expert->title.' '.$expert->name), 'url' => $request->url(), 'asil' => DoktorSeo::asilAdres('/doktor/'.$expert->slug)];

        \App\Models\Expert::girisKaydet($expert, $request);
        // Kart sayacı ile aynı: profil açılması (bot ve admin hariç).
        if (! session('admin_id') && ! preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless/i', (string) $request->userAgent())) {
            Expert::recordCardStat([$expert->id], 'acilma');
        }

        return view('themes._shared.experts.show', [
            'expert' => $expert, 'brans' => $brans, 'bransSlug' => $bransSlug, 'ilceSlug' => $ilceSlug, 'city' => $city,
            'ayniYer' => $ayniYer, 'siteAdresi' => $siteAdresi, 'kirinti' => $kirinti,
            'kanonik' => DoktorSeo::asilAdres('/doktor/'.$expert->slug),
            'publiclyVisible' => doctors_live(),
        ]);
    }
}
