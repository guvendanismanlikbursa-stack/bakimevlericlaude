<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::withCount('facilities')->orderBy('name')->get();

        return view('admin.cities.index', compact('cities'));
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
