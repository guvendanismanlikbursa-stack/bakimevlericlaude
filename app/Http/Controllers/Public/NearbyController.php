<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// "Yakinimdaki Kurumlar": once tarayici konumunu en yakin il merkezine
// eslestirip o ilin kurum listesine yonlendirir (koordinati olmayan
// kurumlar icin il-bazli yaklasiklik). Ayrica, veri cekici/admin formundan
// gercek lat/lng girilmis kurumlar varsa bunlarin gercek mesafesi de
// hesaplanip redirect URL'ine eklenir; hedef sayfa (facilities.index) bu
// durumda "gercek yakinimdaki kurumlar" seridini gosterir.
class NearbyController extends Controller
{
    public function locate(Request $request, GeoLookupService $geo)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $nearest = $geo->nearestCity($data['lat'], $data['lng']);

        if (! $nearest) {
            return response()->json(['ok' => false], 404);
        }

        $city = City::where('slug', Str::slug($nearest['city']))->first();

        if (! $city) {
            return response()->json(['ok' => false], 404);
        }

        $brand = current_brand();
        $hasNearbyFacilities = Facility::published()
            ->forBrand($brand['category_scope'])
            ->whereNotNull('lat')->whereNotNull('lng')
            ->exists();

        $redirectParams = ['city' => $city->slug];
        if ($hasNearbyFacilities) {
            $redirectParams['lat'] = $data['lat'];
            $redirectParams['lng'] = $data['lng'];
        }

        return response()->json([
            'ok' => true,
            'city_slug' => $city->slug,
            'city_name' => $city->name,
            'distance_km' => round($nearest['distance_km']),
            'redirect_url' => brand_route('facilities.index', $redirectParams),
        ]);
    }
}
