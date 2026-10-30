<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Marketer\CrmController as PazarlamaciCrm;
use App\Models\City;
use App\Models\Expert;
use App\Models\ExpertMarketingNote;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityMarketingNote;
use App\Models\Marketer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 30 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'inin daha ayrıntılı hali
 * admin panelinde; admin de kurum ve doktorları arayıp not düşebilsin.
 * Pazarlamacı CRM'ine (Marketer\CrmController / DoctorCrmController) DOKUNULMAZ;
 * aynı durum listesi ve aynı not tabloları kullanılır, böylece admin ile
 * pazarlamacı birbirinin görüşmesini görür ve aynı yeri iki kez aramaz.
 * Admin notu marketer_id boş, admin_id dolu yazılır.
 * Fazlası: kapsam sınırı yok (ön kayıtlı / sahipli / anlaşmalı / abone / hepsi),
 * isim-telefon araması, "kim aradı" ve son görüşme tarihi filtresi, sıralama,
 * tam görüşme geçmişi (kimin konuştuğuyla), WhatsApp, kayıt/site/demo bağlantıları
 * ve günlük özet.
 */
class CrmController extends Controller
{
    use RedirectsOutOfRangePagination;

    public const MUSTERI = [
        'on_kayitli' => 'Ön kayıtlı (pazarlamacı listesi)',
        'sahipli' => 'Sahiplenilmiş',
        'anlasmali' => 'Anlaşmalı',
        'abone' => 'Abone / üye',
        'hepsi' => 'Hepsi',
    ];

    public const SIRALAMA = [
        'son_gorusme' => 'Son görüşme (yeni → eski)',
        'randevu' => 'Randevu saati (yakın → uzak)',
        'ad' => 'Ada göre',
        'eski_gorusme' => 'En uzun süredir aranmayan',
    ];

    // ---------------------------------------------------------------- Görüşme rehberi

    public const REHBER_ANAHTAR = 'crm_doktor_rehber';

    public const KURUM_REHBER_ANAHTAR = 'crm_kurum_rehber';

    /**
     * 30 Ekim 2026: kullanıcının talebi - doktor ve kurum görüşme rehberleri admin
     * tarafından düzenlenir; kayıt yoksa ilk (varsayılan) metin gösterilir.
     */
    public static function rehberMetni(string $tur = 'doktor'): string
    {
        $kayitli = trim((string) \App\Models\Setting::get($tur === 'kurum' ? self::KURUM_REHBER_ANAHTAR : self::REHBER_ANAHTAR, ''));

        return $kayitli !== '' ? $kayitli : ($tur === 'kurum' ? self::varsayilanKurumRehberi() : self::varsayilanRehber());
    }

    /** Kurum rehberinin ilk hali (kullanıcının metni, 30 Ekim 2026 düzeltmeleriyle). */
    public static function varsayilanKurumRehberi(): string
    {
        return trim((string) @file_get_contents(resource_path('crm/kurum_rehber.txt')));
    }

