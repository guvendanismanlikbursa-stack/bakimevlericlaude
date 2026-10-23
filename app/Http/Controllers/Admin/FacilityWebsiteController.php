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
            ->select('facilities.*', 'fw.status as site_durum', 'fw.slug as site_slug', 'fw.custom_domain as site_domain', 'fw.updated_at as site_guncelleme');
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
        match ($request->input('kendi_sitesi')) {
            'yok' => $q->whereIn('facilities.website_status', Facility::WEBSITE_MISSING_STATUSES),
            'var' => $q->where('facilities.website_status', 'var'),
            'bilinmiyor' => $q->whereNull('facilities.website_status'),
            default => null,
        };
        $facilities = $q->orderByRaw("CASE fw.status WHEN 'yayinda' THEN 0 WHEN 'taslak' THEN 1 ELSE 2 END")
            ->orderByDesc('facilities.is_claimed')->orderBy('facilities.name')
            ->paginate(30)->withQueryString();
        $sayilar = [
            'yayinda' => FacilityWebsite::where('status', 'yayinda')->count(),
            'taslak' => FacilityWebsite::where('status', 'taslak')->count(),
        ];
        $cities = \App\Models\City::orderBy('name')->get(['id', 'name']);

        return view('admin.facility-websites.index', compact('facilities', 'sayilar', 'cities'));
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

        $site->applyChanges($content, 'admin', session('admin_id'), session('admin_name'));
        $site->fill([
            'status' => $admin['status'],
            'slug' => $admin['slug'],
            'custom_domain' => $domain,
        ]);
        if ($site->status === 'yayinda' && ! $site->published_at) {
            $site->published_at = now();
        }
        $site->save();
        log_admin_event('facility_website_updated', $facility, ['status' => $site->status, 'slug' => $site->slug, 'custom_domain' => $site->custom_domain]);

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
