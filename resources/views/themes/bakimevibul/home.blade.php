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
@section('title', $section['hero_title'].' | '.$brand['name'])
@section('meta_description', $section['hero_subtitle'].' | '.$brand['name'])

<section class="bg-white border-b border-emerald-100">
  <div class="max-w-6xl mx-auto px-4 py-10 lg:py-14">
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

        <div class="grid sm:grid-cols-3 gap-3 mb-7">
          @foreach($sections as $slug => $item)
            @php $active = $item['slug'] === $section['slug']; @endphp
            <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="rounded-lg border p-4 min-h-[118px] transition hover:shadow-lg hover:-translate-y-0.5 {{ $active ? 'text-white shadow-md' : 'bg-white text-gray-800 border-gray-200' }}" style="{{ $active ? 'background: '.$colors['primary'].'; border-color: '.$colors['primary'].';' : '' }}">
              <div class="flex items-center justify-between gap-2">
                <span class="inline-flex rounded-lg p-2 {{ $active ? 'bg-white/15' : 'bg-gray-50' }}" style="{{ ! $active ? 'color:'.$item['theme']['primary'].';' : '' }}">@include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-7 h-7'])</span>
                @if($active)<span class="text-[11px] font-bold rounded bg-white/20 px-2 py-1">Seçili</span>@endif
              </div>
              <div class="font-extrabold mt-3">{{ $item['title'] }}</div>
              <div class="text-xs mt-1 {{ $active ? 'text-white/80' : 'text-gray-500' }}">{{ implode(', ', array_slice($item['features'], 0, 2)) }}</div>
            </a>
          @endforeach
        </div>
      </div>

      <div class="relative">
        <div class="rounded-lg overflow-hidden border border-gray-100 shadow-xl bg-gray-100 aspect-[5/4]">
          <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="w-full h-full object-cover">
        </div>
        <div class="absolute -bottom-5 left-5 right-5 bg-white shadow-xl border border-gray-100 rounded-lg p-4">
          <div class="text-xs font-bold uppercase tracking-wide mb-1" style="color: {{ $colors['primary'] }};">Hızlı arama</div>
          <div class="font-bold text-gray-900">{{ $section['search_label'] }}</div>
        </div>
      </div>
    </div>

    <form method="GET" action="{{ brand_route('facilities.index') }}" data-district-map='@json($districtMap)' data-count-url="{{ brand_route('facilities.count') }}" class="js-location-filter mt-12 bg-gray-50 border border-gray-200 rounded-lg p-4 grid md:grid-cols-6 gap-3">
      <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
      <select name="city" class="js-city border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
      <select name="district" class="js-district border rounded-md px-3 py-2.5 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
      <select name="category" class="border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
      <select name="service" class="border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
      <select name="price_tier" class="border rounded-md px-3 py-2.5 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
      <button class="rounded-md text-white font-bold px-4 py-2.5" style="background: {{ $colors['primary'] }};">Bul</button>
      <div class="js-live-count md:col-span-6 text-xs font-bold text-gray-500 text-center"></div>
    </form>
  </div>
</section>


<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid lg:grid-cols-[0.95fr_1.05fr] gap-6 items-start">
    <div class="bg-white border border-gray-100 rounded-lg p-6 shadow-sm">
      <div class="text-sm font-bold mb-2" style="color: {{ $colors['primary'] }};">{{ $content['audience'] ?? '' }}</div>
      <h2 class="text-2xl font-extrabold text-gray-950 mb-3">{{ $content['headline'] ?? 'Rehber' }}</h2>
      <p class="text-gray-600 leading-relaxed">{{ $content['intro'] ?? '' }}</p>
      <div class="mt-5 grid sm:grid-cols-2 gap-2">
        @foreach(($content['checks'] ?? []) as $check)
          <div class="rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">{{ $check }}</div>
        @endforeach
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      @foreach(($content['articles'] ?? []) as $article)
        <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="bg-white border border-gray-100 rounded-lg p-5 shadow-sm hover:shadow-lg transition">
          <div class="text-xs font-extrabold uppercase tracking-wide mb-2" style="color: {{ $colors['primary'] }};">Bilgilendirme</div>
          <h3 class="font-extrabold text-gray-950 mb-2">{{ $article['title'] }}</h3>
          <p class="text-sm text-gray-500 leading-relaxed">{{ $article['summary'] }}</p>
        </a>
      @endforeach
    </div>
  </div>
</section>
@include('themes._shared.partials.pre-registered-facilities')
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="flex items-end justify-between mb-6">
    <div><div class="text-sm font-bold mb-1" style="color: {{ $colors['primary'] }};">{{ $section['title'] }}</div><h2 class="text-2xl font-extrabold text-gray-950">Öne çıkan kurumlar</h2></div>
    <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-bold" style="color: {{ $colors['primary'] }};">Tümünü gör →</a>
  </div>
  <div class="grid md:grid-cols-3 gap-5">
    @forelse($featured as $facility)
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="bg-white border border-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition group">
        @php $cardImage = facility_card_image($facility, $section); @endphp
        <div class="h-40 flex items-center justify-center overflow-hidden" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
        <div class="p-4"><h3 class="font-extrabold text-gray-950 mb-1">{{ $facility->name }}</h3><p class="text-sm text-gray-500 mb-3">{{ $facility->city->name }} · {{ $facility->category->name }}</p><div class="flex justify-between text-sm"><span class="text-amber-500 font-bold">★ {{ number_format($facility->rating, 1) }}</span><span class="font-bold text-gray-700">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span></div></div>
      </a>
    @empty
      <div class="md:col-span-3 border border-dashed rounded-lg p-8 text-center text-gray-500 bg-white">Bu bölüm için öne çıkan kurum eklenmedi.</div>
    @endforelse
  </div>
</section>

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
@endsection


