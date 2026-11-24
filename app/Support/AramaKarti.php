<?php

namespace App\Support;

use App\Models\Facility;
use App\Models\FacilityInterestLead;
use App\Models\FacilityMarketingNote;
use App\Models\OfferRequest;
use App\Models\SearchQuery;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 24 Kasım 2026: kullanıcının talebi - "Arama kartı". Kurumu telefonla aramadan önce, İLK ARAMADA satışı kapatmak için
 * "mümkün olan maksimum bilgi" tek sayfada: kurumun durumu, sayfasına gelen gerçek ilgi, bölgedeki aile talebi, rakipler,
 * yorumlardan güçlü/zayıf yanlar, eksikler, kuruma uygun teklif, itiraz cevapları ve görüşmede gönderilecek bağlantılar.
 *
 * Her açılışta canlı veriyle hesaplanır. Aynı gün kullanıcının ikinci kararı: "buradan yazan her şeyi ekleyelim" -
 * görüşme notu/takip, kurum sahibi hakkında ELLE girilen bilgiler (ilgi alanı, kişisel notlar; otomatik toplama YOK,
 * admin kendisi araştırıp yazar), kuruma ulaşmak isteyen ailelerin adı/telefonu ve sağlık kuruluşlarına da üyelik
 * paketleri (uyarıyla) kartta gösterilir. Sayfa yalnız admin içindir.
 */
class AramaKarti
{
    /** Yorumlardaki konular: anahtar kelime kökleri (küçük harf, Türkçe). */
    public const KONULAR = [
        'temizlik' => ['Temizlik ve hijyen', ['temiz', 'hijyen', 'kirli', 'pis', 'koku', 'bakımlı']],
        'personel' => ['Personelin ilgisi ve güler yüzü', ['personel', 'çalışan', 'ilgili', 'ilgisiz', 'güler yüz', 'güleryüz', 'şefkat', 'sevecen', 'saygılı', 'kaba', 'bakıcı', 'hemşire', 'ablalar', 'abiler']],
        'ogretmen' => ['Öğretmenler ve eğitim', ['öğretmen', 'eğitim', 'müfredat', 'ingilizce', 'etkinlik', 'gelişim']],
        'yemek' => ['Yemek ve beslenme', ['yemek', 'mutfak', 'beslenme', 'kahvaltı', 'menü', 'aşçı']],
        'saglik' => ['Sağlık takibi (doktor, ilaç)', ['doktor', 'hekim', 'ilaç', 'sağlık', 'tedavi', 'fizyoterap', 'hemşire']],
        'iletisim' => ['Aileye bilgi verme / iletişim', ['bilgilendir', 'iletişim', 'haber ver', 'telefon', 'ulaşamad', 'açmıyor', 'fotoğraf at', 'video at']],
        'ortam' => ['Bina, oda ve bahçe', ['bahçe', 'oda', 'bina', 'ferah', 'geniş', 'manzara', 'konfor', 'havalandır', 'eski', 'dar']],
        'aktivite' => ['Etkinlik ve sosyal yaşam', ['aktivite', 'etkinlik', 'oyun', 'gezi', 'sosyal', 'kutlama', 'doğum günü']],
        'fiyat' => ['Fiyat ve ücret', ['fiyat', 'ücret', 'pahalı', 'uygun fiyat', 'ucuz', 'para']],
        'guven' => ['Güven ve huzur', ['güven', 'huzur', 'içim rahat', 'gönül rahatlığı', 'aile gibi', 'evimiz gibi', 'ev gibi']],
        'yonetim' => ['Yönetim ve kurum sahibi', ['müdür', 'yönetim', 'sahibi', 'kurucu', 'hanım', 'bey']],
    ];

    /** Kurum sahibi / yetkili hakkında elle girilen alanlar (facilities.sahip_profili). */
    public const SAHIP_ALANLARI = [
        'ad' => 'Adı soyadı',
        'gorev' => 'Görevi (sahibi, müdür, ortak…)',
        'meslek' => 'Mesleği / geçmişi',
        'ilgi' => 'İlgi alanları, hobileri',
        'onem' => 'Önem verdiği konular (iş)',
        'kisisel' => 'Kişisel notlar (aile, memleket, takım…)',
        'iletisim' => 'Nasıl konuşmayı sever (kısa/detaylı, resmi/samimi)',
        'sosyal' => 'Sosyal medya / web bağlantıları',
        'not' => 'Diğer notlar',
    ];

