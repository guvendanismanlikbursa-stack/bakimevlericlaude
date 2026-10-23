<?php

namespace App\Services;

use App\Models\FacilityWebsite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 23 Ekim 2026: kullanıcının talebi - "Profesyonel Site" içerik formu
// (logo, renk, yazı stili, video, rakamlar, neden biz, ekip, SSS, duyurular).
// Kurum paneli ve admin paneli aynı doğrulamayı kullanır. Fotoğraflar kurum
// fotoğraflarıyla aynı klasöre kaydedilir (diğer sitelere eşitleme yalnızca
// o klasörü kabul ediyor). Eski dosyalar silinmez; geri alma çalışsın diye.
class KurumSitesiProForm
{
    public function __construct(private ImageCompressionService $images, private CrossDomainImageSync $sync)
    {
    }

    /** Formda bu alanlardan biri gelmişse profesyonel içerik de işlenir. */
    public static function submitted(Request $request): bool
    {
        return $request->has('pro_form');
    }

    public function validated(Request $request, FacilityWebsite $site): array
    {
        $l = FacilityWebsite::LIMITS;
        $request->validate([
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_style' => ['required', Rule::in(array_keys(FacilityWebsite::FONT_STYLES))],
            'video_url' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'stats' => ['nullable', 'array', 'max:'.$l['stats']],
            'stats.*.deger' => ['nullable', 'string', 'max:20'],
            'stats.*.etiket' => ['nullable', 'string', 'max:60'],
            'values_list' => ['nullable', 'array', 'max:'.$l['values_list']],
            'values_list.*.baslik' => ['nullable', 'string', 'max:80'],
            'values_list.*.metin' => ['nullable', 'string', 'max:300'],
            'team' => ['nullable', 'array', 'max:'.$l['team']],
            'team.*.ad' => ['nullable', 'string', 'max:100'],
            'team.*.unvan' => ['nullable', 'string', 'max:100'],
            'team.*.aciklama' => ['nullable', 'string', 'max:400'],
            'team.*.foto_dosya' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'team.*.foto' => ['nullable', 'string', 'max:255'],
            'team.*.foto_sil' => ['nullable', 'boolean'],
            'faqs' => ['nullable', 'array', 'max:'.$l['faqs']],
            'faqs.*.soru' => ['nullable', 'string', 'max:200'],
            'faqs.*.cevap' => ['nullable', 'string', 'max:1500'],
            'announcements' => ['nullable', 'array', 'max:'.$l['announcements']],
            'announcements.*.baslik' => ['nullable', 'string', 'max:150'],
            'announcements.*.tarih' => ['nullable', 'date'],
            'announcements.*.metin' => ['nullable', 'string', 'max:2000'],
        ], [
            'brand_color.regex' => 'Renk kodu geçersiz (ör. #0d6e6a).',
            'logo.mimes' => 'Logo JPG, PNG veya WEBP olmalı.',
        ]);
        if ($request->filled('video_url') && ! (new FacilityWebsite(['video_url' => $request->input('video_url')]))->videoEmbedUrl()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['video_url' => 'Yalnızca YouTube video adresi girilebilir.']);
        }

        $temiz = fn ($v) => trim((string) $v);
        $data = [
            'brand_color' => $request->filled('brand_color') ? strtolower($request->input('brand_color')) : null,
            'font_style' => $request->input('font_style'),
            'video_url' => $request->filled('video_url') ? $temiz($request->input('video_url')) : null,
            'stats' => $this->rows($request->input('stats', []), ['deger', 'etiket'], ['deger', 'etiket']),
            'values_list' => $this->rows($request->input('values_list', []), ['baslik', 'metin'], ['baslik']),
            'faqs' => $this->rows($request->input('faqs', []), ['soru', 'cevap'], ['soru', 'cevap']),
            'announcements' => collect($this->rows($request->input('announcements', []), ['baslik', 'tarih', 'metin'], ['baslik']))
                ->sortByDesc(fn ($r) => $r['tarih'] ?? '')->values()->all(),
        ];

        // Logo.
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->store($request->file('logo'), 'logo');
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }

        // Ekip: mevcut foto yolu yalnızca bu sitenin kayıtlı ekibinde varsa kabul edilir.
        $mevcutFotolar = collect((array) $site->team)->pluck('foto')->filter()->all();
        $ekip = [];
        foreach ((array) $request->input('team', []) as $i => $satir) {
            $ad = $temiz($satir['ad'] ?? '');
            $dosya = $request->file("team.$i.foto_dosya");
            if ($ad === '') {
                continue;
            }
            $foto = in_array($satir['foto'] ?? null, $mevcutFotolar, true) ? $satir['foto'] : null;
            if ($dosya) {
                $foto = $this->store($dosya, "team.$i.foto_dosya");
            } elseif (! empty($satir['foto_sil'])) {
                $foto = null;
            }
            $ekip[] = ['ad' => $ad, 'unvan' => $temiz($satir['unvan'] ?? ''), 'aciklama' => $temiz($satir['aciklama'] ?? ''), 'foto' => $foto];
        }
        $data['team'] = $ekip;

        foreach (['stats', 'values_list', 'team', 'faqs', 'announcements'] as $alan) {
            if ($data[$alan] === []) {
                $data[$alan] = null;
            }
        }

        return $data;
    }

    /** Boş satırları atar, metinleri kırpar; zorunlu alanı boş olan satır atılır. */
    private function rows($girdi, array $alanlar, array $zorunlu): array
    {
        $out = [];
        foreach ((array) $girdi as $satir) {
            if (! is_array($satir)) {
                continue;
            }
            $r = [];
            foreach ($alanlar as $a) {
                $r[$a] = trim((string) ($satir[$a] ?? ''));
            }
            $eksik = array_filter($zorunlu, fn ($a) => $r[$a] === '');
            if ($eksik) {
                continue;
            }
            $out[] = $r;
        }

        return $out;
    }

    private function store($file, string $alan): string
    {
        try {
            $path = $this->images->store($file, 'facilities');
        } catch (\RuntimeException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([$alan => 'Fotoğraf okunamadı. JPG, PNG veya WEBP yükleyin.']);
        }
        $this->sync->syncStore($path);

        return $path;
    }
}
