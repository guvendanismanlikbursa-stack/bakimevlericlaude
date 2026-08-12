<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

// 30 Temmuz 2026: admin paneli su ana kadar public sitenin manifest'ini
// (start_url=ana sayfa) paylasiyordu, bu yuzden "Ana ekrana ekle" ile
// kurulan kisayol normal siteye gidiyordu, admin girisine degil - ve
// gercek bir kurulu (installable) PWA/TWA icin gereken ayri "scope" de
// yoktu. Bu, admin paneline OZEL, kendi start_url/scope'una sahip bir
// manifest - TWA (bkz. android/ klasoru, bubblewrap ile derlenen gercek
// .apk) bunu kullanir.
class AdminManifestController extends Controller
{
    public function show()
    {
        $manifest = [
            'name' => 'Bakım Admin Panel',
            'short_name' => 'Admin Panel',
            'description' => '3 site için ortak yönetim paneli',
            'start_url' => route('admin.login'),
            'scope' => url('/admin'),
            'display' => 'standalone',
            'background_color' => '#111827',
            'theme_color' => '#111827',
            'icons' => [
                [
                    'src' => asset('images/logo-bakimevleri-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('images/logo-bakimevleri-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
    }
}