    public static function hazirla(Facility $f): array
    {
        $f->loadMissing(['city', 'category', 'images', 'website', 'facilityUsers', 'claims', 'subscriptionPackage']);
        $saglik = $f->isHealthProvider();

        return [
            'kurum' => self::kurum($f),
            'saat' => self::aramaSaati($f),
            'ilgi' => self::ilgi($f),
            'rakip' => $rakip = self::rakipler($f),
            'bolge' => self::bolge($f, $rakip['ilce']),
            'yorum' => self::yorumlar($f),
            'eksik' => self::eksikler($f),
            'gecmis' => self::gecmis($f),
            'teklif' => self::teklif($f),
            'saglik' => $saglik,
            'sahip' => self::sahip($f),
            'aileler' => self::aileler($f),
            'notlar' => self::notlar($f),
        ];
    }

    /** Kayıtlı sahip bilgileri + elle araştırma için hazır arama bağlantıları. */
    private static function sahip(Facility $f): array
    {
        $profil = is_array($f->sahip_profili) ? $f->sahip_profili : [];
        $adlar = collect([$profil['ad'] ?? null])
            ->merge($f->facilityUsers->pluck('name'))
            ->merge($f->claims->pluck('applicant_name'))
            ->merge(FacilityMarketingNote::where('facility_id', $f->id)->whereNotNull('contact_name')->pluck('contact_name'))
            ->filter()->map(fn ($x) => trim($x))->unique()->take(3)->values();
        $yer = $f->city->name ?? '';
        $g = fn ($q) => 'https://www.google.com/search?q='.rawurlencode($q);
        $baglantilar = [];
        foreach ($adlar as $ad) {
            $baglantilar[] = ['ad' => $ad.' · Google', 'url' => $g('"'.$ad.'" '.$f->name)];
            $baglantilar[] = ['ad' => $ad.' · LinkedIn', 'url' => 'https://www.linkedin.com/search/results/all/?keywords='.rawurlencode($ad.' '.$yer)];
            $baglantilar[] = ['ad' => $ad.' · Facebook', 'url' => 'https://www.facebook.com/search/top?q='.rawurlencode($ad.' '.$yer)];
            $baglantilar[] = ['ad' => $ad.' · Instagram', 'url' => $g('site:instagram.com "'.$ad.'"')];
            $baglantilar[] = ['ad' => $ad.' · Haberler', 'url' => 'https://www.google.com/search?tbm=nws&q='.rawurlencode('"'.$ad.'"')];
        }
        $baglantilar[] = ['ad' => 'Kurum · Google', 'url' => $g('"'.$f->name.'" '.$yer)];
        $baglantilar[] = ['ad' => 'Kurum · Instagram', 'url' => $g('site:instagram.com "'.$f->name.'"')];
        $baglantilar[] = ['ad' => 'Kurum · Facebook', 'url' => 'https://www.facebook.com/search/top?q='.rawurlencode($f->name)];
        $baglantilar[] = ['ad' => 'Kurum · Haberler', 'url' => 'https://www.google.com/search?tbm=nws&q='.rawurlencode('"'.$f->name.'"')];
        $baglantilar[] = ['ad' => 'Kurum sahibi kim? · Google', 'url' => $g('"'.$f->name.'" kurucu OR sahibi OR müdür')];

        return ['profil' => $profil, 'adlar' => $adlar->all(), 'baglantilar' => $baglantilar];
    }

