<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WhatsappClick;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

// Yuzen WhatsApp butonuna tiklama kaydi. Tarayici WhatsApp sohbetini kendi
// acar (wa.me linki), bu uc nokta sadece admin panelinde goruntulenecek
// kaydi tutar — WhatsApp'in kendisine otomatik mesaj gitmesi ayri (ucretli)
// bir WhatsApp Business API entegrasyonu gerektirir, bu kapsamda degildir.
class WhatsappController extends Controller
{
    public function track(Request $request, GeoLookupService $geo)
    {
        $data = $request->validate([
            'page_url' => 'nullable|string|max:500',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $cityName = null;
        if (isset($data['lat'], $data['lng'])) {
            $cityName = $geo->nearestCity($data['lat'], $data['lng'])['city'] ?? null;
        }

        WhatsappClick::create([
            'brand' => current_brand()['slug'],
            'page_url' => $data['page_url'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'city_name' => $cityName,
            'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}
