<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityMarketingNote;
use Illuminate\Http\Request;

// 27 Eylul 2026: kullanicinin talebi - pazarlamacinin telefonla arayip
// abonelige ikna etmeye calistigi kurumlarin listesi. Desen AdminFacility
// InvitationController ile BIREBIR AYNI (sekmeler + il/ilce/kategori
// filtresi) - kullanicinin acik istegi "kurum davetleri sayfa yapısı gibi
// olmalı". Liste SADECE telefonu olan, henuz sahiplenilmemis/anlasmali
// olmayan/abone olmayan kurumlari icerir (zaten musteri olani pazarlamaci
// bosuna aramasin).
class CrmController extends Controller
{
    use RedirectsOutOfRangePagination;

    // 28 Eylul 2026: kullanicinin talebi - "Düşünecek"/"Üye Oldu" durumlari
    // ve ayri bir "Randevulu Aramalar" sekmesi ('callback' => true, durum
    // listesi degil, marketing_callback_at dolu/gelecekteki kurumlari gosterir
    // - yetkili o an musait olmayip "su gun su saatte arayin" dediginde).
    private const GROUPS = [
        'all' => ['title' => 'Tümü', 'statuses' => null],
        'to_call' => ['title' => 'Aranacaklar', 'statuses' => [null, 'called']],
        'callback' => ['title' => 'Randevulu Aramalar', 'callback' => true],
        'reached_decision_maker' => ['title' => 'Yetkiliyle Görüştü', 'statuses' => ['reached_decision_maker']],
        'listened_details' => ['title' => 'Detay Dinledi', 'statuses' => ['listened_details']],
        'price_requested' => ['title' => 'Fiyat İstedi', 'statuses' => ['price_requested']],
        'thinking' => ['title' => 'Düşünecek', 'statuses' => ['thinking']],
        'interested' => ['title' => 'İlgileniyor', 'statuses' => ['interested']],
        'subscribed' => ['title' => 'Üye Oldu', 'statuses' => ['subscribed']],
        'receipt_sent' => ['title' => 'Dekont Geldi', 'statuses' => ['receipt_sent']],
        'not_interested' => ['title' => 'İlgilenmiyor', 'statuses' => ['not_interested']],
        'removal_requested' => ['title' => 'Kaldırılmasını İstiyor', 'statuses' => ['removal_requested']],
    ];

    // 29 Eylul 2026: kullanicinin talebi - satis hunisi ("100 arama -> 30
    // yetkiliyle gorusme -> 15 detay dinledi -> 7 fiyat istedi -> 3 odeme")
    // takip edebilmek icin 3 yeni ARA durum eklendi (mevcutlara EK, hicbiri
    // kaldirilmadi). Huni sirasi: called < reached_decision_maker <
    // listened_details < price_requested < subscribed (bkz. Admin\
    // MarketerController::funnel()).
    private const STATUSES = [
        'called' => 'Arandı',
        'reached_decision_maker' => 'Yetkiliyle Görüştü',
        'listened_details' => 'Detay Dinledi',
        'price_requested' => 'Fiyat İstedi',
        'thinking' => 'Düşünecek',
        'interested' => 'İlgileniyor',
        'subscribed' => 'Üye Oldu',
        'not_interested' => 'İlgilenmiyor',
        'receipt_sent' => 'Dekont Geldi',
        // 29 Eylul 2026: kullanicinin talebi - "her isletmenin platformdan
        // kaldirilma hakki var". Bu durum secilince asagida storeNote()
        // icinde ADMIN'E OTOMATIK VE ACIL bildirim gider - gercek silme
        // ISLEMI kasitli olarak OTOMATIK YAPILMAZ, admin facility-remove
        // ile inceleyip kendisi onaylar (yanlislikla/kotu niyetle tek
        // tikla gercek veri kaybi riskine karsi).
        'removal_requested' => 'Platformdan Kaldırılmasını İstiyor',
    ];