    /** Kuruma ulaşmak isteyen / teklif isteyen aileler (son 180 gün, en yeni 20). */
    private static function aileler(Facility $f): array
    {
        $kisa = fn ($m, $n) => mb_strimwidth(preg_replace('/\s+/', ' ', (string) $m), 0, $n, '…');
        $liste = collect();
        foreach (FacilityInterestLead::where('facility_id', $f->id)->where('created_at', '>=', now()->subDays(180))->latest()->limit(20)->get() as $x) {
            $liste->push(['an' => $x->created_at, 'tur' => 'Ulaşmak istedi', 'ad' => $x->full_name, 'telefon' => $x->phone, 'eposta' => $x->email,
                'mesaj' => $x->message ? $kisa($x->message, 180) : null, 'iletildi' => $x->sent_to_facility_at?->format('d.m.Y')]);
        }
        foreach ($f->offerRequests()->where('created_at', '>=', now()->subDays(180))->latest()->limit(20)->get() as $x) {
            $ek = array_filter([$x->care_for, $x->patient_age ? $x->patient_age.' yaş' : null, $x->budget_max ? 'bütçe ≤ '.number_format((float) $x->budget_max, 0, ',', '.').' ₺' : null, $x->message ? $kisa($x->message, 150) : null]);
            $liste->push(['an' => $x->created_at, 'tur' => 'Fiyat teklifi istedi', 'ad' => $x->full_name, 'telefon' => $x->phone, 'eposta' => $x->email,
                'mesaj' => $ek ? implode(' · ', $ek) : null, 'iletildi' => $x->sent_to_facility_at?->format('d.m.Y')]);
        }

        return $liste->sortByDesc('an')->take(20)->map(fn ($x) => ['tarih' => $x['an']?->format('d.m.Y H:i')] + $x)->values()->all();
    }

    /** CRM görüşme geçmişi (en yeni 15). */
    private static function notlar(Facility $f): array
    {
        return FacilityMarketingNote::where('facility_id', $f->id)->with(['marketer', 'admin'])->latest()->limit(15)->get()
            ->map(fn ($n) => [
                'tarih' => $n->created_at?->format('d.m.Y H:i'),
                'durum' => \App\Http\Controllers\Marketer\CrmController::STATUSES[$n->status] ?? $n->status,
                'kim' => $n->marketer?->name ?: ($n->admin?->name ?? 'Admin'),
                'kisi' => $n->contactLabel(),
                'not' => $n->sozlesme ? "Site sözleşmesi WhatsApp'tan gönderildi." : $n->note,
                'tekrar' => $n->callback_at?->format('d.m.Y H:i'),
            ])->all();
    }

    private static function kurum(Facility $f): array
    {
        $yetkililer = $f->facilityUsers->map(fn ($u) => trim($u->name.($u->role ? ' ('.$u->role.')' : '')))->filter()->unique()->values()->all();
        $basvuran = $f->claims->sortByDesc('created_at')->pluck('applicant_name')->filter()->unique()->values()->all();
        $sonTemas = FacilityMarketingNote::where('facility_id', $f->id)->whereNotNull('contact_name')->latest()->first();

        return [
            'ad' => $f->name,
            'tur' => $f->category->name ?? '—',
            'yer' => trim(($f->district ? $f->district.', ' : '').($f->city->name ?? ''), ', '),
            'adres' => $f->address,
            'telefon' => $f->phone,
            'telefon_turu' => $f->phone ? classify_phone_type($f->phone) : null,
            'whatsapp' => $f->phone && classify_phone_type($f->phone) === 'mobile' ? normalize_whatsapp_number($f->phone) : '',
            'eposta' => $f->email,
            'site' => $f->website_url,
            'site_durumu' => $f->website_status ? (Facility::WEBSITE_STATUSES[$f->website_status] ?? $f->website_status) : null,
            'bizim_site' => $f->website ? ($f->website->isLive() ? 'Yayında'.($f->website->isPro() ? ' (Profesyonel)' : '') : 'Taslak') : null,
            'durum' => $f->is_broker_managed ? 'Anlaşmalı' : ($f->is_claimed ? 'Sahiplenilmiş (panel hesabı var)' : 'Ön kayıtlı (sahiplenilmemiş)'),
            'uye' => $f->isMember(),
            'uyelik_bitis' => $f->subscription_paid_until?->format('d.m.Y'),
            'bakiye' => (float) $f->balance,
            'kapasite' => $f->capacity,
            'fiyat' => $f->price_min || $f->price_max ? trim(($f->price_min ? number_format((float) $f->price_min, 0, ',', '.').' ₺' : '').($f->price_max && $f->price_max != $f->price_min ? ' – '.number_format((float) $f->price_max, 0, ',', '.').' ₺' : '')) : null,
            'bos_yer' => $f->vacancy_general ?? (($f->vacancy_male || $f->vacancy_female) ? trim(($f->vacancy_male ? 'Erkek '.$f->vacancy_male.' ' : '').($f->vacancy_female ? 'Kadın '.$f->vacancy_female : '')) : null),
            'saatler' => $f->openingHoursSummary(7),
            'puan' => $f->rating ? (float) $f->rating : null,
            'yorum_sayisi' => $f->totalReviewCount(),
            'sistemde' => $f->created_at?->format('d.m.Y'),
            'yetkililer' => $yetkililer,
            'basvuranlar' => $basvuran,
            'son_temas' => $sonTemas ? trim($sonTemas->contact_name.($sonTemas->contact_role ? ' – '.$sonTemas->contact_role : '')) : null,
            'sayfa' => url('/kurumlar/'.$f->slug),
            'yayinda' => (bool) $f->is_published,
        ];
    }

