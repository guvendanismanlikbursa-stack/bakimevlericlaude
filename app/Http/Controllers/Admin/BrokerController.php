<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\BrokerReferral;
use App\Models\Facility;
use Illuminate\Http\Request;

// 25 Agustos 2026: kullanicinin talebi - kendi kisisel "aracilik" isini
// (bazi Bursa kurumlariyla anlasip aileleri yerlestirip komisyon alma)
// takip edebilecegi kucuk bir CRM. Herkese acik hicbir sayfayi/akisi
// etkilemez - sadece admin panelde yeni, ayri bir bolum. bkz.
// FacilityInvitationController - sekme/durum filtreleme deseni oradan
// alindi (tutarlilik icin).
class BrokerController extends Controller
{
    use RedirectsOutOfRangePagination;

    private const GROUPS = [
        'tumu' => ['title' => 'Tümü', 'statuses' => null],
        'yonlendirildi' => ['title' => 'Yönlendirildi', 'statuses' => ['yonlendirildi']],
        'randevu_alindi' => ['title' => 'Randevu Alındı', 'statuses' => ['randevu_alindi']],
        'yerlesti' => ['title' => 'Yerleşti', 'statuses' => ['yerlesti']],
        'iptal' => ['title' => 'İptal / Vazgeçti', 'statuses' => ['iptal']],
    ];

    private const STATUS_LABELS = [
        'yonlendirildi' => 'Yönlendirildi',
        'randevu_alindi' => 'Randevu Alındı',
        'yerlesti' => 'Yerleşti',
        'iptal' => 'İptal / Vazgeçti',
    ];

    // 25 Agustos 2026: kullanicinin talebi - ucretsiz ziyaret hizmeti icin
    // hastanin hareket durumu (kime nasil bir ziyaret gerekecegi).
    private const MOBILITY_LABELS = [
        'yatalak' => 'Yatağa Bağımlı',
        'kismi_bagimli' => 'Kısmi Bağımlı',
        'yurutebiliyor' => 'Yürüyebiliyor',
    ];

    /**
     * "Anlaşmalı Kurumlar" sekmesi: kurum listesinde arama yapip tek tikla
     * bir kurumu aracilik havuzuna ekleyip cikarabilme.
     */
    public function facilities(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Facility::with(['city', 'category'])->whereNull('deleted_at');

        if ($request->boolean('only_managed', true) && $q === '') {
            $query->where('is_broker_managed', true);
        }

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        $facilities = $query->orderByDesc('is_broker_managed')->orderBy('name')->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        $managedCount = Facility::where('is_broker_managed', true)->count();

        return view('admin.broker.facilities', compact('facilities', 'q', 'managedCount'));
    }

    public function toggleFacility(Facility $facility)
    {
        $facility->update(['is_broker_managed' => ! $facility->is_broker_managed]);

        return back()->with('success', $facility->is_broker_managed
            ? "\"{$facility->name}\" anlaşmalı kurumlar listesine eklendi."
            : "\"{$facility->name}\" anlaşmalı kurumlar listesinden çıkarıldı.");
    }

    /**
     * "Yönlendirmeler" sekmesi: aile-kurum eslesmelerinin asama/ucret takibi.
     */
    public function referrals(Request $request)
    {
        $group = $request->query('group', 'tumu');
        if (! isset(self::GROUPS[$group])) {
            $group = 'tumu';
        }

        $query = BrokerReferral::with('facility')->latest('referred_at');

        if (self::GROUPS[$group]['statuses'] !== null) {
            $query->whereIn('status', self::GROUPS[$group]['statuses']);
        }

        $referrals = $query->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $referrals)) {
            return $redirect;
        }

        $counts = BrokerReferral::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $groupCounts = [];
        foreach (self::GROUPS as $key => $def) {
            $groupCounts[$key] = $def['statuses'] === null
                ? $counts->sum()
                : collect($def['statuses'])->sum(fn ($s) => $counts[$s] ?? 0);
        }

        $pendingFeeTotal = BrokerReferral::where('fee_status', 'bekliyor')->whereNotNull('fee_amount')->sum('fee_amount');
        $managedFacilities = Facility::where('is_broker_managed', true)->orderBy('name')->get(['id', 'name']);
        $groups = self::GROUPS;
        $statusLabels = self::STATUS_LABELS;
        $mobilityLabels = self::MOBILITY_LABELS;

        return view('admin.broker.referrals', compact(
            'referrals', 'group', 'groups', 'groupCounts', 'pendingFeeTotal', 'managedFacilities', 'statusLabels', 'mobilityLabels'
        ));
    }

    public function storeReferral(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'family_name' => 'required|string|max:150',
            'family_phone' => 'nullable|string|max:30',
            'patient_name' => 'nullable|string|max:150',
            'patient_age' => 'nullable|integer|min:0|max:130',
            'patient_mobility' => 'nullable|string|in:'.implode(',', array_keys(self::MOBILITY_LABELS)),
            'wants_visit' => 'nullable|in:1,0',
            'fee_amount' => 'nullable|numeric|min:0',
            'referred_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['wants_visit'] = $request->filled('wants_visit') ? $request->boolean('wants_visit') : null;

        BrokerReferral::create($data + ['status' => 'yonlendirildi', 'fee_status' => 'bekliyor']);

        return back()->with('success', 'Yeni yönlendirme eklendi.');
    }

    public function updateReferral(Request $request, BrokerReferral $referral)
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(self::STATUS_LABELS)),
            'fee_status' => 'required|string|in:bekliyor,odendi',
            'fee_amount' => 'nullable|numeric|min:0',
            'placed_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'patient_name' => 'nullable|string|max:150',
            'patient_age' => 'nullable|integer|min:0|max:130',
            'patient_mobility' => 'nullable|string|in:'.implode(',', array_keys(self::MOBILITY_LABELS)),
            'wants_visit' => 'nullable|in:1,0',
        ]);
        $data['wants_visit'] = $request->filled('wants_visit') ? $request->boolean('wants_visit') : null;

        $referral->update($data);

        return back()->with('success', 'Yönlendirme güncellendi.');
    }
}