    private function baseQuery(Request $request)
    {
        $q = Facility::whereNull('deleted_at')
            ->where('is_claimed', false)
            ->where('is_broker_managed', false)
            ->where('is_subscribed', false)
            ->where('phone_type', '!=', 'none');

        if ($request->filled('city')) {
            $q->whereHas('city', fn ($c) => $c->where('slug', $request->city));
        }
        if ($request->filled('district')) {
            $q->where('district', $request->district);
        }
        if ($request->filled('category')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $request->category));
        }

        // 15 Ekim 2026: kullanicinin talebi - pazarlamaciya il/ilce/kategori
        // bazinda bolge atanmissa, CRM listesi sadece o kapsamla sinirlanir
        // (iki pazarlamaci ayni kurumu aramasin). Atama yoksa davranis
        // ESKISI GIBI sinirsizdir (bkz. Marketer::hasScopeRestriction()).
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        if ($marketer?->assigned_city_ids) {
            $q->whereIn('city_id', $marketer->assigned_city_ids);
        }
        if ($marketer?->assigned_districts) {
            $q->whereIn('district', $marketer->assigned_districts);
        }
        if ($marketer?->assigned_category_ids) {
            $q->whereIn('facility_category_id', $marketer->assigned_category_ids);
        }

        return $q;
    }

    public function index(Request $request)
    {
        $group = $request->query('group', 'to_call');
        if (! isset(self::GROUPS[$group])) {
            $group = 'to_call';
        }
        $def = self::GROUPS[$group];

        $query = $this->baseQuery($request)->with(['city', 'category', 'marketingNotes']);

        if ($def['callback'] ?? false) {
            $query->whereNotNull('marketing_callback_at');
        } elseif ($def['statuses'] !== null) {
            $statuses = $def['statuses'];
            $query->where(function ($q) use ($statuses) {
                if (in_array(null, $statuses, true)) {
                    $q->orWhereNull('marketing_status');
                }
                $named = array_filter($statuses, fn ($s) => $s !== null);
                if ($named) {
                    $q->orWhereIn('marketing_status', $named);
                }
            });
        }

        if ($def['callback'] ?? false) {
            // En yakin randevu en ustte - gecmis (unutulmus) randevular da
            // en altta gorunmeye devam eder ki pazarlamaci gozden kacirmasin.
            $query->orderBy('marketing_callback_at');
        } else {
            $query->orderByDesc('marketing_status_at')->orderByDesc('id');
        }

        $facilities = $query->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        $counts = $this->baseQuery($request)
            ->selectRaw('marketing_status, count(*) as total')
            ->groupBy('marketing_status')
            ->pluck('total', 'marketing_status');
        $callbackCount = $this->baseQuery($request)->whereNotNull('marketing_callback_at')->count();

        $groupCounts = [];
        foreach (self::GROUPS as $key => $groupDef) {
            if ($groupDef['callback'] ?? false) {
                $groupCounts[$key] = $callbackCount;
            } else {
                $groupCounts[$key] = $groupDef['statuses'] === null
                    ? $counts->sum()
                    : collect($groupDef['statuses'])->sum(fn ($s) => $counts[$s] ?? 0);
            }
        }

        // 15 Ekim 2026: kullanicinin talebi - pazarlamacinin kendi filtre
        // acilir menuleri de ATANMIS kapsamiyla sinirlanir, kapsaminin
        // disindaki il/kategoriyi secip bos liste gormesin.
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        $cities = City::orderBy('name')
            ->when($marketer?->assigned_city_ids, fn ($q) => $q->whereIn('id', $marketer->assigned_city_ids))
            ->get();
        $categories = FacilityCategory::orderBy('name')
            ->when($marketer?->assigned_category_ids, fn ($q) => $q->whereIn('id', $marketer->assigned_category_ids))
            ->get();
        $districtMap = $cities->mapWithKeys(function ($city) use ($marketer) {
            $all = districts_for_city($city->name);
            $scoped = $marketer?->assigned_districts ? array_values(array_intersect($all, $marketer->assigned_districts)) : $all;

            return [$city->slug => $scoped];
        });
        $groups = self::GROUPS;
        $statusLabels = self::STATUSES;

        $canViewOfferPage = (bool) $marketer?->can_view_offer_page;

        return view('marketer.crm.index', compact(
            'facilities', 'group', 'groups', 'groupCounts', 'cities', 'categories', 'districtMap', 'statusLabels', 'canViewOfferPage'
        ));
    }

    /**
     * 18 Ekim 2026: kullanıcının talebi - pazarlamacı kurumu telefonla
     * ararken kuruma özel İyilik Hareketi teklif sayfasını (fiyatlar +
     * kurumun gerçek son 30 gün rakamları) açabilsin. Yalnızca admin bu
     * pazarlamacıya izin verdiyse ve kurum pazarlamacının kendi CRM
     * kapsamındaysa. Süreli imzalı link kullanılır: program yayında olmasa
     * da açılır ve kurumun "teklifi açtı" sayacına eklenmez.
     */
    public function offerPage(Request $request, Facility $facility)
    {
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        abort_unless($marketer?->can_view_offer_page, 403, 'Teklif sayfasını görme yetkiniz yok.');
        abort_unless($this->baseQuery(new Request)->whereKey($facility->id)->exists(), 404);

        return redirect()->away(membership_signed_preview_url('membership.offer', ['token' => $facility->ensureOfferToken()]));
    }

    public function storeNote(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'status' => 'required|in:called,reached_decision_maker,listened_details,price_requested,thinking,interested,subscribed,not_interested,receipt_sent,removal_requested',
            'note' => 'nullable|string|max:2000',
            'brochure_requested' => 'nullable|in:0,1',
            'callback_at' => 'nullable|date',
        ]);

        FacilityMarketingNote::create([
            'facility_id' => $facility->id,
            'marketer_id' => session('marketer_id'),
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'brochure_requested' => $request->filled('brochure_requested') ? $request->boolean('brochure_requested') : null,
            'callback_at' => $data['callback_at'] ?? null,
        ]);

        $update = ['marketing_status' => $data['status'], 'marketing_status_at' => now()];
        if ($request->filled('brochure_requested')) {
            $update['marketing_brochure_requested'] = $request->boolean('brochure_requested');
        }
        // 28 Eylul 2026: kullanicinin talebi - bu notta yeni bir randevu
        // girildiyse kurumun "aktif randevusu" GUNCELLENIR; girilmediyse
        // ONCEKI randevu (varsa) OLDUGU GIBI kalir - her ne sebeple durum
        // guncellendiginde (ör. sadece "Arandı" diye isaretlemek icin)
        // mevcut randevu yanlislikla silinmez.
        if ($request->filled('callback_at')) {
            $update['marketing_callback_at'] = $data['callback_at'];
        }
        $facility->update($update);

        // 29 Eylul 2026: kullanicinin talebi - "kaldirilmasini istiyor"
        // isaretlenince, pazarlamacinin "Kaydi admine gonder" butonuna
        // ayrica basmasini BEKLEMEDEN, admin ANINDA haberdar edilir - bu
        // isteklerin gozden kacip günlerce isleme alinmamasi kabul edilemez.
        if ($data['status'] === 'removal_requested') {
            $marketer = \App\Models\Marketer::find(session('marketer_id'));
            $body = "{$marketer?->name} tarafından arandı: \"{$facility->name}\" ({$facility->phone}) yetkilisi kurumun platformdan kaldırılmasını istiyor.";
            if ($data['note'] ?? null) {
                $body .= "\nNot: {$data['note']}";
            }
            \App\Models\Admin::all()->each(fn ($admin) => notify_user(
                $admin, 'marketer_removal_requested', 'Kaldırılma talebi: '.$facility->name, $body,
                ['facility_id' => $facility->id],
            ));
        }

        return back()->with('success', 'Not kaydedildi.');
    }

    /**
     * 28 Eylul 2026: kullanicinin talebi - pazarlamaci gorustugu bir
     * kurumun kaydini admin'e (mail + admin paneli bildirimi, bkz.
     * notify_user()) aninda iletebilsin - ozellikle dekont geldiginde
     * admin'in beklemeden haberi olsun diye.
     */
    public function notifyAdmin(Facility $facility)
    {
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        $lastNote = $facility->marketingNotes()->first();
        $statusLabels = self::STATUSES;
        $statusText = $lastNote ? ($statusLabels[$lastNote->status] ?? $lastNote->status) : 'Not yok';

        $body = "{$marketer?->name} tarafından \"{$facility->name}\" ({$facility->phone}) ile görüşüldü.\nDurum: {$statusText}";
        if ($lastNote?->note) {
            $body .= "\nNot: {$lastNote->note}";
        }
        if ($facility->marketing_callback_at) {
            $body .= "\nAranacak zaman: ".$facility->marketing_callback_at->format('d.m.Y H:i');
        }

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin, 'marketer_facility_forwarded', 'Pazarlamacıdan kurum bildirimi: '.$facility->name, $body,
            ['facility_id' => $facility->id],
        ));

        return back()->with('success', 'Kurum kaydı admine gönderildi.');
    }
}
