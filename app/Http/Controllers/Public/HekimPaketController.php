<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ExpertWebsite;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * 30 Ekim 2026: kullanıcının talebi - hekim internet sitesi paketleri (Temel /
 * Profesyonel / Prestij). Hedef paket ortadaki Profesyonel'dir. Sayfa demo
 * sitesinden gelinirse (?d=slug&k=demo-anahtarı) her paketin örneği doktorun
 * kendi demo sitesinde açılır. Fiyatlar admin panelinden (Doktor Siteleri)
 * değiştirilir. Satış sayfası olduğu için arama motorlarına kapalıdır.
 */
class HekimPaketController extends Controller
{
    public const PAKETLER = [
        'temel' => ['ad' => 'Temel', 'ikon' => '🩺', 'tasarim' => 'standart', 'varsayilan' => '6.900 ₺ / yıl'],
        'profesyonel' => ['ad' => 'Profesyonel', 'ikon' => '⭐', 'tasarim' => 'klasik', 'varsayilan' => '12.900 ₺ / yıl'],
        'prestij' => ['ad' => 'Prestij', 'ikon' => '💎', 'tasarim' => 'hareketli', 'varsayilan' => '21.900 ₺ / yıl'],
    ];

    public static function fiyat(string $paket): string
    {
        return (string) Setting::get('hekim_paket_fiyat_'.$paket, self::PAKETLER[$paket]['varsayilan']);
    }

    public function show(Request $request)
    {
        // Demo sitesinden gelindiyse paket örnekleri doktorun kendi sitesinde gösterilir.
        $site = null;
        if ($request->filled('d') && $request->filled('k')) {
            $aday = ExpertWebsite::where('slug', (string) $request->query('d'))->first();
            if ($aday && $aday->expert && $aday->demoValid((string) $request->query('k'))) {
                $site = $aday;
            }
        }
        $wa = preg_replace('/\D+/', '', (string) Setting::get('whatsapp_number', config('platform.default_whatsapp.number')));

        return response()->view('themes._shared.hekim-paketleri', [
            'paketler' => collect(self::PAKETLER)->map(fn ($p, $k) => $p + ['fiyat' => self::fiyat($k)])->all(),
            'site' => $site,
            'wa' => $wa,
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
