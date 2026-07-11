<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function show(Request $request)
    {
        $brand = current_brand();

        $manifest = [
            'name' => $brand['name'],
            'short_name' => $brand['logo_text'] ?? $brand['name'],
            'description' => $brand['tagline'] ?? $brand['name'],
            'start_url' => brand_route('home'),
            'scope' => brand_route('home'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $brand['primary_color'],
            'icons' => [
                [
                    'src' => asset('images/logo-'.$brand['slug'].'-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('images/logo-'.$brand['slug'].'-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
    }
}
