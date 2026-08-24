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

<section class="bg-white border-b border-emerald-100">
  <div class="max-w-6xl mx-auto px-4 py-10 lg:py-14">
    {{-- 12 Agustos 2026: kullanicinin talebi - bolum secimi her seyden
         once, en usta; mobilde de "bolum -> filtre -> bilgilendirme"
         sirasi korunmali. --}}
    <div class="grid sm:grid-cols-3 gap-3 mb-8">
      @foreach($sections as $slug => $item)
        @php $active = $item['slug'] === $section['slug']; @endphp
        <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="relative section-card-beam rounded-lg border p-4 min-h-[118px] overflow-hidden transition hover:shadow-lg hover:-translate-y-0.5 {{ $active ? 'text-white shadow-md' : 'text-white border-gray-200' }}" style="--beam-color: {{ $active ? '#ffffff' : $item['theme']['secondary'] }}; {{ $active ? 'background: '.$colors['primary'].'; border-color: '.$colors['primary'].';' : '' }}">
          @unless($active)
            <img src="{{ $item['hero_image_card'] ?? $item['hero_image'] }}" alt="" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/78 via-black/35 to-black/10"></div>
          @endunless
          <div class="relative flex items-center justify-between gap-2">
            <span class="inline-flex rounded-lg p-2 {{ $active ? 'bg-white/15' : 'bg-white/15' }}">@include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-7 h-7'])</span>
            @if($active)<span class="text-[11px] font-bold rounded bg-white/20 px-2 py-1">Seçili</span>@endif
          </div>
          <div class="relative font-extrabold mt-3">{{ $item['title'] }}</div>
          <div class="relative text-xs mt-1 {{ $active ? 'text-white/80' : 'text-white/85' }}">{{ implode(', ', array_slice($item['features'], 0, 2)) }}</div>
        </a>
      @endforeach
    </div>

    {{-- 12 Agustos 2026: form (GERCEK filtre) bilerek 3 bolum kartindan
         HEMEN SONRA - onceki halde buraya sadece dekoratif "hero gorsel
         kutusu" tasinmisti, GERCEK form hala en altta kalmisti; kullanici
         "bakimevleri gibi olmali" dedi, sira mobilde/masaustunde ayni:
         bolum -> filtre -> bilgilendirme (baslik+gorsel). --}}
    <form id="js-quick-search" method="GET" action="{{ brand_route('home') }}" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-home-results" class="js-location-filter mb-10 bg-gray-50 border border-gray-200 rounded-lg p-4 grid md:grid-cols-7 gap-3 scroll-mt-24">
      <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
      {{-- 18 Agustos 2026: bkz. bakimevleri/home.blade.php ayni tarihli
           yorum - form BOS filtrelerle gonderilince "hicbir kurum
           bulunamiyordu" hatasinin duzeltmesi. --}}
      <input type="hidden" name="listele" value="1">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara" class="border rounded-md px-3 py-2.5 text-sm bg-white">
      <select name="city" aria-label="İl" class="js-city border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
      <select name="district" aria-label="İlçe" class="js-district border rounded-md px-3 py-2.5 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
      <select name="category" aria-label="Kurum türü" class="border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
      <select name="service" aria-label="Kurumun özellikleri" class="border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
      <span class="flex items-center gap-1.5">
        <select name="price_tier" aria-label="Fiyat segmenti" class="flex-1 border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        @include('themes._shared.partials.segment-info-icon', ['categories' => $categories, 'id' => 'segment-info-home', 'categorySelectName' => 'category'])
      </span>
      <button class="rounded-md text-white font-bold px-4 py-2.5" style="background: {{ $colors['primary'] }};">Bul</button>
      @if($isFiltering)
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="md:col-span-7 text-center text-xs font-bold text-gray-400 underline">Filtreleri temizle</a>
      @endif
    </form>

    <div class="grid lg:grid-cols-[1.04fr_0.96fr] gap-8 items-center">
      <div>
        <div class="inline-flex items-center gap-2 text-sm font-bold rounded-lg px-3 py-2 mb-5" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
          <span>{{ $brand['name'] }} kurum bulma rehberi</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight text-gray-950 mb-4">{{ $section['hero_title'] }}</h1>
        <p class="text-lg text-gray-600 leading-relaxed max-w-2xl mb-5">{{ $section['hero_subtitle'] }}</p>
        <div class="flex flex-wrap gap-3 mb-7">
          <a href="{{ brand_route('pages.show', ['slug' => $guideSlug]) }}" class="inline-flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-extrabold text-white shadow-sm hover:shadow-md transition" style="background: {{ $colors['primary'] }};">
            @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
            <span>Rehberi oku</span>
          </a>
          <a href="{{ brand_route('pages.show', ['slug' => $faqSlug]) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-extrabold text-gray-900 hover:shadow-md transition">
            <span>Soru-cevap</span>
          </a>
        </div>

        @include('themes._shared.partials.home-engagement-actions')
      </div>

      <div class="relative">
        <div class="rounded-lg overflow-hidden border border-gray-100 shadow-xl bg-gray-100 aspect-[5/4]">
          <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="w-full h-full object-cover">
        </div>
        {{-- 12 Agustos 2026: kullanicinin talebi - bu kart salt dekoratifti,
             tiklanamiyordu, kullanici "hizli arama filtresi yok" diye
             kafasi karisti. Artik yukaridaki GERCEK filtre formuna
             tiklanabilir/kaydiran bir baglanti. --}}
        <a href="#js-quick-search" class="absolute -bottom-5 left-5 right-5 bg-white shadow-xl border border-gray-100 rounded-lg p-4 flex items-center justify-between gap-3 hover:shadow-2xl transition group">
          <span>
            <span class="block text-xs font-bold uppercase tracking-wide mb-1" style="color: {{ $colors['primary'] }};">Hızlı arama</span>
            <span class="block font-bold text-gray-900">{{ $section['search_label'] }}</span>
          </span>
          <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white group-hover:-translate-y-0.5 transition" style="background: {{ $colors['primary'] }};">↑</span>
        </a>
      </div>
    </div>
  </div>
</section>


@include('themes._shared.partials.trust-stats')

<div id="js-home-results">
  @include('themes.bakimevibul.home._results')
</div>

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
@endsection


