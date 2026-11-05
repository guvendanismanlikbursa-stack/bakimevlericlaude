@extends('layouts.brand')
@php
  $kategoriAdi = $kategori ? ($kategoriler[$kategori] ?? null) : null;
  $sayfaNo = $guides->currentPage();
@endphp
@section('title', ($kategoriAdi ? $kategoriAdi.' Yazıları' : 'Bakım Rehberi - Karar Vermenize Yardımcı Yazılar').($sayfaNo > 1 ? ' - Sayfa '.$sayfaNo : ''))
@section('meta_description', $kategoriAdi
  ? current_brand()['name'].' bakım rehberinde '.$kategoriAdi.' konusundaki yazılar: ailelerin karar vermesine yardımcı pratik bilgiler ve kontrol listeleri.'
  : current_brand()['name'].' ile bakım kararlarınıza yardımcı olabilecek rehber makaleleri, sık sorulan soruları ve kontrol listelerini keşfedin.')
@section('content')
{{-- 5 Kasım 2026: konu sekmeleri, kapak görselli kartlar ve sayfalama (her siteye 100 rehber yazısı). --}}
<div class="max-w-5xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-2">{{ $kategoriAdi ?? 'Bakım Rehberi' }}</h1>
  <p class="text-sm text-gray-500 mb-6">Karar verirken işinize yarayacak yazılar.</p>

  @if($kategoriler)
    <nav style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;" aria-label="Rehber konuları">
      <a href="{{ brand_route('guides.index') }}" style="padding:6px 12px;border-radius:999px;font-size:13px;font-weight:800;text-decoration:none;{{ $kategori ? 'background:#fff;color:#374151;border:1px solid #e5e7eb;' : 'background:var(--primary);color:#fff;border:1px solid transparent;' }}">Tümü</a>
      @foreach($kategoriler as $k => $ad)
        @if(($sayilar[$k] ?? 0) > 0)
          <a href="{{ brand_route('guides.index', ['kategori' => $k]) }}" style="padding:6px 12px;border-radius:999px;font-size:13px;font-weight:800;text-decoration:none;{{ $kategori === $k ? 'background:var(--primary);color:#fff;border:1px solid transparent;' : 'background:#fff;color:#374151;border:1px solid #e5e7eb;' }}">{{ $ad }} <span style="opacity:.7;">({{ $sayilar[$k] }})</span></a>
        @endif
      @endforeach
    </nav>
  @endif

  @if($guides->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Henüz rehber makalesi eklenmedi.</p>
    </div>
  @else
    <div class="grid md:grid-cols-2 gap-5">
      @foreach($guides as $guide)
        <a href="{{ brand_route('pages.show', ['slug' => $guide->slug]) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition block" style="overflow:hidden;">
          @if($guide->cover_path)
            <img src="{{ $guide->kapakUrl() }}" alt="{{ $guide->title }}" loading="lazy" width="960" height="540" style="display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#f3f4f6;">
          @endif
          <div class="p-5">
            @if($guide->kategoriAdi())<div style="font-size:12px;font-weight:800;color:#6b7280;margin-bottom:4px;">{{ $guide->kategoriAdi() }}</div>@endif
            <h2 class="font-black text-lg text-gray-950 mb-2">{{ $guide->title }}</h2>
            @if($guide->summary)<p class="text-sm text-gray-600 line-clamp-3">{{ $guide->summary }}</p>@endif
            <span class="text-xs text-primary font-black mt-3 inline-block">Devamını oku →</span>
          </div>
        </a>
      @endforeach
    </div>
    @if($guides->hasPages())
      <div class="mt-8">{{ $guides->links() }}</div>
    @endif
  @endif
</div>
@endsection
