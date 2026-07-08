<?php

namespace App\Http\Controllers\Admin;

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
    private const GROUPS = [
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
        $group = $request->query('group', 'to_send');
        if (! isset(self::GROUPS[$group])) {
            $group = 'to_send';
        }

        $query = Facility::with(['city', 'category'])
            ->whereIn('ownership_type', ['ozel', 'vakif'])
            ->whereIn('invitation_status', self::GROUPS[$group]['statuses']);

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->boolean('has_mobile')) {
            $query->where('phone_type', 'mobile');
        }

        $facilities = $query->latest()->paginate(20)->withQueryString();

        $counts = Facility::whereIn('ownership_type', ['ozel', 'vakif'])
            ->selectRaw('invitation_status, count(*) as total')
            ->groupBy('invitation_status')
            ->pluck('total', 'invitation_status');

        $groupCounts = [];
        foreach (self::GROUPS as $key => $def) {
            $groupCounts[$key] = collect($def['statuses'])->sum(fn ($s) => $counts[$s] ?? 0);
        }

        $mobileCount = Facility::whereIn('ownership_type', ['ozel', 'vakif'])->where('phone_type', 'mobile')->count();

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
            $facility->update(['invitation_status' => 'opened', 'invitation_status_at' => now()]);
            log_admin_event('facility_invitation_status_changed', $facility, [
                'old_status' => 'not_started', 'new_status' => 'opened',
            ]);
        }

        return redirect()->away($url);
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
