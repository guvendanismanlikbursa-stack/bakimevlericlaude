<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $editingPage = $request->filled('edit') ? ContentPage::find($request->query('edit')) : null;

        return view('admin.content-pages.index', compact('pages', 'brands', 'editingPage'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);

        ContentPage::updateOrCreate(
            ['brand' => $data['brand'], 'slug' => $data['slug']],
            $data
        );

        return back()->with('success', 'Sayfa kaydedildi.');
    }

    /**
     * Baslik degisince slug de degisir; bu yuzden guncelleme updateOrCreate
     * ile brand+slug'a gore DEGIL, dogrudan modelin kendi id'siyle yapilir
     * (yoksa baslik degistiginde yeni bir satir olusup eskisi site'de
     * yayinda kalmis "hayalet" sayfa olarak kalirdi).
     */
    public function update(Request $request, ContentPage $contentPage)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);

        $contentPage->update($data);

        return redirect()->route('admin.content-pages.index')->with('success', 'Sayfa güncellendi.');
    }

    public function destroy(ContentPage $contentPage)
    {
        $contentPage->delete();

        return back()->with('success', 'Sayfa silindi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'brand' => ['required', 'string', Rule::in(array_keys(config('brands.brands')))],
            'type' => 'required|in:page,guide',
            'title' => 'required|string|max:180',
            'summary' => 'nullable|string|max:300',
            'body' => 'required|string',
        ]);
    }
}
