<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NearbySearch;
use Illuminate\Http\Request;

// "Yakinimda Ara" tiklamasi yapan HERKESIN (kayitli olsun olmasin) konumunu
// listeler - admin talep yogunlugunun gercekte nerelerden geldigini gorebilsin.
class NearbySearchController extends Controller
{
    public function index(Request $request)
    {
        $brands = config('brands.brands');

        $query = NearbySearch::with('familyUser')->latest();
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $searches = $query->paginate(30)->withQueryString();

        $cityCounts = NearbySearch::query()
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->brand))
            ->whereNotNull('city_name')
            ->selectRaw('city_name, count(*) as total')
            ->groupBy('city_name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        return view('admin.nearby-searches.index', compact('brands', 'searches', 'cityCounts'));
    }
}
