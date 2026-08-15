<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;

/**
 * On kayitli (henuz sahiplenilmemis) ozel/vakif kurumlara WhatsApp davet
 * gonderme akisini yonetir. Gonderim insan tarafindan WhatsApp uzerinden
 * yapilir (wa.me linki hazir mesajla acilir); bu ekran sadece durumu takip
 * eder ki hicbir kurum "kaybolmasin".
 */
class FacilityInvitationController extends Controller
{
    use RedirectsOutOfRangePagination;

    private const GROUPS = [
        // 28 Temmuz 2026: kullanici bir il+kategori sectiginde "bu ildeki
        // TUM kurumlari gormek" bekliyordu ('Kurumlar' sayfasindaki gibi),
        // ama varsayilan sekme sadece 'not_started' (Gonderilecekler)
        // durumundakileri gosteriyordu - digerleri (sabit hatli, kamu
        // oldugu icin haric tutulmus vb.) "kayboluyor" gibi goruniyordu.
        // Bu yeni 'all' grubu durum filtresi UYGULAMAZ, secili il/ilce/
        // kategoriye uyan TUM ozel-vakif kurumlari (durumu ne olursa
        // olsun, tablodaki "Durum" kolonuyla birlikte) gosterir ve
        // varsayilan sekme yapildi - eski durum-bazli sekmeler (toplu
        // WhatsApp gonderim is akisi icin) oldugu gibi duruyor.
        'all' => ['title' => 'Kurumlar (Tümü)', 'statuses' => null],
        'to_send' => ['title' => 'Gönderilecekler', 'statuses' => ['not_started']],
        'opened' => ['title' => 'WhatsApp açılanlar', 'statuses' => ['opened']],
        'sent' => ['title' => 'Gönderildi', 'statuses' => ['sent']],
        'claimed' => ['title' => 'Sahiplenme başlatanlar', 'statuses' => ['claimed', 'approved']],
        'unreachable' => ['title' => 'Ulaşılamayanlar', 'statuses' => ['unreachable', 'wrong_number']],
        'landline' => ['title' => 'Sabit hatlılar', 'statuses' => ['landline_only']],
        'contact_missing' => ['title' => 'Telefonu olmayanlar', 'statuses' => ['contact_missing']],
        'do_not_contact' => ['title' => 'İstemeyenler', 'statuses' => ['do_not_contact']],
        'excluded' => ['title' => 'Davet dışı bırakılanlar', 'statuses' => ['excluded']],
    ];

    private const UPDATABLE_STATUSES = [
        'opened', 'sent', 'claimed', 'approved', 'do_not_contact',
        'unreachable', 'wrong_number', 'landline_only', 'contact_missing', 'excluded', 'not_started',
    ];

