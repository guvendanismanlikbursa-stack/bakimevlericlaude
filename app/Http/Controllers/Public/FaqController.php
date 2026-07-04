<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index()
    {
        $brand = current_brand();

        // SSS icerigi nadiren degisir, her sayfa yuklemesinde sorgu atmamak icin
        // 1 saat cache'lenir; admin panelden ekleme/guncelleme/silmede temizlenir.
        $faqs = Cache::remember("faqs:{$brand['slug']}", 3600, function () use ($brand) {
            return Faq::active()->forBrand($brand['slug'])->orderBy('sort_order')->get();
        });

        return view("themes.{$brand['theme']}.faq", compact('brand', 'faqs'));
    }
}
