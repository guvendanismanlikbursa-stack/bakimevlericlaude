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

<section class="relative overflow-hidden bg-white">
  <div class="absolute inset-x-0 top-0 h-[420px]" style="background: {{ $colors['soft'] }};"></div>
  <div class="relative max-w-6xl mx-auto px-4 pt-10 pb-12">
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

    <div class="max-w-5xl mx-auto mb-7">
      <div class="text-center text-sm font-black mb-3" style="color: {{ $colors['primary'] }};">Bölüm seçin</div>
      <div class="grid sm:grid-cols-3 gap-3">
        @foreach($sections as $slug => $item)
          @php $active = $item['slug'] === $section['slug']; @endphp
          <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="group section-card-beam rounded-2xl border p-4 min-h-[112px] transition hover:-translate-y-0.5 hover:shadow-xl {{ $active ? 'text-white shadow-lg' : 'bg-white text-gray-800 border-gray-100 shadow-sm' }}" style="--beam-color: {{ $active ? '#ffffff' : $item['theme']['primary'] }}; {{ $active ? 'background:'.$colors['primary'].'; border-color:'.$colors['primary'].';' : '' }}">
            <div class="flex items-start justify-between gap-3">
              <span class="inline-flex rounded-xl p-2 {{ $active ? 'bg-white/18 text-white' : 'bg-gray-50' }}" style="{{ ! $active ? 'color:'.$item['theme']['primary'].';' : '' }}">
                @include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-7 h-7'])
              </span>
              @if($active)<span class="rounded-full bg-white/20 px-2 py-1 text-[11px] font-black">Seçili</span>@endif
            </div>
            <div class="font-black mt-3">{{ $item['title'] }}</div>
            <div class="text-xs mt-1 {{ $active ? 'text-white/80' : 'text-gray-500' }}">{{ implode(', ', array_slice($item['features'], 0, 2)) }}</div>
          </a>
        @endforeach
      </div>
    </div>

    <div class="relative w-full max-w-full rounded-2xl sm:rounded-[28px] overflow-hidden shadow-2xl bg-gray-900 min-h-[260px] sm:min-h-[330px] sm:aspect-[16/7]">
      <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="absolute inset-0 w-full h-full object-cover opacity-90">
      <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(0,0,0,.62), rgba(0,0,0,.10) 65%);"></div>
      <div class="absolute left-4 right-4 bottom-5 sm:left-6 sm:right-6 sm:bottom-6 text-white max-w-xl">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/20 backdrop-blur px-3 py-1 text-xs font-bold mb-3">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
          <span>{{ $section['title'] }}</span>
        </div>
        <h2 class="text-xl sm:text-2xl md:text-3xl font-black leading-tight break-words">{{ $section['search_label'] }}</h2>
      </div>
    </div>

    <div class="-mt-8 relative z-10 max-w-5xl mx-auto">
      <form method="GET" action="{{ brand_route('facilities.index') }}" data-district-map='@json($districtMap)' data-count-url="{{ brand_route('facilities.count') }}" class="js-location-filter bg-white rounded-2xl shadow-xl border border-gray-100 p-5 grid sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
        <select name="city" class="js-city border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
        <select name="district" class="js-district border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
        <select name="category" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
        <select name="service" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
        <select name="price_tier" class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <button class="rounded-xl text-white font-black px-4 py-3" style="background: {{ $colors['primary'] }};">Ara</button>
        <div class="js-live-count sm:col-span-2 lg:col-span-6 text-xs font-bold text-gray-500 text-center"></div>
      </form>
    </div>
  </div>
</section>


<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="text-center max-w-3xl mx-auto mb-8">
    <div class="text-sm font-black mb-2" style="color: {{ $colors['primary'] }};">{{ $content['audience'] ?? '' }}</div>
    <h2 class="text-3xl font-black text-gray-950 mb-3">{{ $content['headline'] ?? 'Karar rehberi' }}</h2>
    <p class="text-gray-600 leading-relaxed">{{ $content['intro'] ?? '' }}</p>
  </div>
  <div class="grid lg:grid-cols-3 gap-4 mb-6">
    @foreach(($content['faq_preview'] ?? []) as $qa)
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="font-black text-gray-950 mb-2">{{ $qa[0] }}</div>
        <p class="text-sm text-gray-500 leading-relaxed">{{ $qa[1] }}</p>
      </div>
    @endforeach
  </div>
  <div class="flex flex-wrap justify-center gap-3">
    @foreach(($content['articles'] ?? []) as $article)
      <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-black hover:shadow-md transition" style="color: {{ $colors['primary'] }};">{{ $article['title'] }}</a>
    @endforeach
  </div>
</section>
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="text-center max-w-2xl mx-auto mb-8">
    <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">{{ $section['title'] }}</div>
    <h2 class="text-3xl font-black text-gray-950">Ailelerin incelediği kurumlar</h2>
  </div>
  <div class="grid md:grid-cols-3 gap-6">
    @forelse($featured as $facility)
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition">
        @php $cardImage = facility_card_image($facility, $section); @endphp
        <div class="h-48 overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
        <div class="p-5"><div class="text-xs font-bold mb-2" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</div><h3 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h3><p class="text-sm text-gray-500 mb-4">{{ $facility->city->name }}</p><div class="flex items-center justify-between text-sm"><span class="text-amber-500 font-black">★ {{ number_format($facility->rating, 1) }}</span><span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Teklif al' }}</span></div></div>
      </a>
    @empty
      <div class="md:col-span-3 bg-white border border-dashed rounded-2xl p-8 text-center text-gray-500">Bu bölüm için öne çıkan kurum eklenmedi.</div>
    @endforelse
  </div>
</section>

@include('themes._shared.partials.pre-registered-facilities')

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
@endsection


