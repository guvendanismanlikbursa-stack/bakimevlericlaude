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

        $site->applyChanges($content, session('admin_id'), session('admin_name'));
        $site->fill(['status' => $data['status'], 'slug' => $data['slug'], 'custom_domain' => $domain]);
        if ($site->status === 'yayinda' && ! $site->published_at) {
            $site->published_at = now();
        }
        $site->save();
        log_admin_event('expert_website_updated', $expert, ['status' => $site->status, 'slug' => $site->slug, 'custom_domain' => $site->custom_domain]);

        return redirect()->route('admin.experts.website.edit', $expert)->with('success', 'Doktor sitesi kaydedildi.');
    }

    public function preview(Expert $expert)
    {
        $site = ExpertWebsite::forExpert($expert);

        return redirect('/dr/'.$site->slug.'?onizleme=1');
    }

    public function revert(Expert $expert, ExpertWebsiteRevision $revision)
    {
        $site = ExpertWebsite::forExpert($expert);
        abort_unless((int) $revision->expert_website_id === (int) $site->id, 404);
        $site->applyChanges((array) $revision->before, session('admin_id'), (session('admin_name') ?: 'Admin').' (geri alma)');

        return redirect()->route('admin.experts.website.edit', $expert)->with('success', 'Site, seçilen değişiklikten önceki haline döndürüldü.');
    }
}
