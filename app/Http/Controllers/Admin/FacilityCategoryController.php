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

    public function destroy(FacilityCategory $category)
    {
        if ($category->facilities()->exists()) {
            return back()->withErrors(['category' => 'Bu kategoriye bağlı kurumlar var.']);
        }

        $category->delete();

        return back()->with('success', 'Kategori silindi.');
    }
}
