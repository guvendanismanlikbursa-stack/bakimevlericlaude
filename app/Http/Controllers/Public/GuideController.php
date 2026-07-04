<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;

// "Bakim Rehberi": SEO degeri yuksek rehber makaleleri. content_pages
// tablosunu type=guide ile kullanir, tekil makale gosterimi mevcut
// pages.show route'u uzerinden calisir (ayni slug sistemi).
class GuideController extends Controller
{
    public function index()
    {
        $brand = current_brand();
        $guides = ContentPage::guides()->where('brand', $brand['slug'])->latest()->get();

        return view("themes.{$brand['theme']}.guides", compact('brand', 'guides'));
    }
}
