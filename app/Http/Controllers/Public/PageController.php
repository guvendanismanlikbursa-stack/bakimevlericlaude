<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;

class PageController extends Controller
{
    public function show(string $slug, ?string $prefixedSlug = null)
    {
        if ($prefixedSlug !== null) {
            $slug = $prefixedSlug;
        }

        $brand = current_brand();

        $page = ContentPage::where('brand', $brand['slug'])
            ->where('slug', $slug)
            ->first();

        // 5 Kasım 2026: yayın tarihi gelmemiş rehber yazısı henüz yayında değildir.
        abort_if($page && $page->type === 'guide' && ! $page->yayindaMi(), 404);
        $ilgili = collect();
        if ($page && $page->type === 'guide' && $page->category) {
            $ilgili = ContentPage::guides()->yayinda()->where('brand', $brand['slug'])->where('category', $page->category)
                ->where('id', '!=', $page->id)->orderByRaw('cover_path is null')->orderByRaw('abs(cast(id as signed) - ?)', [$page->id])->limit(4)->get();
        }

        if (! $page) {
            $fallback = site_content_page($brand['slug'], $slug);
            abort_if(! $fallback, 404);

            $page = (object) [
                'brand' => $brand['slug'],
                'slug' => $slug,
                'title' => $fallback['title'],
                'body' => $fallback['body'],
                'is_fallback' => true,
            ];
        }

        return view("themes.{$brand['theme']}.page", compact('page', 'ilgili'));
    }
}