    /** Çalışma saatlerine göre en uygun arama zamanı (kurumda yoğun saatler dışı). */
    private static function aramaSaati(Facility $f): array
    {
        $gun = Facility::GUNLER[now()->dayOfWeekIso - 1];
        $bugun = ($f->opening_hours ?? [])[$gun] ?? null;
        $acik247 = $bugun && str_contains(mb_strtolower($bugun), '24');
        $kapali = $bugun && str_contains(mb_strtolower($bugun), 'kapalı');
        $oneri = $f->isHealthProvider()
            ? '10:00–12:00 veya 14:00–16:30 (seans yoğunluğu dışında)'
            : (str_contains((string) ($f->category->brand_scope ?? ''), 'cocuk') || str_contains(mb_strtolower($f->category->name ?? ''), 'kreş') || str_contains(mb_strtolower($f->category->name ?? ''), 'anaokul')
                ? '09:30–11:30 veya 13:30–15:30 (sabah teslim, öğle yemeği/uyku ve akşam teslim saatleri dışında)'
                : '10:00–11:30 veya 14:00–16:30 (yemek, ilaç ve ziyaret saatleri dışında)');

        return ['bugun' => $gun, 'bugun_saat' => $bugun, 'kapali' => $kapali, 'acik247' => $acik247, 'oneri' => $oneri];
    }

    private static function ilgi(Facility $f): array
    {
        $ilgi30 = $f->gercekIlgi(30);
        $ilgi90 = $f->gercekIlgi(90);
        $ulasamayan = FacilityInterestLead::where('facility_id', $f->id)->where('created_at', '>=', now()->subDays(90))->count();
        $sonTalep = FacilityInterestLead::where('facility_id', $f->id)->max('created_at');
        $teklifIstegi = $f->offerRequests()->count();

        return [
            'ay' => $ilgi30,
            'uc_ay' => $ilgi90,
            'gosterilen_ay' => $f->monthlyDisplayViewsCount(),
            'toplam_goruntulenme' => (int) $f->views_count,
            'favori' => (int) $f->favorites_count,
            'teklif_istegi' => $teklifIstegi,
            'teklif_istegi_90' => $f->offerRequests()->where('created_at', '>=', now()->subDays(90))->count(),
            'verilen_teklif' => $f->quotes()->count(),
            'ulasamayan_90' => $ulasamayan,
            'son_ulasmak_isteyen' => $sonTalep ? \Illuminate\Support\Carbon::parse($sonTalep)->format('d.m.Y') : null,
            'foto_bakilma' => (int) $f->images->sum('views_count'),
            'olcum_basi' => \Illuminate\Support\Carbon::parse(Facility::GERCEK_ILGI_BASLANGIC)->format('d.m.Y'),
        ];
    }

