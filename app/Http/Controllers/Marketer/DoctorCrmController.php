<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Expert;
use App\Models\ExpertMarketingNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 25 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'ine doktorlar. Sekmeler,
// durumlar ve sorular kurum CRM'iyle (CrmController) BİREBİR aynı; yalnızca
// "ilgilendiği paket" seçenekleri doktora göre (ExpertMarketingNote::PACKAGES).
class DoctorCrmController extends Controller
{
    use RedirectsOutOfRangePagination;

    private function baseQuery(Request $request)
    {
        $q = Expert::query()->where('is_published', true)->whereNotNull('phone')->where('phone', '!=', '');
        if ($request->filled('city')) {
            $q->whereHas('city', fn ($c) => $c->where('slug', $request->city));
        }
        if ($request->filled('district')) {
            $q->where('district', $request->district);
        }
        if ($request->filled('branch')) {
            $q->where('branch', $request->branch);
        }
        if ($request->filled('site')) {
            $q->websiteFilter($request->string('site')->toString());
        }
        // Pazarlamacıya atanmış il/ilçe kapsamı doktorlarda da geçerli.
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        if ($marketer?->assigned_city_ids) {
            $q->whereIn('city_id', $marketer->assigned_city_ids);
        }
        if ($marketer?->assigned_districts) {
            $q->whereIn('district', $marketer->assigned_districts);
        }

        return $q;
    }

    public function index(Request $request)
    {
        $groups = CrmController::GROUPS;
        $group = $request->query('group', 'to_call');
        if (! isset($groups[$group])) {
            $group = 'to_call';
        }
        $def = $groups[$group];
        $query = $this->baseQuery($request)->with(['city', 'marketingNotes']);
        if ($def['callback'] ?? false) {
            $query->whereNotNull('marketing_callback_at')->orderBy('marketing_callback_at');
        } else {
            if ($def['statuses'] !== null) {
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
            $query->orderByDesc('marketing_status_at')->orderByDesc('id');
        }
        $experts = $query->paginate(20)->withQueryString();
        if ($redirect = $this->redirectIfPageOutOfRange($request, $experts)) {
            return $redirect;
        }

        $counts = $this->baseQuery($request)->selectRaw('marketing_status, count(*) as total')->groupBy('marketing_status')->pluck('total', 'marketing_status');
        $callbackCount = $this->baseQuery($request)->whereNotNull('marketing_callback_at')->count();
        $groupCounts = [];
        foreach ($groups as $key => $g) {
            $groupCounts[$key] = ($g['callback'] ?? false) ? $callbackCount
                : ($g['statuses'] === null ? $counts->sum() : collect($g['statuses'])->sum(fn ($s) => $counts[$s] ?? 0));
        }

        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        $cities = City::orderBy('name')->when($marketer?->assigned_city_ids, fn ($q) => $q->whereIn('id', $marketer->assigned_city_ids))->get();
        $districtMap = $cities->mapWithKeys(function ($city) use ($marketer) {
            $all = districts_for_city($city->name);

            return [$city->slug => $marketer?->assigned_districts ? array_values(array_intersect($all, $marketer->assigned_districts)) : $all];
        });
        $branches = Expert::query()->where('is_published', true)->whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch');
        $statusLabels = CrmController::STATUSES;

        return view('marketer.crm.doktorlar', compact('experts', 'group', 'groups', 'groupCounts', 'cities', 'districtMap', 'branches', 'statusLabels'));
    }

    public function storeNote(Request $request, Expert $expert)
    {
        abort_unless($this->baseQuery(new Request)->whereKey($expert->id)->exists(), 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(CrmController::STATUSES))],
            'note' => 'nullable|string|max:2000',
            'brochure_requested' => 'nullable|in:0,1',
            'callback_at' => 'nullable|date',
            'contact_name' => 'nullable|string|max:120',
            'contact_role' => 'nullable|in:calisan,mudur,sahibi',
            'website_status' => 'nullable|in:var,yok,sosyal',
            'site_offer_interest' => 'nullable|in:ilgilendi,ilgilenmedi',
            'package_interest' => ['nullable', Rule::in(array_keys(ExpertMarketingNote::PACKAGES))],
        ]);
        ExpertMarketingNote::create([
            'expert_id' => $expert->id,
            'marketer_id' => session('marketer_id'),
            'status' => $data['status'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_role' => $data['contact_role'] ?? null,
            'website_status' => $data['website_status'] ?? null,
            'site_offer_interest' => $data['site_offer_interest'] ?? null,
            'package_interest' => $data['package_interest'] ?? null,
            'note' => $data['note'] ?? null,
            'brochure_requested' => $request->filled('brochure_requested') ? $request->boolean('brochure_requested') : null,
            'callback_at' => $data['callback_at'] ?? null,
        ]);
        $update = ['marketing_status' => $data['status'], 'marketing_status_at' => now()];
        if ($request->filled('brochure_requested')) {
            $update['marketing_brochure_requested'] = $request->boolean('brochure_requested');
        }
        if ($request->filled('callback_at')) {
            $update['marketing_callback_at'] = $data['callback_at'];
        }
        if ($data['website_status'] ?? null) {
            $update['website_status'] = $data['website_status'];
            $update['website_checked_at'] = now();
        }
        $expert->update($update);

        if ($data['status'] === 'removal_requested') {
            $marketer = \App\Models\Marketer::find(session('marketer_id'));
            $body = "{$marketer?->name} tarafından arandı: \"{$expert->name}\" ({$expert->phone}) platformdan kaldırılmasını istiyor.".(($data['note'] ?? null) ? "\nNot: {$data['note']}" : '');
            \App\Models\Admin::all()->each(fn ($admin) => notify_user($admin, 'marketer_removal_requested', 'Doktor kaldırılma talebi: '.$expert->name, $body, ['expert_id' => $expert->id]));
        }

        return back()->with('success', 'Not kaydedildi.');
    }

    public function notifyAdmin(Expert $expert)
    {
        abort_unless($this->baseQuery(new Request)->whereKey($expert->id)->exists(), 404);
        $marketer = \App\Models\Marketer::find(session('marketer_id'));
        $son = $expert->marketingNotes()->first();
        $body = "{$marketer?->name} tarafından doktor \"{$expert->name}\" ({$expert->phone}) ile görüşüldü.\nDurum: ".($son ? (CrmController::STATUSES[$son->status] ?? $son->status) : 'Not yok');
        if ($son?->package_interest) {
            $body .= "\nİlgilendiği paket: ".(ExpertMarketingNote::PACKAGES[$son->package_interest] ?? $son->package_interest);
        }
        if ($son?->note) {
            $body .= "\nNot: {$son->note}";
        }
        \App\Models\Admin::all()->each(fn ($admin) => notify_user($admin, 'marketer_facility_forwarded', 'Pazarlamacıdan doktor bildirimi: '.$expert->name, $body, ['expert_id' => $expert->id]));

        return back()->with('success', 'Doktor kaydı admine gönderildi.');
    }
}