    public static function varsayilanRehber(): string
    {
        return <<<'REHBER'
## 1 · Giriş
"Merhaba Hocam, ben Güven, bakimevleri.com'dan arıyorum. Bursa'da hasta bakım hizmeti veren bir firmayız, ofisimiz Şehir Hastanesi'nin batı girişinde. Müsaitseniz 2 dakikanızı rica edeceğim; uygun değilse sizi ne zaman arayayım?"
! "Hastane adına" ya da "hastanemiz" demeyin.

## 2 · Rehberdeki yeri (ücretsiz)
"bakimevleri.com, bakimevibul.com ve bakimeviara.com'da aileler için bir hekim rehberi var. Profiliniz burada ücretsiz yer alıyor: adresiniz, branşınız, telefonunuz. Sıralama alfabetik, yorum ve puan yok; TTB kılavuzunu esas alarak hazırladık. Bilgileriniz doğru mu, bir kontrol etmek istedik."

## 3 · Soru
"Muayenehaneniz bu adreste mi? Şu an kendinize ait bir internet siteniz var mı?"
! Cevabı not formundaki "Web sitesi var mı?" kutusuna işaretleyin.

## 4 · Teklif (sitesi yoksa / sadece rehber veya Instagram varsa)
"Hekimler için kendi alan adınızla, TTB kurallarına uygun, sade internet siteleri de hazırlıyoruz. Alan adı, barındırma ve güvenlik sertifikası dahil. Sizin bilgilerinizle üç farklı tasarımda örneğini hazırladık bile. WhatsApp'tan göndermeme izin verir misiniz? Siteyi almasanız da rehberdeki yeriniz ücretsiz olarak devam eder."
! İzin alınmadan link gönderilmez. İzin verirse kartın altındaki "Demo site gönder".

## İyilik Hareketi (yalnızca gerçekten yapılıyorsa)
"Bu işten elde ettiğimiz gelirin bir kısmı, platformumuzdaki İyilik Hareketi kapsamında ihtiyaç sahibi yaşlı ve çocukların bakımına gidiyor."

## 5 · Kapanış
"Örnekleri bugün gönderiyorum. Perşembe günü sizi arayıp beğendiğiniz tasarımı sorsam uygun olur mu, hangi saat iyi?"
! İlk görüşmede sipariş istenmez. Verdiği gün/saati "Ne zaman tekrar aranacak" alanına yazın.

## İtirazlara cevap
- **"Doktortakvimi'nde varım":** "Orada sizin adınıza bir sayfa var, ama alan adı sizin değil. Kendi adınızla bir adres, Google'da adınız arandığında en üstte sizin sitenizin çıkmasını sağlar."
- **"Hastanede çalışıyorum":** "Hastanenin sitesinde sayfanız varsa ona ihtiyaç duymayabilirsiniz; muayenehaneniz yoksa sizi oyalamayayım." Kibarca kapatın.
- **"Sitem var":** "Çok güzel. O zaman sadece rehberdeki bilgilerinizi kontrol edelim." Sitesini CRM'e işleyin.
- **"Pahalı":** "Temel paketimiz yıllık {temel_fiyat}, yani ayda yaklaşık {aylik_fiyat}. Alan adı ve barındırma dahil."
- **"TTB'den sorun olur mu?":** "Sitede yorum, puan ve reklam yok; kılavuzun istediği zorunlu cümle sayfanın en üstünde duruyor."
- **"Listeden çıkarın":** "Elbette, hemen işleme alıyorum." Durumu "Platformdan Kaldırılmasını İstiyor" seçin.
- **Sekreter:** "Hocaya ileteceğiniz kısa bir bilgi; hangi gün ve saatte aramam uygun olur?"

## Söylenmeyecekler
! "Seçkin hekimler", "bugün 5 hekim sipariş verdi" gibi doğrulanamayan ifadeler, 7/24 destek sözü, paket sayfasında olmayan bir özellik.
REHBER;
    }

    public function saveGuide(Request $request)
    {
        $anahtar = $request->input('tur') === 'kurum' ? self::KURUM_REHBER_ANAHTAR : self::REHBER_ANAHTAR;
        if ($request->boolean('varsayilan')) {
            \App\Models\Setting::set($anahtar, '');
            log_admin_event('crm_rehber_sifirlandi');

            return back()->with('success', 'Görüşme rehberi ilk haline döndürüldü.')->with('rehber_kaydedildi', true);
        }
        $data = $request->validate(['rehber' => 'required|string|max:20000'], ['rehber.required' => 'Rehber boş olamaz.']);
        \App\Models\Setting::set($anahtar, str_replace("\r\n", "\n", $data['rehber']));
        log_admin_event('crm_rehber_guncellendi');

        return back()->with('success', 'Görüşme rehberi kaydedildi.')->with('rehber_kaydedildi', true);
    }

    // ---------------------------------------------------------------- Kurumlar

