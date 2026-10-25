<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertWebsite;
use App\Models\ExpertWebsiteRevision;
use App\Services\CrossDomainImageSync;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 23 Ekim 2026: kullanıcının talebi - doktor siteleri yalnızca admin
// panelinden düzenlenir: başlık, hakkımda, ilgi alanları, çalışma saatleri,
// fotoğraf, renk, bölümler, yayın durumu, adres ve alan adı.
class ExpertWebsiteController extends Controller
{
    /**
     * 25 Ekim 2026: kullanıcının talebi - "Doktor Siteleri" listesi (Kurum
     * Siteleri ile aynı yapı): filtreler, özet, tek tıkla standart/profesyonel.
     */
    public function index(Request $request)
    {
        $q = Expert::query()->with('city')
            ->leftJoin('expert_websites as ew', 'ew.expert_id', '=', 'experts.id')
            ->select('experts.*', 'ew.status as site_durum', 'ew.slug as site_slug', 'ew.custom_domain as site_domain', 'ew.plan as site_plan', 'ew.pro_until as site_pro_bitis', 'ew.pro_tasarim as site_tasarim', 'ew.id as site_id');
        if ($request->filled('q')) {
            $q->where('experts.name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('city')) {
            $q->where('experts.city_id', $request->city);
        }
        if ($request->filled('branch')) {
            $q->where('experts.branch', $request->branch);
        }
        match ($request->input('durum')) {
            'yayinda' => $q->where('ew.status', 'yayinda'),
            'taslak' => $q->where('ew.status', 'taslak'),
            'hic' => $q->whereNull('ew.id'),
            default => null,
        };
        $bugun = now()->toDateString();
        match ($request->input('paket')) {
            'profesyonel' => $q->where('ew.plan', 'profesyonel')->where(fn ($w) => $w->whereNull('ew.pro_until')->orWhere('ew.pro_until', '>=', $bugun)),
            'standart' => $q->where('ew.status', 'yayinda')->where(fn ($w) => $w->where('ew.plan', '!=', 'profesyonel')->orWhereNull('ew.plan')->orWhere('ew.pro_until', '<', $bugun)),
            'bitiyor' => $q->where('ew.plan', 'profesyonel')->whereBetween('ew.pro_until', [$bugun, now()->addDays(30)->toDateString()]),
            'dolmus' => $q->where('ew.plan', 'profesyonel')->where('ew.pro_until', '<', $bugun),
            default => null,
        };
        if ($request->filled('bitis_bas')) {
            $q->whereDate('ew.pro_until', '>=', $request->date('bitis_bas'));
        }
        if ($request->filled('bitis_son')) {
            $q->whereDate('ew.pro_until', '<=', $request->date('bitis_son'));
        }
        match ($request->input('kendi_sitesi')) {
            'yok' => $q->whereIn('experts.website_status', \App\Models\Facility::WEBSITE_MISSING_STATUSES),
            'var' => $q->where('experts.website_status', 'var'),
            'bilinmiyor' => $q->whereNull('experts.website_status'),
            default => null,
        };
        $experts = $q->orderByRaw("CASE ew.status WHEN 'yayinda' THEN 0 WHEN 'taslak' THEN 1 ELSE 2 END")
            ->orderBy('experts.name')->paginate(30)->withQueryString();
        $ozet = ExpertWebsite::summary();
        $siteStat = \Illuminate\Support\Facades\DB::table('expert_website_stats')
            ->whereIn('expert_website_id', $experts->pluck('site_id')->filter()->all())
            ->where('gun', '>=', now()->subDays(29)->toDateString())
            ->groupBy('expert_website_id')
            ->selectRaw('expert_website_id, SUM(ziyaret) ziyaret, SUM(whatsapp) whatsapp, SUM(arama) arama, SUM(form) form')
            ->get()->keyBy('expert_website_id');
        $cities = \App\Models\City::orderBy('name')->get(['id', 'name']);
        $branches = Expert::query()->whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch');

        return view('admin.expert-websites.index', compact('experts', 'ozet', 'cities', 'branches', 'siteStat') + ['kartStat' => Expert::cardStats30($experts->pluck('id')->all())]);
    }

    /**
     * Tek tıkla standart site: yayında değilse standart olarak yayına alınır;
     * yayındaki standart site yayından kaldırılır (taslak).
     */
    public function toggleStandard(Expert $expert)
    {
        $site = ExpertWebsite::forExpert($expert);
        if ($site->isLive() && ! $site->isPro()) {
            $site->update(['status' => 'taslak']);
            $mesaj = $site->fullName().': site yayından kaldırıldı (taslak).';
        } else {
            $site->fill(['status' => 'yayinda', 'plan' => 'standart', 'pro_until' => null, 'pro_yayina_aldi' => false]);
            $site->published_at ??= now();
            $site->save();
            $mesaj = $site->fullName().': standart site yayında.';
        }
        log_admin_event('expert_website_standard_toggled', $expert, ['status' => $site->status]);

        return back()->with('success', $mesaj);
    }

    /**
     * Tek tıkla Profesyonel Site: 1 yıllık açılır ve yayına alınır; açıksa
     * kapatılır (site bu düğmeyle yayına alındıysa taslağa döner).
     */
    /**
     * 25 Ekim 2026: kullanıcının talebi - Profesyonel sitenin tasarımını (Klasik / Hareketli)
     * listeden tek tıkla seçme; kurumla/doktorla görüştükten sonra admin karar verir.
     */
    public function setDesign(Request $request, Expert $expert)
    {
        $tasarim = $request->validate(['tasarim' => ['required', Rule::in(array_keys(ExpertWebsite::PRO_TASARIMLAR))]])['tasarim'];
        $site = ExpertWebsite::forExpert($expert);
        $site->update(['pro_tasarim' => $tasarim]);
        log_admin_event('expert_website_design_set', $expert, ['tasarim' => $tasarim]);

        return back()->with('success', ''.$expert->name.': Profesyonel tasarım "'.strip_tags(ExpertWebsite::PRO_TASARIMLAR[$tasarim]).'" olarak seçildi.'.($site->isPro() ? '' : ' (Paket Profesyonel olunca geçerli olur.)'));
    }

    public function togglePro(Expert $expert)
    {
        $site = ExpertWebsite::forExpert($expert);
        if ($site->isPro()) {
            $taslagaDon = (bool) $site->pro_yayina_aldi;
            $site->update(['plan' => 'standart', 'pro_until' => null, 'pro_yayina_aldi' => false] + ($taslagaDon ? ['status' => 'taslak'] : []));
            $mesaj = $site->fullName().': Profesyonel Site kapatıldı'.($taslagaDon ? ', site yayından kaldırıldı.' : ', site standart tasarımla yayında.');
        } else {
            $site->fill(['plan' => 'profesyonel', 'pro_until' => now()->addYear()->toDateString(), 'pro_yayina_aldi' => ! $site->isLive(), 'status' => 'yayinda']);
            $site->published_at ??= now();
            $site->save();
            $mesaj = $site->fullName().': Profesyonel Site açıldı ('.$site->pro_until->format('d.m.Y').' tarihine kadar).';
        }
        log_admin_event('expert_website_pro_toggled', $expert, ['plan' => $site->plan]);

        return back()->with('success', $mesaj);
    }

    public function edit(Expert $expert)
    {
        $site = ExpertWebsite::forExpert($expert);
        $revisions = $site->revisions()->limit(30)->get();

        return view('admin.expert-websites.edit', compact('expert', 'site', 'revisions'));
    }

    public function update(Request $request, Expert $expert, ImageCompressionService $images, CrossDomainImageSync $imageSync)
    {
        $site = ExpertWebsite::forExpert($expert);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ExpertWebsite::STATUSES))],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('expert_websites', 'slug')->ignore($site->id)],
            'custom_domain' => ['nullable', 'string', 'max:190'],
            'hero_title' => ['nullable', 'string', 'max:150'],
            'hero_subtitle' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:5000'],
            'ilgi_alanlari' => ['nullable', 'string', 'max:3000'],
            'calisma_saatleri' => ['nullable', 'string', 'max:1000'],
            'theme' => ['required', Rule::in(array_keys(ExpertWebsite::THEMES))],
            'sections' => ['nullable', 'array'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_photo' => ['nullable', 'boolean'],
            'plan' => ['nullable', Rule::in(array_keys(ExpertWebsite::PLANS))],
            'pro_until' => ['nullable', 'date'],
            'pro_tasarim' => ['nullable', Rule::in(array_keys(ExpertWebsite::PRO_TASARIMLAR))],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_style' => ['nullable', Rule::in(array_keys(ExpertWebsite::FONT_STYLES))],
            'video_url' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'stats' => ['nullable', 'array', 'max:'.ExpertWebsite::LIMITS['stats']],
            'stats.*.deger' => ['nullable', 'string', 'max:20'],
            'stats.*.etiket' => ['nullable', 'string', 'max:60'],
            'faqs' => ['nullable', 'array', 'max:'.ExpertWebsite::LIMITS['faqs']],
            'faqs.*.soru' => ['nullable', 'string', 'max:200'],
            'faqs.*.cevap' => ['nullable', 'string', 'max:1500'],
            'announcements' => ['nullable', 'array', 'max:'.ExpertWebsite::LIMITS['announcements']],
            'announcements.*.baslik' => ['nullable', 'string', 'max:150'],
            'announcements.*.tarih' => ['nullable', 'date'],
            'announcements.*.metin' => ['nullable', 'string', 'max:2000'],
        ], ['slug.regex' => 'Adres yalnızca küçük harf, rakam ve tire içerebilir (ör. dr-ayse-yilmaz).']);

        $domain = \App\Models\FacilityWebsite::normalizeDomain($data['custom_domain'] ?? null);
        if ($domain) {
            $brandHosts = collect(config('brands.brands', []))->pluck('domains')->flatten()->map(fn ($d) => strtolower((string) $d))->all();
            $request->validate(['custom_domain' => [function ($attr, $value, $fail) use ($domain, $site, $brandHosts) {
                if (in_array($domain, $brandHosts, true)) {
                    $fail('Platformun kendi alan adları doktor sitesine verilemez.');
                } elseif (! preg_match('/^(?:[a-z0-9-]+\.)+[a-z]{2,}$/', $domain)) {
                    $fail('Alan adı geçersiz (ör. drayseyilmaz.com).');
                } elseif (ExpertWebsite::domainTaken($domain, 'expert', $site->id)) {
                    $fail('Bu alan adı başka bir kurum ya da doktor sitesinde kullanılıyor.');
                }
            }]]);
        }

        $sections = [];
        foreach (array_keys(ExpertWebsite::SECTIONS) as $key) {
            $sections[$key] = ! empty($data['sections'][$key]);
        }
        $content = [
            'hero_title' => $data['hero_title'] ?? null,
            'hero_subtitle' => $data['hero_subtitle'] ?? null,
            'about_text' => $data['about_text'] ?? null,
            'ilgi_alanlari' => $data['ilgi_alanlari'] ?? null,
            'calisma_saatleri' => $data['calisma_saatleri'] ?? null,
            'theme' => $data['theme'],
            'sections' => $sections,
        ];

        // Fotoğraf kurum fotoğraflarıyla aynı klasöre kaydedilir: diğer iki
        // siteye eşitleme yalnızca bu klasördeki dosyaları kabul ediyor.
        // Eski dosya silinmez; geri alma ile eski fotoğrafa dönülebilir.
        if ($request->hasFile('photo')) {
            try {
                $path = $images->store($request->file('photo'), 'facilities');
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['photo' => 'Fotoğraf okunamadı. JPG, PNG veya WEBP yükleyin.']);
            }
            $imageSync->syncStore($path);
            $content['photo_path'] = $path;
        } elseif (! empty($data['remove_photo'])) {
            $content['photo_path'] = null;
        }

        // 25 Ekim 2026: Profesyonel Site içerikleri (form "pro_form" ile gelir).
        if ($request->has('pro_form')) {
            if ($request->filled('video_url') && ! (new ExpertWebsite(['video_url' => $request->input('video_url')]))->videoEmbedUrl()) {
                return back()->withInput()->withErrors(['video_url' => 'Yalnızca YouTube video adresi girilebilir.']);
            }
            $satir = function ($girdi, array $alanlar, array $zorunlu) {
                $out = [];
                foreach ((array) $girdi as $s) {
                    if (! is_array($s)) {
                        continue;
                    }
                    $r = [];
                    foreach ($alanlar as $a) {
                        $r[$a] = trim((string) ($s[$a] ?? ''));
                    }
                    if (! array_filter($zorunlu, fn ($a) => $r[$a] === '')) {
                        $out[] = $r;
                    }
                }

                return $out ?: null;
            };
            $content['brand_color'] = $request->filled('brand_color') ? strtolower($request->input('brand_color')) : null;
            $content['font_style'] = $request->input('font_style') ?: 'zarif';
            $content['video_url'] = $request->filled('video_url') ? trim($request->input('video_url')) : null;
            $content['stats'] = $satir($request->input('stats', []), ['deger', 'etiket'], ['deger', 'etiket']);
            $content['faqs'] = $satir($request->input('faqs', []), ['soru', 'cevap'], ['soru', 'cevap']);
            $duyuru = $satir($request->input('announcements', []), ['baslik', 'tarih', 'metin'], ['baslik']);
            $content['announcements'] = $duyuru ? collect($duyuru)->sortByDesc(fn ($r) => $r['tarih'] ?? '')->values()->all() : null;
            if ($request->hasFile('logo')) {
                try {
                    $logo = $images->store($request->file('logo'), 'facilities');
                } catch (\RuntimeException $e) {
                    return back()->withInput()->withErrors(['logo' => 'Logo okunamadı. JPG, PNG veya WEBP yükleyin.']);
                }
                $imageSync->syncStore($logo);
                $content['logo_path'] = $logo;
            } elseif ($request->boolean('remove_logo')) {
                $content['logo_path'] = null;
            }
        }

        $site->applyChanges($content, session('admin_id'), session('admin_name'));
        $site->fill(['status' => $data['status'], 'slug' => $data['slug'], 'custom_domain' => $domain,
            'plan' => $data['plan'] ?? ($site->plan ?: 'standart'), 'pro_until' => $data['pro_until'] ?? null,
            'pro_tasarim' => $data['pro_tasarim'] ?? ($site->pro_tasarim ?: 'klasik')]);
        if ($site->status === 'yayinda' && ! $site->published_at) {
            $site->published_at = now();
        }
        $site->save();
        log_admin_event('expert_website_updated', $expert, ['status' => $site->status, 'slug' => $site->slug, 'custom_domain' => $site->custom_domain]);

        return redirect()->route('admin.experts.website.edit', $expert)->with('success', 'Doktor sitesi kaydedildi.');
    }

    public function preview(Request $request, Expert $expert)
    {
        $site = ExpertWebsite::forExpert($expert);
        $tasarim = in_array($request->query('tasarim'), ['pro', 'standart', 'klasik', 'hareketli'], true) ? '&tasarim='.$request->query('tasarim') : '';

        return redirect('/dr/'.$site->slug.'?onizleme=1'.$tasarim);
    }

    public function revert(Expert $expert, ExpertWebsiteRevision $revision)
    {
        $site = ExpertWebsite::forExpert($expert);
        abort_unless((int) $revision->expert_website_id === (int) $site->id, 404);
        $site->applyChanges((array) $revision->before, session('admin_id'), (session('admin_name') ?: 'Admin').' (geri alma)');

        return redirect()->route('admin.experts.website.edit', $expert)->with('success', 'Site, seçilen değişiklikten önceki haline döndürüldü.');
    }
}
