<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

// 28 Eylul 2026: kullanicinin talebi - pazarlamaci telefonuna/masaustune
// "Ana ekrana ekle" ile kisayol/uygulama olarak ekleyebilsin diye - bkz.
// AdminManifestController AYNI desen, sadece scope/start_url pazarlamaci
// alanina ozel.
class MarketerManifestController extends Controller
{
    public function show()
    {
        $manifest = [
            'name' => 'Pazarlamacı CRM',
            'short_name' => 'CRM',
            'description' => 'Kurum arama ve görüşme takip paneli',
            'start_url' => route('marketer.login'),
            'scope' => url('/pazarlamaci'),
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
