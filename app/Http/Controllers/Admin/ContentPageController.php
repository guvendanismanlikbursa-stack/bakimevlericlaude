<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentPageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentPage::query();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $pages = $query->orderBy('brand')->orderBy('title')->get();
        $brands = config('brands.brands');

        return view('admin.content-pages.index', compact('pages', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => 'required|string',
            'type' => 'required|in:page,guide',
            'title' => 'required|string|max:180',
            'summary' => 'nullable|string|max:300',
            'body' => 'required|string',
        ]);
        $data['slug'] = Str::slug($data['title']);

        ContentPage::updateOrCreate(
            ['brand' => $data['brand'], 'slug' => $data['slug']],
            $data
        );

        return back()->with('success', 'Sayfa kaydedildi.');
    }

    public function destroy(ContentPage $contentPage)
    {
        $contentPage->delete();

        return back()->with('success', 'Sayfa silindi.');
    }
}
