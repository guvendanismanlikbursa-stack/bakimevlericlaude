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
        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan denetimde bulundu - facilities.city_id FK'si cascadeOnDelete
        // (bkz. migration), yani bu sehir silinirse MySQL o sehirdeki TUM
        // kurumlari (gorseller/yorumlar/kurum hesaplari dahil, zincirleme
        // cascade ile) fiziksel olarak siler - Eloquent'i hic gormeden.
        // exists() kontrolu Facility::SoftDeletes kullandigi icin COP
        // KUTUSUNDAKI (soft-silinmis, hala geri yuklenebilir) kurumlari
        // GORMUYORDU - bir sehirdeki tum kurumlar cop kutusundaysa "bagli
        // kurum yok" denip sehir silinebiliyordu, bu da cop kutusundaki o
        // kurumlarin GERI DONDURULEMEZ sekilde kaybolmasina yol aciyordu.
        if ($city->facilities()->withTrashed()->exists()) {
            return back()->withErrors(['city' => 'Bu şehre bağlı kurumlar var (çöp kutusundakiler dahil), önce onları taşıyın/kalıcı silin.']);
        }

        $city->delete();

        return back()->with('success', 'Şehir silindi.');
    }
}