    private function facilityQuery(Request $r)
    {
        $q = Facility::query()->whereNull('deleted_at');
        match ($r->query('musteri', 'on_kayitli')) {
            'sahipli' => $q->where('is_claimed', true),
            'anlasmali' => $q->where('is_broker_managed', true),
            'abone' => $q->where('is_subscribed', true),
            'hepsi' => null,
            default => $q->where('is_claimed', false)->where('is_broker_managed', false)->where('is_subscribed', false),
        };
        if ($r->query('telefon') !== 'hepsi') {
            $q->where('phone_type', '!=', 'none');
        }
        if ($r->filled('city')) {
            $q->whereHas('city', fn ($c) => $c->where('slug', $r->city));
        }
        if ($r->filled('district')) {
            $q->where('district', $r->district);
        }
        if ($r->filled('category')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $r->category));
        }
        if ($r->filled('site')) {
            $q->websiteFilter($r->string('site')->toString());
        }
        $this->ortakFiltreler($q, $r);

        return $q;
    }

    public function index(Request $request)
    {
        [$group, $def] = $this->grup($request);
        $query = $this->facilityQuery($request)->with(['city', 'category', 'marketingNotes.marketer', 'marketingNotes.admin', 'website']);
        $this->grupUygula($query, $def);
        $this->sirala($query, $request, $def);
        $facilities = $query->paginate(25)->withQueryString();
        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        return view('admin.crm.index', [
            'tur' => 'kurum',
            'kayitlar' => $facilities,
            'group' => $group,
            'groups' => PazarlamaciCrm::GROUPS,
            'groupCounts' => $this->grupSayilari(fn () => $this->facilityQuery($request)),
            'cities' => City::orderBy('name')->get(),
            'categories' => FacilityCategory::orderBy('name')->get(),
            'branches' => collect(),
            'districtMap' => $this->ilceHaritasi(),
            'statusLabels' => PazarlamaciCrm::STATUSES,
            'marketers' => Marketer::orderBy('name')->get(['id', 'name']),
            'ozet' => $this->ozet(FacilityMarketingNote::class, Facility::query()->whereNull('deleted_at')),
        ]);
    }

    public function storeNote(Request $request, Facility $facility)
    {
        $data = $this->notDogrula($request, false);
        FacilityMarketingNote::create($this->notAlanlari($request, $data) + ['facility_id' => $facility->id]);
        $update = $this->kayitGuncelleme($request, $data);
        if ($data['website_status'] ?? null) {
            $update['website_source'] = 'admin';
        }
        $facility->update($update);
        log_admin_event('admin_crm_note', $facility, ['status' => $data['status']]);

        return redirect()->to(url()->previous().'#k'.$facility->id)->with('success', 'Not kaydedildi: '.$facility->name);
    }

    /** Kuruma özel İyilik Hareketi teklif sayfası (pazarlamacıdaki gibi süreli imzalı link). */
    public function offerPage(Facility $facility)
    {
        return redirect()->away(membership_signed_preview_url('membership.offer', ['token' => $facility->ensureOfferToken()]));
    }

    // ---------------------------------------------------------------- Doktorlar

    private function expertQuery(Request $r)
    {
        $q = Expert::query();
        if ($r->query('yayin') !== 'hepsi') {
            $q->where('is_published', true);
        }
        if ($r->query('telefon') !== 'hepsi') {
            $q->whereNotNull('phone')->where('phone', '!=', '');
        }
        if ($r->filled('city')) {
            $q->whereHas('city', fn ($c) => $c->where('slug', $r->city));
        }
        if ($r->filled('district')) {
            $q->where('district', $r->district);
        }
        if ($r->filled('branch')) {
            $q->where('branch', $r->branch);
        }
        if ($r->filled('site')) {
            $q->websiteFilter($r->string('site')->toString());
        }
        $this->ortakFiltreler($q, $r);

        return $q;
    }

    public function doctors(Request $request)
    {
        [$group, $def] = $this->grup($request);
        $query = $this->expertQuery($request)->with(['city', 'marketingNotes.marketer', 'marketingNotes.admin']);
        $this->grupUygula($query, $def);
        $this->sirala($query, $request, $def);
        $experts = $query->paginate(25)->withQueryString();
        if ($redirect = $this->redirectIfPageOutOfRange($request, $experts)) {
            return $redirect;
        }
        $siteler = \App\Models\ExpertWebsite::whereIn('expert_id', $experts->pluck('id'))->get()->keyBy('expert_id');

        return view('admin.crm.index', [
            'tur' => 'doktor',
            'kayitlar' => $experts,
            'doktorSiteleri' => $siteler,
            'group' => $group,
            'groups' => PazarlamaciCrm::GROUPS,
            'groupCounts' => $this->grupSayilari(fn () => $this->expertQuery($request)),
            'cities' => City::orderBy('name')->get(),
            'categories' => collect(),
            'branches' => Expert::query()->whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch'),
            'districtMap' => $this->ilceHaritasi(),
            'statusLabels' => PazarlamaciCrm::STATUSES,
            'marketers' => Marketer::orderBy('name')->get(['id', 'name']),
            'ozet' => $this->ozet(ExpertMarketingNote::class, Expert::query()),
        ]);
    }

    public function storeDoctorNote(Request $request, Expert $expert)
    {
        $data = $this->notDogrula($request, true);
        ExpertMarketingNote::create($this->notAlanlari($request, $data) + [
            'expert_id' => $expert->id,
            'package_interest' => $data['package_interest'] ?? null,
        ]);
        $expert->update($this->kayitGuncelleme($request, $data));
        log_admin_event('admin_crm_note', $expert, ['status' => $data['status']]);

        return redirect()->to(url()->previous().'#k'.$expert->id)->with('success', 'Not kaydedildi: '.$expert->name);
    }

    // ---------------------------------------------------------------- Ortak

    /** Arama, "kim aradı" ve son görüşme filtresi (kurum ve doktorda aynı). */
    private function ortakFiltreler($q, Request $r): void
    {
        if ($r->filled('ara')) {
            $ara = trim((string) $r->ara);
            $rakam = preg_replace('/\D+/', '', $ara);
            $q->where(function ($w) use ($ara, $rakam) {
                $w->whereTrLike('name', $ara);
                if (strlen($rakam) >= 4) {
                    $w->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE ?", ['%'.ltrim($rakam, '0').'%']);
                }
            });
        }
        $kim = (string) $r->query('kim', '');
        if ($kim === 'hic') {
            $q->whereDoesntHave('marketingNotes');
        } elseif ($kim === 'ben') {
            $q->whereHas('marketingNotes', fn ($n) => $n->where('admin_id', session('admin_id')));
        } elseif ($kim === 'admin') {
            $q->whereHas('marketingNotes', fn ($n) => $n->whereNotNull('admin_id'));
        } elseif (ctype_digit($kim)) {
            $q->whereHas('marketingNotes', fn ($n) => $n->where('marketer_id', (int) $kim));
        }
        if ($r->filled('son_gun') && ctype_digit((string) $r->son_gun)) {
            $q->where('marketing_status_at', '>=', now()->subDays((int) $r->son_gun));
        }
        if ($r->filled('aranmayan_gun') && ctype_digit((string) $r->aranmayan_gun)) {
            $q->where(fn ($w) => $w->whereNull('marketing_status_at')->orWhere('marketing_status_at', '<', now()->subDays((int) $r->aranmayan_gun)));
        }
    }

    private function grup(Request $r): array
    {
        $group = (string) $r->query('group', 'to_call');
        if (! isset(PazarlamaciCrm::GROUPS[$group])) {
            $group = 'to_call';
        }

        return [$group, PazarlamaciCrm::GROUPS[$group]];
    }

    private function grupUygula($query, array $def): void
    {
        if ($def['callback'] ?? false) {
            $query->whereNotNull('marketing_callback_at');
        } elseif ($def['statuses'] !== null) {
            $statuses = $def['statuses'];
            $query->where(function ($q) use ($statuses) {
                if (in_array(null, $statuses, true)) {
                    $q->orWhereNull('marketing_status');
                }
                $named = array_values(array_filter($statuses, fn ($s) => $s !== null));
                if ($named) {
                    $q->orWhereIn('marketing_status', $named);
                }
            });
        }
    }

    private function sirala($query, Request $r, array $def): void
    {
        $sira = (string) $r->query('sira', ($def['callback'] ?? false) ? 'randevu' : 'son_gorusme');
        match ($sira) {
            'randevu' => $query->orderByRaw('marketing_callback_at IS NULL')->orderBy('marketing_callback_at'),
            'ad' => $query->orderBy('name'),
            'eski_gorusme' => $query->orderByRaw('marketing_status_at IS NOT NULL')->orderBy('marketing_status_at'),
            default => $query->orderByDesc('marketing_status_at')->orderByDesc('id'),
        };
    }

    private function grupSayilari(\Closure $taban): array
    {
        $counts = $taban()->selectRaw('marketing_status, count(*) as total')->groupBy('marketing_status')->pluck('total', 'marketing_status');
        $callback = $taban()->whereNotNull('marketing_callback_at')->count();
        $sonuc = [];
        foreach (PazarlamaciCrm::GROUPS as $key => $g) {
            $sonuc[$key] = ($g['callback'] ?? false) ? $callback
                : ($g['statuses'] === null ? $counts->sum() : collect($g['statuses'])->sum(fn ($s) => $counts[$s] ?? 0));
        }

        return $sonuc;
    }

    private function ilceHaritasi()
    {
        return City::orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->slug => districts_for_city($c->name)]);
    }

    /** Sayfa üstündeki günlük özet. */
    private function ozet(string $notModeli, $kayitSorgusu): array
    {
        $bugun = now()->startOfDay();

        return [
            'bugun_randevu' => (clone $kayitSorgusu)->whereBetween('marketing_callback_at', [$bugun, $bugun->copy()->endOfDay()])->count(),
            'geciken_randevu' => (clone $kayitSorgusu)->where('marketing_callback_at', '<', now())->whereNotIn('marketing_status', ['subscribed', 'receipt_sent', 'not_interested', 'removal_requested'])->count(),
            'bugun_gorusme' => $notModeli::where('created_at', '>=', $bugun)->count(),
            'bugun_benim' => $notModeli::where('created_at', '>=', $bugun)->where('admin_id', session('admin_id'))->count(),
            'hafta_gorusme' => $notModeli::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    private function notDogrula(Request $request, bool $doktor): array
    {
        return $request->validate([
            'status' => ['required', Rule::in(array_keys(PazarlamaciCrm::STATUSES))],
            'note' => 'nullable|string|max:2000',
            'brochure_requested' => 'nullable|in:0,1',
            'callback_at' => 'nullable|date',
            'contact_name' => 'nullable|string|max:120',
            'contact_role' => 'nullable|in:calisan,mudur,sahibi',
            'website_status' => 'nullable|in:var,yok,sosyal',
            'site_offer_interest' => 'nullable|in:ilgilendi,ilgilenmedi',
        ] + ($doktor ? ['package_interest' => ['nullable', Rule::in(array_keys(ExpertMarketingNote::PACKAGES))]] : []));
    }

    private function notAlanlari(Request $request, array $data): array
    {
        return [
            'marketer_id' => null,
            'admin_id' => session('admin_id'),
            'status' => $data['status'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_role' => $data['contact_role'] ?? null,
            'website_status' => $data['website_status'] ?? null,
            'site_offer_interest' => $data['site_offer_interest'] ?? null,
            'note' => $data['note'] ?? null,
            'brochure_requested' => $request->filled('brochure_requested') ? $request->boolean('brochure_requested') : null,
            'callback_at' => $data['callback_at'] ?? null,
        ];
    }

    private function kayitGuncelleme(Request $request, array $data): array
    {
        $update = ['marketing_status' => $data['status'], 'marketing_status_at' => now()];
        if ($request->filled('brochure_requested')) {
            $update['marketing_brochure_requested'] = $request->boolean('brochure_requested');
        }
        if ($request->filled('callback_at')) {
            $update['marketing_callback_at'] = $data['callback_at'];
        }
        // Randevuyu kapat kutusu: görüşme yapıldıysa eski randevu listeden düşer.
        if ($request->boolean('randevu_kapat') && ! $request->filled('callback_at')) {
            $update['marketing_callback_at'] = null;
        }
        if ($data['website_status'] ?? null) {
            $update['website_status'] = $data['website_status'];
            $update['website_checked_at'] = now();
        }

        return $update;
    }
}