    /** İl / ilçe genelinde aynı türde kurum arayan aile talebi. */
    private static function bolge(Facility $f, string $ilce = ''): array
    {
        $ilce = $ilce !== '' ? $ilce : (string) $f->district;
        $taban = fn () => OfferRequest::query()->where('city_id', $f->city_id)->where('facility_category_id', $f->facility_category_id);
        $ilceSor = function () use ($f, $taban, $ilce) {
            $q = $taban();
            if ($f->district_id) {
                $q->where('district_id', $f->district_id);
            } else {
                $q->whereHas('facility', fn ($x) => $x->where('district', $ilce));
            }

            return $q;
        };
        $ilceKurumlari = fn () => Facility::query()->where('city_id', $f->city_id)->where('district', $ilce)->where('facility_category_id', $f->facility_category_id);
        $ilceUlasma = FacilityInterestLead::query()->whereIn('facility_id', $ilceKurumlari()->select('id'))->where('created_at', '>=', now()->subDays(30))->count();
        $arama = SearchQuery::query()->where('city_id', $f->city_id)->where('facility_category_id', $f->facility_category_id)
            ->where('search_date', '>=', now()->subDays(30)->toDateString())->sum('count');

        return [
            'il_30' => $taban()->where('created_at', '>=', now()->subDays(30))->count(),
            'il_90' => $taban()->where('created_at', '>=', now()->subDays(90))->count(),
            'ilce_30' => $ilce !== '' ? $ilceSor()->where('created_at', '>=', now()->subDays(30))->count() : null,
            'ilce_90' => $ilce !== '' ? $ilceSor()->where('created_at', '>=', now()->subDays(90))->count() : null,
            'ilce_ulasma_30' => $ilce !== '' ? $ilceUlasma : null,
            'ilce' => $ilce,
            'il_arama_30' => (int) $arama,
            'butce' => self::butce($taban()->where('created_at', '>=', now()->subDays(180))),
        ];
    }

    private static function butce(Builder $q): ?string
    {
        $r = $q->where(fn ($x) => $x->whereNotNull('budget_min')->orWhereNotNull('budget_max'))
            ->selectRaw('avg(budget_min) as a, avg(budget_max) as b, count(*) as n')->first();
        if (! $r || ! $r->n) {
            return null;
        }
        $b = fn ($v) => $v ? number_format((float) $v, 0, ',', '.').' ₺' : null;

        return trim(($b($r->a) ?? '').($r->b ? ' – '.$b($r->b) : '')).' (son 6 ayda bütçe yazan '.$r->n.' aile)';
    }

