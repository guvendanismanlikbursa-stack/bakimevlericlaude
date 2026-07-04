<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// "Turkiye Istatistikleri" + "Haritali dagilim": il il kurum sayilari.
// Gercek bir cografi harita (GeoJSON) yerine, mevcut veriyle dogru ve
// bakimi kolay bir siralanmis dagilim + oransal cubuk gorunumu kullanilir.
class StatsController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;
        $scope = $activeSection ? $activeSection['scopes'] : $brand['category_scope'];

        $rows = Facility::published()
            ->forBrand($scope)
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->select('cities.name as city_name', 'cities.slug as city_slug', DB::raw('count(*) as total'))
            ->groupBy('cities.name', 'cities.slug')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');
        $maxCity = $rows->max('total') ?: 1;
        $citiesWithData = $rows->count();
        $totalCities = City::count();

        // Gercek Turkiye haritasi (public/images/turkiye-harita.svg) icin:
        // haritadaki 81 ilin TAMAMI renklendirilebilsin diye, veri olmayan
        // iller de 0 olarak eklenir (yukaridaki $rows sadece kurumu olan
        // illeri icerir, INNER JOIN oldugu icin).
        $mapCounts = City::orderBy('name')->get()->mapWithKeys(function ($city) use ($rows) {
            $row = $rows->firstWhere('city_slug', $city->slug);
            return [$city->slug => $row->total ?? 0];
        });

        return view("themes.{$brand['theme']}.stats", compact(
            'brand', 'sections', 'activeSection', 'rows', 'grandTotal', 'maxCity', 'citiesWithData', 'totalCities', 'mapCounts'
        ));
    }
}
