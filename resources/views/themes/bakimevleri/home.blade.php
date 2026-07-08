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

<section class="relative bg-gray-950 text-white overflow-hidden">
  {{-- Foto&#287;raf gorunur/canli kalsin diye opaklik yuksek tutuluyor; sadece
       sag taraftaki (filtre kart&#305;n&#305;n oturdugu) serit, karta sert bir
       kesim gibi carpmamak icin bir mask-image ile yumusakca saydamlasiyor. --}}
  <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="absolute inset-0 w-full h-full object-cover opacity-55" style="mask-image: linear-gradient(to right, black 0%, black 58%, transparent 82%); -webkit-mask-image: linear-gradient(to right, black 0%, black 58%, transparent 82%);">
  <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/65 to-transparent"></div>
  <div class="relative max-w-6xl mx-auto px-4 py-12 lg:py-16">
    <div class="grid lg:grid-cols-[1fr_360px] gap-8 items-start">
      <div class="pt-4">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold mb-6">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
          <span>{{ $brand['name'] }} uzman rehberi</span>
        </div>
        <h1 class="max-w-3xl text-4xl md:text-6xl font-black leading-tight mb-5">{{ $section['hero_title'] }}</h1>
        <p class="max-w-2xl text-lg text-white/78 leading-relaxed mb-5">{{ $section['hero_subtitle'] }}</p>
        <div class="flex flex-wrap gap-3 mb-8">
          <a href="{{ brand_route('pages.show', ['slug' => $guideSlug]) }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-black text-gray-950 hover:bg-gray-100 transition">
            @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
            <span>Bilgi rehberi</span>
          </a>
          <a href="{{ brand_route('pages.show', ['slug' => $faqSlug]) }}" class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-3 text-sm font-black text-white hover:bg-white/16 transition">
            <span>Uzman cevapları</span>
          </a>
        </div>
        @include('themes._shared.partials.home-engagement-actions')

        <div class="grid sm:grid-cols-3 gap-3 max-w-3xl">
          @foreach($sections as $slug => $item)
            @php $active = $item['slug'] === $section['slug']; @endphp
            <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="border rounded-xl p-4 transition {{ $active ? 'bg-white text-gray-950 border-white' : 'bg-white/8 text-white border-white/14 hover:bg-white/14' }}">
              <div class="flex items-center gap-3">
                <span class="inline-flex rounded-lg p-2 {{ $active ? 'bg-gray-100' : 'bg-white/10' }}" style="{{ $active ? 'color:'.$colors['primary'].';' : '' }}">@include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-6 h-6'])</span>
                <span class="font-black">{{ $item['title'] }}</span>
              </div>
              <div class="text-xs mt-3 {{ $active ? 'text-gray-500' : 'text-white/62' }}">{{ implode(' · ', array_slice($item['features'], 0, 2)) }}</div>
            </a>
          @endforeach
        </div>
      </div>

      <form method="GET" action="{{ brand_route('facilities.index') }}" data-district-map='@json($districtMap)' data-count-url="{{ brand_route('facilities.count') }}" class="js-location-filter bg-white text-gray-900 rounded-xl shadow-2xl p-5 border border-white/20">
        <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
        <div class="flex items-center gap-2 mb-4" style="color: {{ $colors['primary'] }};">@include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-5 h-5'])<span class="font-black">Detaylı filtre</span></div>
        <div class="space-y-3">
          <select name="city" class="js-city w-full border border-gray-200 rounded-lg px-3 py-3 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
          <select name="district" class="js-district w-full border border-gray-200 rounded-lg px-3 py-3 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
          <select name="category" class="w-full border border-gray-200 rounded-lg px-3 py-3 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
          <select name="service" class="w-full border border-gray-200 rounded-lg px-3 py-3 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
          <select name="price_tier" class="w-full border border-gray-200 rounded-lg px-3 py-3 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
          <button class="w-full rounded-lg text-white font-black px-4 py-3" style="background: {{ $colors['primary'] }};">Kurumları listele</button>
          <div class="js-live-count text-xs font-bold text-gray-500 text-center"></div>
        </div>
      </form>
    </div>
  </div>
</section>


<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid lg:grid-cols-[320px_1fr] gap-6">
    <div class="bg-gray-950 text-white rounded-xl p-6 h-fit">
      <div class="text-sm font-black text-white/70 mb-2">Bilgi merkezi</div>
      <h2 class="text-2xl font-black mb-3">{{ $content['headline'] ?? 'Kapsamlı rehber' }}</h2>
      <p class="text-sm text-white/72 leading-relaxed">{{ $content['intro'] ?? '' }}</p>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      @foreach(($content['articles'] ?? []) as $article)
        <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm hover:shadow-xl transition">
          <div class="text-xs font-black uppercase tracking-wide mb-2" style="color: {{ $colors['primary'] }};">Makale ve SSS</div>
          <h3 class="font-black text-gray-950 mb-2">{{ $article['title'] }}</h3>
          <p class="text-sm text-gray-500 leading-relaxed">{{ $article['summary'] }}</p>
        </a>
      @endforeach
      <div class="md:col-span-2 bg-white border border-gray-100 rounded-xl p-5">
        <div class="font-black text-gray-950 mb-3">Hızlı soru cevap</div>
        <div class="grid md:grid-cols-3 gap-3">
          @foreach(($content['faq_preview'] ?? []) as $qa)
            <div class="rounded-lg bg-gray-50 p-3"><div class="text-sm font-black text-gray-900">{{ $qa[0] }}</div><p class="text-xs text-gray-500 mt-1">{{ $qa[1] }}</p></div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid lg:grid-cols-[280px_1fr] gap-8">
    <aside class="bg-white border border-gray-100 rounded-xl p-5 h-fit shadow-sm">
      <div class="font-black text-gray-950 mb-3">{{ $section['title'] }} özellikleri</div>
      <div class="space-y-2">
        @foreach(array_slice($section['features'], 0, 6) as $feature)
          <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'service' => $feature]) }}" class="block rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700 hover:shadow-sm">{{ $feature }}</a>
        @endforeach
      </div>
    </aside>
    <div>
      <div class="flex items-end justify-between mb-6"><div><div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Seçilmiş kurumlar</div><h2 class="text-3xl font-black text-gray-950">Öne çıkanlar</h2></div><a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Listeye git →</a></div>
      <div class="grid md:grid-cols-2 gap-5">
        @forelse($featured as $facility)
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="group bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition grid sm:grid-cols-[150px_1fr]">
            @php $cardImage = facility_card_image($facility, $section); @endphp
            <div class="h-44 sm:h-full overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
            <div class="p-4"><div class="text-xs font-black mb-2" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</div><h3 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h3><p class="text-sm text-gray-500 mb-4">{{ $facility->city->name }}</p><div class="flex items-center justify-between text-sm"><span class="text-amber-500 font-black">★ {{ number_format($facility->rating, 1) }}</span><span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span></div></div>
          </a>
        @empty
          <div class="md:col-span-2 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölüm için öne çıkan kurum eklenmedi.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>

@include('themes._shared.partials.pre-registered-facilities')

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
@endsection


