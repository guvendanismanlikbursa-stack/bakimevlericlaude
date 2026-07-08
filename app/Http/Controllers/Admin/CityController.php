<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::withCount('facilities')->orderBy('name')->get();

        $categories = FacilityCategory::orderBy('id')->pluck('name', 'id');

        $counts = Facility::selectRaw('city_id, facility_category_id, count(*) as total')
            ->groupBy('city_id', 'facility_category_id')
            ->get()
            ->groupBy('city_id');

        $categoryBreakdown = $cities->mapWithKeys(function ($city) use ($counts, $categories) {
            $rows = $counts->get($city->id, collect());

            $breakdown = $rows
                ->sortByDesc('total')
                ->mapWithKeys(fn ($row) => [$categories[$row->facility_category_id] ?? 'Diğer' => $row->total])
                ->all();

            return [$city->id => $breakdown];
        });

        return view('admin.cities.index', compact('cities', 'categoryBreakdown'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120|unique:cities,name']);
        $data['slug'] = Str::slug($data['name']);
        City::create($data);

        return back()->with('success', 'Şehir eklendi.');
    }

    public function destroy(City $city)
    {
        if ($city->facilities()->exists()) {
            return back()->withErrors(['city' => 'Bu şehre bağlı kurumlar var, önce onları taşıyın/silin.']);
        }

        $city->delete();

        return back()->with('success', 'Şehir silindi.');
    }
}
