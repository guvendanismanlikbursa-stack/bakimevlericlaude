<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;

// 12 Agustos 2026: kullanicinin talebi - "Hakkımızda" sayfasi admin
// panelinden duz metin/markdown olarak girilmisti, genel ContentPage
// sablonu bunu HTML olarak degil DUZ METIN olarak bastigi icin ekranda
// "# Hakkımızda", "**Bakimevleri.com**" gibi ham markdown isaretleri
// goruluyordu - "amator" gorunumun asil sebebi buydu. Bu sayfa artik
// ContentPage'den degil, bu ozel controller + islenmis, gercek verilerle
// (kurum/sehir sayisi) beslenen bir sablon uzerinden geliyor - 3 markada
// da /sayfa/hakkimizda route'u (routes/web.php'de genel {slug} joker
// route'undan ONCE tanimli) buraya yonleniyor.
class AboutController extends Controller
{
    public function show()
    {
        $brand = current_brand();
        $sections = service_sections();

        $facilityCount = Facility::discoverable()->forBrand($brand['category_scope'])->count();
        $claimedCount = Facility::discoverable()->forBrand($brand['category_scope'])->where('is_claimed', true)->count();
        $cityCount = Facility::discoverable()->forBrand($brand['category_scope'])
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->distinct('cities.id')
            ->count('cities.id');
        $totalCities = City::count();

        return view("themes.{$brand['theme']}.about", compact(
            'brand', 'sections', 'facilityCount', 'claimedCount', 'cityCount', 'totalCities'
        ));
    }
}
