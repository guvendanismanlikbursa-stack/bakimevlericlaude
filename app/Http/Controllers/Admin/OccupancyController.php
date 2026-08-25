<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use Illuminate\Http\Request;

// 25 Agustos 2026: kullanicinin talebi - hangi kurumda bay/bayan icin kac
// bos yer oldugunu takip edebilmek istiyor. Bilerek TUM kurumlar icin
// (sahiplenilmis/on kayitli/aracilik fark etmez) ve bilerek SADECE admin
// panelde - bu bilgi hicbir public sayfada/API'de gosterilmez, kurum
// yetkilileri de goremez, sadece admin (kullanicinin kendisi) gorur/yazar.
class OccupancyController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $cityId = $request->query('city');

        $query = Facility::with('city')->whereNull('deleted_at');

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }
        if ($cityId) {
            $query->where('city_id', $cityId);
        }
        if ($request->boolean('only_tracked')) {
            $query->whereNotNull('vacant_beds_updated_at');
        }

        $facilities = $query->orderByDesc('vacant_beds_updated_at')->orderBy('name')->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        $cities = City::orderBy('name')->get();

        return view('admin.occupancy.index', compact('facilities', 'q', 'cityId', 'cities'));
    }

    public function update(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'vacant_beds_male' => 'nullable|integer|min:0',
            'vacant_beds_female' => 'nullable|integer|min:0',
        ]);

        $facility->update($data + ['vacant_beds_updated_at' => now()]);

        return back()->with('success', "\"{$facility->name}\" doluluk bilgisi güncellendi.");
    }
}
