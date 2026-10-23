<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Facility\WebsiteController;
use App\Models\Facility;
use App\Models\FacilityWebsite;
use App\Models\FacilityWebsiteRevision;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 23 Ekim 2026: kullanıcının talebi - admin her kurumun sitesini düzenler,
// yayına alır/kaldırır, adresini (slug) ve alan adını belirler; değişiklik
// geçmişinden eski hale döner.
class FacilityWebsiteController extends Controller
{
    /**
     * 23 Ekim 2026: kullanıcının talebi - bütün kurumların siteleri tek listede:
     * yayında / taslak / hiç açılmamış, kendi sitesi olan / olmayan.
     */
    public function index(Request $request)
    {
        $q = Facility::query()->with(['city', 'category'])
            ->leftJoin('facility_websites as fw', 'fw.facility_id', '=', 'facilities.id')
            ->select('facilities.*', 'fw.status as site_durum', 'fw.slug as site_slug', 'fw.custom_domain as site_domain', 'fw.updated_at as site_guncelleme', 'fw.plan as site_plan', 'fw.pro_until as site_pro_bitis', 'fw.id as site_id');
        if ($request->filled('q')) {
            $q->where('facilities.name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('city')) {
            $q->where('facilities.city_id', $request->city);
        }
        match ($request->input('durum')) {
            'yayinda' => $q->where('fw.status', 'yayinda'),
            'taslak' => $q->where('fw.status', 'taslak'),
            'hic' => $q->whereNull('fw.id'),
            default => null,
        };
        // 23 Ekim 2026: paket ve bitiş tarihi filtreleri.
        $bugun = now()->toDateString();
        match ($request->input('paket')) {
            'profesyonel' => $q->where('fw.plan', 'profesyonel')->where(fn ($w) => $w->whereNull('fw.pro_until')->orWhere('fw.pro_until', '>=', $bugun)),
            'standart' => $q->where('fw.status', 'yayinda')->where(fn ($w) => $w->where('fw.plan', '!=', 'profesyonel')->orWhereNull('fw.plan')->orWhere('fw.pro_until', '<', $bugun)),
            'bitiyor' => $q->where('fw.plan', 'profesyonel')->whereBetween('fw.pro_until', [$bugun, now()->addDays(30)->toDateString()]),
            'dolmus' => $q->where('fw.plan', 'profesyonel')->where('fw.pro_until', '<', $bugun),
            default => null,
        };
        if ($request->filled('bitis_bas')) {
            $q->whereDate('fw.pro_until', '>=', $request->date('bitis_bas'));
        }
        if ($request->filled('bitis_son')) {
            $q->whereDate('fw.pro_until', '<=', $request->date('bitis_son'));
        }
        if ($request->filled('bitis_bas') || $request->filled('bitis_son') || in_array($request->input('paket'), ['bitiyor', 'dolmus', 'profesyonel'], true)) {
            $q->orderBy('fw.pro_until');
        }
        match ($request->input('kendi_sitesi')) {
            'yok' => $q->whereIn('facilities.website_status', Facility::WEBSITE_MISSING_STATUSES),
            'var' => $q->where('facilities.website_status', 'var'),
            'bilinmiyor' => $q->whereNull('facilities.website_status'),
            default => null,
        };
        $facilities = $q->orderByRaw("CASE fw.status WHEN 'yayinda' THEN 0 WHEN 'taslak' THEN 1 ELSE 2 END")
            ->orderByDesc('facilities.is_claimed')->orderBy('facilities.name')
            ->paginate(30)->withQueryString();
        // Listelenen sitelerin son 30 gün sayıları (tek sorgu).
        $siteStat = \Illuminate\Support\Facades\DB::table('facility_website_stats')
            ->whereIn('facility_website_id', $facilities->pluck('site_id')->filter()->all())
            ->where('gun', '>=', now()->subDays(29)->toDateString())
            ->groupBy('facility_website_id')
            ->selectRaw('facility_website_id, SUM(ziyaret) ziyaret, SUM(whatsapp) whatsapp, SUM(arama) arama, SUM(form) form')
            ->get()->keyBy('facility_website_id');
        $proFiyat = \App\Models\Setting::get('kurum_sitesi_pro_fiyat', '14.900 ₺ / yıl');
        $cities = \App\Models\City::orderBy('name')->get(['id', 'name']);

        return view('admin.facility-websites.index', compact('facilities', 'siteStat', 'cities', 'proFiyat'));
    }

    /**
     * 23 Ekim 2026: kullanıcının talebi - tek tıkla Profesyonel Site:
     * paket 1 yıllığına açılır ve site yayına alınır; açıksa kapatılır
     * (site hazır tasarıma döner, yayında kalır).
     */
    public function togglePro(Facility $facility)
    {
        $site = FacilityWebsite::forFacility($facility);
        if ($site->isPro()) {
            // Site bu düğmeyle yayına alındıysa kapatınca taslağa döner.
            $taslagaDon = (bool) $site->pro_yayina_aldi;
            $site->update(['plan' => 'standart', 'pro_until' => null, 'pro_yayina_aldi' => false] + ($taslagaDon ? ['status' => 'taslak'] : []));
            $mesaj = $facility->name.': Profesyonel Site kapatıldı'.($taslagaDon ? ', site yayından kaldırıldı (taslak).' : ', site hazır tasarımla yayında.');
        } else {
            $site->fill(['plan' => 'profesyonel', 'pro_until' => now()->addYear()->toDateString(), 'pro_yayina_aldi' => ! $site->isLive(), 'status' => 'yayinda']);
            if (! $site->published_at) {
                $site->published_at = now();
            }
            $site->save();
            $mesaj = $facility->name.': Profesyonel Site açıldı ('.$site->pro_until->format('d.m.Y').' tarihine kadar) ve site yayında.';
        }
        log_admin_event('facility_website_pro_toggled', $facility, ['plan' => $site->plan, 'pro_until' => optional($site->pro_until)->toDateString()]);

        return back()->with('success', $mesaj);
    }

    /**
     * Kurum panelindeki Profesyonel Site tanıtımında görünen fiyat yazısı.
     */
    public function savePrice(Request $request)
    {
        $data = $request->validate(['fiyat' => 'required|string|max:80']);
        \App\Models\Setting::set('kurum_sitesi_pro_fiyat', trim($data['fiyat']));

        return back()->with('success', 'Profesyonel Site fiyatı kaydedildi.');
    }

    /**
     * Listeden tek tıkla önizleme: kayıt yoksa taslak olarak oluşturulur.
     */
    public function preview(Facility $facility)
    {
        $site = FacilityWebsite::forFacility($facility);

        return redirect('/siteler/'.$site->slug.'?onizleme=1');
    }

    public function edit(Facility $facility)
    {
        $site = FacilityWebsite::forFacility($facility);
        $facility->load('images');
        $images = $facility->images->reject(fn ($img) => str_starts_with((string) $img->path, 'facilities/demo/'))->values();
        $revisions = $site->revisions()->limit(30)->get();

        return view('admin.facility-websites.edit', compact('facility', 'site', 'images', 'revisions'));
    }

    public function update(Request $request, Facility $facility)
    {
        $site = FacilityWebsite::forFacility($facility);
        $content = WebsiteController::validateContent($request, $facility->id);
        $admin = $request->validate([
            'status' => ['required', Rule::in(array_keys(FacilityWebsite::STATUSES))],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('facility_websites', 'slug')->ignore($site->id)],
            'custom_domain' => ['nullable', 'string', 'max:190'],
            'plan' => ['nullable', Rule::in(array_keys(FacilityWebsite::PLANS))],
            'pro_until' => ['nullable', 'date'],
        ], ['slug.regex' => 'Adres yalnızca küçük harf, rakam ve tire içerebilir (ör. gunes-bakimevi).']);

        $domain = FacilityWebsite::normalizeDomain($admin['custom_domain'] ?? null);
        if ($domain) {
            $brandHosts = collect(config('brands.brands', []))->pluck('domains')->flatten()->map(fn ($d) => strtolower((string) $d))->all();
            $request->validate(['custom_domain' => [function ($attr, $value, $fail) use ($domain, $site, $brandHosts) {
                if (in_array($domain, $brandHosts, true)) {
                    $fail('Platformun kendi alan adları kurum sitesine verilemez.');
                } elseif (! preg_match('/^(?:[a-z0-9-]+\.)+[a-z]{2,}$/', $domain)) {
                    $fail('Alan adı geçersiz (ör. gunesbakimevi.com).');
                } elseif (\App\Models\ExpertWebsite::domainTaken($domain, 'facility', $site->id)) {
                    $fail('Bu alan adı başka bir kurum ya da doktor sitesinde kullanılıyor.');
                }
            }]]);
        }

        if (\App\Services\KurumSitesiProForm::submitted($request)) {
            $content = array_merge($content, app(\App\Services\KurumSitesiProForm::class)->validated($request, $site));
        }
        $site->applyChanges($content, 'admin', session('admin_id'), session('admin_name'));
        $site->fill([
            'status' => $admin['status'],
            'slug' => $admin['slug'],
            'custom_domain' => $domain,
            'plan' => $admin['plan'] ?? ($site->plan ?: 'standart'),
            'pro_until' => $admin['pro_until'] ?? null,
        ]);
        if ($site->status === 'yayinda' && ! $site->published_at) {
            $site->published_at = now();
        }
        $site->save();
        log_admin_event('facility_website_updated', $facility, ['status' => $site->status, 'slug' => $site->slug, 'custom_domain' => $site->custom_domain, 'plan' => $site->plan]);

        return redirect()->route('admin.facility-websites.edit', $facility)->with('success', 'Kurum sitesi kaydedildi.');
    }

    public function revert(Facility $facility, FacilityWebsiteRevision $revision)
    {
        $site = FacilityWebsite::forFacility($facility);
        abort_unless((int) $revision->facility_website_id === (int) $site->id, 404);
        $site->applyChanges((array) $revision->before, 'admin', session('admin_id'), (session('admin_name') ?: 'Admin').' (geri alma)');

        return redirect()->route('admin.facility-websites.edit', $facility)->with('success', 'Site, seçilen değişiklikten önceki haline döndürüldü.');
    }
}