    public function index(Request $request)
    {
        $group = $request->query('group', 'all');
        if (! isset(self::GROUPS[$group])) {
            $group = 'all';
        }

        // 30 Temmuz 2026: kullanici il/kategori filtresi secince sekmelerdeki
        // sayilarin (ve "Cep telefonu olanlar" sayacinin) HALA ulke geneli
        // gostermesi "mantiksiz" bulundu, cunku listenin kendisi filtreliyken
        // sekme sayilari filtrelenmiyordu - ikisi tutarsizdi. $baseQuery
        // olusturucusu il/ilce/kategori filtrelerini (durum ve cep-telefon
        // filtresi HARIC) hem tabloya hem sekme sayaclarina AYNI sekilde
        // uygular ki "hangi sekmeye/filtreye bakiliyorsa o sayiyi gostersin"
        // beklentisi karsilansin.
        $baseQuery = function () use ($request) {
            $q = Facility::whereIn('ownership_type', ['ozel', 'vakif']);

            if ($request->filled('city')) {
                $q->whereHas('city', fn ($c) => $c->where('slug', $request->city));
            }
            if ($request->filled('district')) {
                $q->where('district', $request->district);
            }
            if ($request->filled('category')) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $request->category));
            }

            return $q;
        };

        $query = $baseQuery()->with(['city', 'category']);

        if (self::GROUPS[$group]['statuses'] !== null) {
            $query->whereIn('invitation_status', self::GROUPS[$group]['statuses']);
        }

        if ($request->boolean('has_mobile')) {
            $query->where('phone_type', 'mobile');
        }

        $facilities = $query->latest()->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        $counts = $baseQuery()
            ->selectRaw('invitation_status, count(*) as total')
            ->groupBy('invitation_status')
            ->pluck('total', 'invitation_status');

        $groupCounts = [];
        foreach (self::GROUPS as $key => $def) {
            $groupCounts[$key] = $def['statuses'] === null
                ? $counts->sum()
                : collect($def['statuses'])->sum(fn ($s) => $counts[$s] ?? 0);
        }

        $mobileCount = $baseQuery()->where('phone_type', 'mobile')->count();

        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $statusLabels = facility_invitation_statuses();
        $groups = self::GROUPS;

        return view('admin.invitations.index', compact(
            'facilities', 'group', 'groups', 'groupCounts', 'mobileCount',
            'cities', 'categories', 'districtMap', 'statusLabels'
        ));
    }

    /**
     * "WhatsApp Aç" butonu: hazir mesajli wa.me linkine yonlendirir ve ayni
     * anda durumu "opened" olarak isaretler (tek tikla hem acilir hem
     * kaydedilir). Daha ileri bir durumdaysa (sent/claimed/approved vb.)
     * geriye dusurmez.
     */
    public function openWhatsapp(Facility $facility)
    {
        $url = facility_whatsapp_url($facility);
        abort_if(! $url, 404, 'Bu kurumun cep telefonu yok.');

        if (in_array($facility->invitation_status, ['not_started', 'opened'], true)) {
            $oldStatus = $facility->invitation_status;
            $facility->update(['invitation_status' => 'opened', 'invitation_status_at' => now()]);
            log_admin_event('facility_invitation_status_changed', $facility, [
                'old_status' => $oldStatus, 'new_status' => 'opened',
            ]);
        }

        return redirect()->away($url);
    }

    // 30 Temmuz 2026: WhatsApp'in kendi politikasi geregi toplu otomatik
    // gonderim yapilamiyor (bkz. kullaniciyla yapilan konusma - resmi API
    // sablonlari onaysiz gonderime izin vermiyor, resmi olmayan otomasyon
    // ise numarayi banlatma riski tasiyor). Bunun yerine INSANIN elle
    // gonderdigi ama kurum kurum ARAMAK ZORUNDA KALMADAN, tek ekrandan
    // art arda hizlica ilerleyebilecegi bir "Hizli Gonderim" modu: her
    // seferinde SIRADAKI 'not_started' (mobil telefonlu, hic gonderilmemis)
    // kurumu tek basina gosterir - WhatsApp'ta Ac yeni sekmede acilir (bu
    // sekme kaybolmaz), Siradaki butonu bir sonrakine gecer.
    public function quickSend(Request $request)
    {
        $query = Facility::with(['city', 'category'])
            ->whereIn('ownership_type', ['ozel', 'vakif'])
            ->where('invitation_status', 'not_started');

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        $facility = (clone $query)->orderBy('id')->first();
        $remaining = $query->count();

        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();

        return view('admin.invitations.quick-send', compact('facility', 'remaining', 'cities', 'categories'));
    }

    public function updateStatus(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', self::UPDATABLE_STATUSES),
        ]);

        $oldStatus = $facility->invitation_status;
        $facility->update(['invitation_status' => $data['status'], 'invitation_status_at' => now()]);

        log_admin_event('facility_invitation_status_changed', $facility, [
            'old_status' => $oldStatus,
            'new_status' => $data['status'],
        ]);

        return back()->with('success', 'Kurum davet durumu güncellendi.');
    }
}
