<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\Request;

// "Bakim Rehberi": SEO degeri yuksek rehber makaleleri. content_pages
// tablosunu type=guide ile kullanir, tekil makale gosterimi mevcut
// pages.show route'u uzerinden calisir (ayni slug sistemi).
// 5 Kasım 2026: konu (kategori) filtresi, kapak görselli kartlar, sayfalama; yayın tarihi gelmemiş yazılar gizli.
class GuideController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();
        $kategoriler = config('rehber.kategoriler.'.$brand['slug'], []);
        $kategori = array_key_exists((string) $request->query('kategori'), $kategoriler) ? (string) $request->query('kategori') : null;

        $sorgu = ContentPage::guides()->yayinda()->where('brand', $brand['slug']);
        $sayilar = (clone $sorgu)->whereNotNull('category')->selectRaw('category, count(*) as n')->groupBy('category')->pluck('n', 'category');
        $guides = $sorgu->when($kategori, fn ($q) => $q->where('category', $kategori))
            ->orderByRaw('cover_path is null')->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(12)->withQueryString();

        return view("themes.{$brand['theme']}.guides", compact('brand', 'guides', 'kategoriler', 'kategori', 'sayilar'));
    }
}
