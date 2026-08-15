<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FacilityCategoryController extends Controller
{
    public function index()
    {
        $categories = FacilityCategory::withCount('facilities')->orderBy('name')->get();
        $brands = config('brands.brands');

        return view('admin.categories.index', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'brand_scope' => 'required|string|max:60',
        ]);
        $data['slug'] = Str::slug($data['name']);
        FacilityCategory::create($data);

        return back()->with('success', 'Kategori eklendi.');
    }

    // 16 Temmuz 2026: fiyat segmenti esikleri artik global degil, kurum
    // kategorisi bazinda ayarlanabiliyor (bkz. Facility::priceTier/priceTiers).
    public function updatePriceTiers(Request $request, FacilityCategory $category)
    {
        $data = $request->validate([
            'price_tier_standart_min' => 'required|integer|min:0',
            'price_tier_premium_min' => 'required|integer|gt:price_tier_standart_min',
            'price_tier_ultra_min' => 'required|integer|gt:price_tier_premium_min',
        ], [
            'price_tier_premium_min.gt' => 'Premium eşiği Standart eşiğinden büyük olmalı.',
            'price_tier_ultra_min.gt' => 'Ultra Premium eşiği Premium eşiğinden büyük olmalı.',
        ]);

        $category->update($data);

        return back()->with('success', "{$category->name} segment eşikleri güncellendi.");
    }

    public function destroy(FacilityCategory $category)
    {
        // 15 Agustos 2026: bkz. Admin\CityController::destroy ayni tarihli
        // yorum - ayni risk burada da vardi (facility_category_id FK'si de
        // cascadeOnDelete), cop kutusundaki kurumlar gorulmuyordu.
        if ($category->facilities()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Bu kategoriye bağlı kurumlar var (çöp kutusundakiler dahil).']);
        }

        $category->delete();

        return back()->with('success', 'Kategori silindi.');
    }
}
