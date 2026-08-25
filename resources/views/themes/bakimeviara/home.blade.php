@extends('layouts.brand')
@section('content')
@php
  $section = $activeSection;
  $colors = $section['theme'];
  $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
  $content = site_section_content($brand['slug'], $section['slug']);
  $guideSlug = $section['slug'] . '-rehberi';
  $faqSlug = $section['slug'] . '-soru-cevap';
@endphp
@section('title', $section['hero_title'])
@section('meta_description', $section['hero_subtitle'].' | '.$brand['name'])
@if($section['slug'] !== ($brand['default_section'] ?? null) || ($isFiltering ?? false))
  @section('robots_meta', 'noindex,follow')
@endif

<section class="relative overflow-hidden bg-white">
  <div class="absolute inset-x-0 top-0 h-[420px]" style="background: {{ $colors['soft'] }};"></div>
  <div class="relative max-w-6xl mx-auto px-4 pt-10 pb-12">
    {{-- 12 Agustos 2026: kullanicinin talebi - sira (mobilde de) "bolum
         karti -> filtreleme -> bilgilendirme" olmali. --}}
    <div class="max-w-5xl mx-auto mb-7">
      <div class="grid sm:grid-cols-3 gap-3">
        @foreach($sections as $slug => $item)
          @php $active = $item['slug'] === $section['slug']; @endphp
          <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="group relative section-card-beam rounded-2xl border p-4 min-h-[112px] overflow-hidden transition hover:-translate-y-0.5 hover:shadow-xl {{ $active ? 'text-white shadow-lg' : 'text-white border-gray-100 shadow-sm' }}" style="--beam-color: {{ $active ? '#ffffff' : $item['theme']['secondary'] }}; {{ $active ? 'background:'.$colors['primary'].'; border-color:'.$colors['primary'].';' : '' }}">
            @unless($active)
              <img src="{{ $item['hero_image_card'] ?? $item['hero_image'] }}" alt="" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover">
              <div class="absolute inset-0 bg-gradient-to-t from-black/78 via-black/35 to-black/10"></div>
            @endunless
            <div class="relative flex items-start justify-between gap-3">
              <span class="inline-flex rounded-xl p-2 {{ $active ? 'bg-white/18 text-white' : 'bg-white/15' }}">
                @include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-7 h-7'])
              </span>
              @if($active)<span class="rounded-full bg-white/20 px-2 py-1 text-[11px] font-black">Seçili</span>@endif
            </div>
            <div class="relative font-black mt-3">{{ $item['title'] }}</div>
            <div class="relative text-xs mt-1 {{ $active ? 'text-white/80' : 'text-white/85' }}">{{ implode(', ', array_slice($item['features'], 0, 2)) }}</div>
          </a>
        @endforeach
      </div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto mb-10">
      <form method="GET" action="{{ brand_route('home') }}" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-home-results" class="js-location-filter bg-white rounded-2xl shadow-xl border border-gray-100 p-5 grid sm:grid-cols-2 lg:grid-cols-7 gap-3">
        <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
        {{-- 18 Agustos 2026: bkz. bakimevleri/home.blade.php ayni tarihli
             yorum - form BOS filtrelerle gonderilince "hicbir kurum
             bulunamiyordu" hatasinin duzeltmesi. --}}
        <input type="hidden" name="listele" value="1">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white">
        <select name="city" aria-label="İl" class="js-city border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
        <select name="district" aria-label="İlçe" class="js-district border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
        <select name="category" aria-label="Kurum türü" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
        <select name="service" aria-label="Kurumun özellikleri" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
        <span class="flex items-center gap-1.5">
          <select name="price_tier" aria-label="Fiyat segmenti" class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
          @include('themes._shared.partials.segment-info-icon', ['categories' => $categories, 'id' => 'segment-info-home', 'categorySelectName' => 'category'])
        </span>
        <button class="rounded-xl text-white font-black px-4 py-3" style="background: {{ $colors['primary'] }};">Ara</button>
        @if($isFiltering)
          <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="sm:col-span-2 lg:col-span-7 text-center text-xs font-bold text-gray-400 underline">Filtreleri temizle</a>
        @endif
      </form>
    </div>

    <div class="text-center max-w-3xl mx-auto mb-6">
      <div class="inline-flex items-center gap-2 rounded-full bg-white shadow-sm border border-gray-100 px-4 py-2 text-sm font-bold mb-5" style="color: {{ $colors['primary'] }};">
        @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
        <span>{{ $brand['name'] }} ile doğru kurumu seç</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black leading-tight text-gray-950 mb-4">{{ $section['hero_title'] }}</h1>
      <p class="text-lg text-gray-600 leading-relaxed">{{ $section['hero_subtitle'] }}</p>
      <div class="mt-5 flex flex-wrap justify-center gap-3">
        <a href="{{ brand_route('pages.show', ['slug' => $guideSlug]) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-black shadow-sm border border-gray-100 hover:shadow-md transition" style="color: {{ $colors['primary'] }};">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
          <span>Aile rehberi</span>
        </a>
        <a href="{{ brand_route('pages.show', ['slug' => $faqSlug]) }}" class="inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-black text-white shadow-sm hover:shadow-md transition" style="background: {{ $colors['primary'] }};">
          <span>Soru-cevap</span>
        </a>
      </div>
    </div>

    @include('themes._shared.partials.home-engagement-actions')

    <div class="relative w-full max-w-full rounded-2xl sm:rounded-[28px] overflow-hidden shadow-2xl bg-gray-900 min-h-[260px] sm:min-h-[330px] sm:aspect-[16/7] mt-7">
      <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="absolute inset-0 w-full h-full object-cover">
      <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(0,0,0,.45), rgba(0,0,0,.05) 65%);"></div>
      <div class="absolute left-4 right-4 bottom-5 sm:left-6 sm:right-6 sm:bottom-6 text-white max-w-xl">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/20 backdrop-blur px-3 py-1 text-xs font-bold mb-3">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
          <span>{{ $section['title'] }}</span>
        </div>
        <h2 class="text-xl sm:text-2xl md:text-3xl font-black leading-tight break-words">{{ $section['search_label'] }}</h2>
      </div>
    </div>
  </div>
</section>


@include('themes._shared.partials.trust-stats')

<div id="js-home-results">
  @include('themes.bakimeviara.home._results')
</div>

@include('themes._shared.partials.pre-registered-facilities')

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
@endsection