    /**
     * Rakipler 4 katmanda (24 Kasım 2026: kullanıcının talebi - "il yerine ilçe, büyük ilçelerde mahalle; il verisini silmeden ekle"):
     * mahalle, 2 km çevre (kesin konum varsa), ilçe, il. Tek sorgu (il geneli) çekilir, dar katmanlar PHP'de süzülür.
     * Kurumun ilçesi boşsa en yakın kurumların ilçesinden tahmin edilir.
     */
    private static function rakipler(Facility $f): array
    {
        $hepsi = Facility::query()->where('is_published', true)->where('name', 'not like', 'QATEST%')
            ->where('city_id', $f->city_id)->where('facility_category_id', $f->facility_category_id)
            ->withCount(['images', 'googleReviews', 'approvedReviews'])
            ->get(['id', 'name', 'slug', 'rating', 'price_min', 'views_count', 'website_status', 'website_url', 'is_claimed', 'is_broker_managed', 'is_subscribed', 'subscription_paid_until', 'district', 'neighborhood', 'lat', 'lng']);
        if (! $hepsi->contains('id', $f->id)) {
            $ben = Facility::query()->whereKey($f->id)->withCount(['images', 'googleReviews', 'approvedReviews'])
                ->first(['id', 'name', 'slug', 'rating', 'price_min', 'views_count', 'website_status', 'website_url', 'is_claimed', 'is_broker_managed', 'is_subscribed', 'subscription_paid_until', 'district', 'neighborhood', 'lat', 'lng']);
            $hepsi->push($ben);
        }
        $bugun = now()->startOfDay();
        $hepsi->each(function ($x) use ($bugun) {
            $x->yorum_toplam = $x->google_reviews_count + $x->approved_reviews_count;
            $x->uye_mi = $x->is_claimed || $x->is_broker_managed || ($x->is_subscribed && $x->subscription_paid_until && $x->subscription_paid_until->gte($bugun));
        });
        $norm = fn ($s) => trim(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], (string) $s)));
        $mesafe = function ($x) use ($f) {
            if (! $f->lat || ! $f->lng || ! $x->lat || ! $x->lng) {
                return null;
            }
            $r = 6371;
            $dLat = deg2rad($x->lat - $f->lat);
            $dLng = deg2rad($x->lng - $f->lng);
            $h = sin($dLat / 2) ** 2 + cos(deg2rad($f->lat)) * cos(deg2rad($x->lat)) * sin($dLng / 2) ** 2;

            return 2 * $r * asin(min(1, sqrt($h)));
        };
        // İlçe: kayıtlı değilse en yakın 7 kurumun en sık ilçesi
        $ilce = trim((string) $f->district);
        $ilceTahmin = false;
        if ($ilce === '' && $f->hasPreciseLocation()) {
            $yakin = $hepsi->filter(fn ($x) => $x->id !== $f->id && trim((string) $x->district) !== '' && $mesafe($x) !== null)
                ->sortBy(fn ($x) => $mesafe($x))->take(7);
            $ilce = (string) ($yakin->countBy(fn ($x) => trim($x->district))->sortDesc()->keys()->first() ?? '');
            $ilceTahmin = $ilce !== '';
        }
        $seviyeler = [];
        $mahalle = trim((string) $f->neighborhood);
        if ($mahalle !== '') {
            $m = $hepsi->filter(fn ($x) => $norm($x->neighborhood) === $norm($mahalle)
                && (($ilce !== '' && $norm($x->district) === $norm($ilce)) || (($d = $mesafe($x)) !== null && $d <= 5) || $x->id === $f->id));
            $seviyeler[] = self::seviye($m, $f, 'mahalle', $mahalle);
        }
        if ($f->hasPreciseLocation()) {
            $seviyeler[] = self::seviye($hepsi->filter(fn ($x) => $x->id === $f->id || (($d = $mesafe($x)) !== null && $d <= 2)), $f, 'çevre', '2 km çevre');
        }
        if ($ilce !== '') {
            $seviyeler[] = self::seviye($hepsi->filter(fn ($x) => $x->id === $f->id || $norm($x->district) === $norm($ilce)), $f, 'ilçe', $ilce.($ilceTahmin ? ' (konuma göre)' : ''));
        }
        $seviyeler[] = self::seviye($hepsi, $f, 'il', $f->city->name ?? 'İl');
        // Ana karşılaştırma: en az 4 kurum olan en dar katman; "bizimle çalışan" cümlesi: üyesi olan en dar katman
        $ana = collect($seviyeler)->first(fn ($s) => $s['toplam'] >= 4) ?? end($seviyeler);
        $uyeli = collect($seviyeler)->first(fn ($s) => $s['uye_sayisi'] > 0);

        return $ana + ['seviyeler' => $seviyeler, 'uye_kapsam' => $uyeli, 'ilce' => $ilce, 'ilce_tahmin' => $ilceTahmin];
    }

    private static function seviye($grup, Facility $f, string $kapsam, string $etiket): array
    {
        $grup = $grup->values();
        $digerleri = $grup->where('id', '!=', $f->id);
        $ort = fn ($c) => $c->count() ? round($c->avg(), 1) : null;
        $siraBul = function ($alan) use ($grup, $f) {
            $i = $grup->sortByDesc($alan)->values()->search(fn ($x) => $x->id === $f->id);

            return $i === false ? null : $i + 1;
        };
        $siteVar = $digerleri->filter(fn ($x) => $x->website_url || ($x->website_status && ! in_array($x->website_status, Facility::WEBSITE_MISSING_STATUSES, true)))->count();
        $uyeler = $digerleri->where('uye_mi', true);

        return [
            'kapsam' => $kapsam,
            'etiket' => $etiket,
            'toplam' => $grup->count(),
            'uye_sayisi' => $uyeler->count(),
            'uyeler' => $uyeler->sortByDesc('rating')->take(6)
                ->map(fn ($x) => ['ad' => $x->name, 'puan' => $x->rating, 'foto' => $x->images_count, 'yorum' => $x->yorum_toplam, 'ilce' => $x->district])->values()->all(),
            'ort_puan' => $ort($digerleri->pluck('rating')->filter()),
            'ort_yorum' => $ort($digerleri->pluck('yorum_toplam')),
            'ort_foto' => $ort($digerleri->pluck('images_count')),
            'ort_fiyat' => $digerleri->pluck('price_min')->filter()->count() >= 2 ? (int) round($digerleri->pluck('price_min')->filter()->avg()) : null,
            'site_orani' => $digerleri->count() ? (int) round($siteVar / $digerleri->count() * 100) : null,
            'sira_puan' => $siraBul('rating'),
            'sira_yorum' => $siraBul('yorum_toplam'),
            'sira_ilgi' => $siraBul('views_count'),
            'en_iyi' => $digerleri->sortByDesc('yorum_toplam')->take(3)->map(fn ($x) => ['ad' => $x->name, 'puan' => $x->rating, 'yorum' => $x->yorum_toplam, 'uye' => (bool) $x->uye_mi])->values()->all(),
        ];
    }

    /** Yorumlardan güçlü / zayıf yanlar (anahtar kelimeyle; övgü = 4-5 yıldız, şikâyet = 1-2 yıldız). */
    private static function yorumlar(Facility $f): array
    {
        $liste = collect();
        foreach ($f->googleReviews()->get(['rating', 'body', 'relative_time_text']) as $r) {
            $liste->push(['puan' => (int) $r->rating, 'metin' => (string) $r->body, 'zaman' => $r->relative_time_text]);
        }
        foreach ($f->approvedReviews()->get(['rating', 'body', 'created_at', 'facility_reply']) as $r) {
            $liste->push(['puan' => (int) $r->rating, 'metin' => (string) $r->body, 'zaman' => $r->created_at?->format('d.m.Y'), 'yanit' => filled($r->facility_reply)]);
        }
        $ovgu = [];
        $sikayet = [];
        foreach ($liste as $y) {
            $m = mb_strtolower($y['metin']);
            if ($m === '') {
                continue;
            }
            foreach (self::KONULAR as $k => [$ad, $kokler]) {
                foreach ($kokler as $kok) {
                    if (str_contains($m, $kok)) {
                        if ($y['puan'] >= 4) {
                            $ovgu[$k] = ($ovgu[$k] ?? 0) + 1;
                        } elseif ($y['puan'] > 0 && $y['puan'] <= 2) {
                            $sikayet[$k] = ($sikayet[$k] ?? 0) + 1;
                        }
                        break;
                    }
                }
            }
        }
        arsort($ovgu);
        arsort($sikayet);
        $ad = fn ($k) => self::KONULAR[$k][0];
        $alinti = function ($kosul) use ($liste) {
            $y = $liste->filter($kosul)->filter(fn ($x) => mb_strlen($x['metin']) >= 40)->sortByDesc(fn ($x) => mb_strlen($x['metin']))->first();

            return $y ? ['metin' => mb_strimwidth(preg_replace('/\s+/', ' ', $y['metin']), 0, 220, '…'), 'puan' => $y['puan'], 'zaman' => $y['zaman']] : null;
        };
        $dagilim = [];
        foreach ([5, 4, 3, 2, 1] as $p) {
            $dagilim[$p] = $liste->where('puan', $p)->count();
        }

        return [
            'sayi' => $liste->count(),
            'dagilim' => $dagilim,
            'ovgu' => array_map(fn ($k, $n) => ['ad' => $ad($k), 'n' => $n], array_keys($ovgu), $ovgu),
            'sikayet' => array_map(fn ($k, $n) => ['ad' => $ad($k), 'n' => $n], array_keys($sikayet), $sikayet),
            'iyi_alinti' => $alinti(fn ($x) => $x['puan'] >= 5),
            'kotu_alinti' => $alinti(fn ($x) => $x['puan'] > 0 && $x['puan'] <= 2),
            'yanitsiz_kotu' => $liste->filter(fn ($x) => $x['puan'] > 0 && $x['puan'] <= 2 && array_key_exists('yanit', $x) && ! $x['yanit'])->count(),
            'sira_etiketi' => $f->reviewRankLabel(),
        ];
    }

    private static function eksikler(Facility $f): array
    {
        $kalite = $f->profileQuality();
        $foto = $f->realImageCount();
        $liste = [];
        if ($foto < 5) {
            $liste[] = 'Sadece '.$foto.' fotoğraf var (aileler en çok fotoğrafa bakıyor).';
        }
        if (! $f->video_path) {
            $liste[] = 'Tanıtım videosu yok.';
        }
        if (mb_strlen((string) $f->description) < 120) {
            $liste[] = 'Kurum açıklaması yok ya da çok kısa.';
        }
        if (! $f->price_min && ! $f->price_max) {
            $liste[] = 'Fiyat bilgisi girilmemiş (aileler fiyat görmeden geçebiliyor).';
        }
        if (! $f->opening_hours) {
            $liste[] = 'Çalışma saatleri yok.';
        }
        if (! $f->website_url && (! $f->website_status || in_array($f->website_status, Facility::WEBSITE_MISSING_STATUSES, true))) {
            $liste[] = 'Kendi internet sitesi yok'.($f->website_status === 'sosyal' ? ' (sadece sosyal medya var)' : '').'.';
        }
        if (! $f->is_claimed && ! $f->is_broker_managed) {
            $liste[] = 'Sayfası sahiplenilmemiş: ailelerin "Ara / WhatsApp" isteği kuruma ulaşmıyor.';
        }
        if (! $f->phone || classify_phone_type($f->phone) !== 'mobile') {
            $liste[] = 'Sayfada WhatsApp ile yazılabilecek cep numarası yok.';
        }

        return ['puan' => $kalite['score'], 'liste' => $liste, 'foto' => $foto];
    }

    private static function gecmis(Facility $f): array
    {
        $notlar = FacilityMarketingNote::where('facility_id', $f->id);

        return [
            'durum' => $f->marketing_status,
            'durum_tarihi' => $f->marketing_status_at?->format('d.m.Y'),
            'gorusme' => (clone $notlar)->where('sozlesme', false)->count(),
            'sozlesme' => (clone $notlar)->where('sozlesme', true)->max('created_at'),
            'teklif_acildi' => $f->offer_link_open_count ? $f->offer_link_open_count.' kez (son: '.$f->offer_link_opened_at?->format('d.m.Y H:i').')' : null,
            'panel_girisi' => $f->site_visited_at?->format('d.m.Y'),
            'davet' => $f->invitation_status,
            'kayit_basvurusu' => $f->claims->count(),
        ];
    }

    private static function teklif(Facility $f): array
    {
        $paketler = SubscriptionPackage::active()->membership()->orderBy('sort_order')->orderBy('price')->get()
            ->filter(fn ($p) => $p->isAvailableForFacility($f))->values()
            ->map(fn ($p) => [
                'ad' => trim(($p->badge_emoji ? $p->badge_emoji.' ' : '').$p->name),
                'fiyat' => (float) $p->price,
                'liste' => $p->hasLaunchDiscount() ? (float) $p->list_price : null,
                'ay' => (int) ($p->months ?: max(1, round(($p->duration_days ?: 365) / 30))),
                'aylik' => (float) $p->price / max(1, (int) ($p->months ?: max(1, round(($p->duration_days ?: 365) / 30)))),
                // Özellik satırları 'Başlık: açıklama' biçiminde uzun; kartta yalnız başlık
                'ozellik' => array_map(fn ($l) => mb_strimwidth(trim(explode(':', $l, 2)[0]), 0, 70, '…'), array_slice($p->featureList(), 0, 8)),
                'site' => $p->includesWebsite(),
                'bedava' => $p->free_months_label,
            ])->all();

        return [
            'paketler' => $paketler,
            'site_paketleri' => SiteSozlesmesi::paketler(false),
            'teklif_linki' => route('admin.crm.offer-page', $f),
            'demo_linki' => route('admin.facility-websites.demo', $f),
        ];
    }
}
