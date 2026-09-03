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
  {{-- 14 Agustos 2026: kullanicinin talebi uzerine yapilan SEO denetiminde
       bulundu - anasayfadaki filtre sonuclari (il/kategori secilince ayni
       sayfada gorunen sonuclar) indexleniyordu ama basligi/aciklamasi
       filtreyi hic yansitmiyordu (hep ayni jenerik anasayfa metni) - bu da
       il x kategori kombinasyonu kadar cogalabilen "duplicate title" riski
       yaratiyordu. Aynı filtreli sonuc zaten /kurumlar sayfasinda dogru
       basiliga sahip olarak indexleniyor, burada noindex yeterli. --}}
  @section('robots_meta', 'noindex,follow')
@endif

<section class="bg-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-10">
    {{-- 12 Agustos 2026: kullanicinin acik talebi - bolum secimi (Yasli
         Bakim/Cocuk/Rehabilitasyon) EN USTE, her seyden once geliyor;
         hangi bolumle ilgilenildigini secmek site kullanimindaki ILK
         adim olmali. --}}
    <div class="grid sm:grid-cols-3 gap-3 mb-6">
      @foreach($sections as $slug => $item)
        @php $active = $item['slug'] === $section['slug']; @endphp
        <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="section-card-beam relative border rounded-xl p-4 overflow-hidden transition {{ $active ? 'bg-white text-gray-950 border-white' : 'text-white border-white/14 hover:brightness-110' }}" style="--beam-color: {{ $active ? $item['theme']['primary'] : $item['theme']['secondary'] }};">
          @unless($active)
            {{-- Karti tanimlayan gorsel: onceden kart tumuyle koyu/yari
                 saydamdi, arka plandaki fotograf neredeyse hic gorunmuyordu.
                 Overlay'i (gradient) hafifletip gorseli belirginlestirdik,
                 metin okunurlugu icin sadece alt kismi koyulastiriyoruz. --}}
            {{-- 14 Agustos 2026: Lighthouse denetiminde bu kart gorseli LCP
                 (en buyuk icerik boyamasi) elementi cikti ama fetchpriority
                 belirtilmemisti - tarayiciya bu gorseli oncelikli getirmesini
                 soyleyerek LCP suresini kisaltir. --}}
            <img src="{{ $item['hero_image_card'] ?? $item['hero_image'] }}" alt="" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/78 via-black/35 to-black/10"></div>
          @endunless
          <div class="relative flex items-center justify-between gap-2">
            <span class="inline-flex rounded-lg p-2 {{ $active ? 'bg-gray-100' : 'bg-white/15' }}" style="{{ $active ? 'color:'.$colors['primary'].';' : '' }}">@include('themes._shared.partials.section-icon', ['section' => $item, 'class' => 'w-6 h-6'])</span>
            @if($active)<span class="text-[11px] font-bold rounded bg-gray-100 px-2 py-1" style="color: {{ $colors['primary'] }};">Seçili</span>@endif
          </div>
          <div class="relative font-black mt-3">{{ $item['title'] }}</div>
          <div class="relative text-xs mt-1 {{ $active ? 'text-gray-500' : 'text-white/85' }}">{{ implode(' · ', array_slice($item['features'], 0, 2)) }}</div>
        </a>
      @endforeach
    </div>

    {{-- 12 Agustos 2026: kullanicinin talebi - metin kutusunda her harfte,
         secimlerde degisince, sayfa yenilenmeden asagidaki ana sonuc
         alaninda (buyuk kurum kartlari + sayfalama) sonuclar ANINDA
         guncellenir - bkz. location-filter-script.blade.php. --}}
    <form method="GET" action="{{ brand_route('home') }}" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-home-results" class="js-location-filter bg-white text-gray-900 rounded-xl shadow-sm p-5 border border-gray-100 grid sm:grid-cols-2 lg:grid-cols-6 gap-3">
      <input type="hidden" name="bolum" value="{{ $section['slug'] }}">
      {{-- 18 Agustos 2026: kullanicinin bildirdigi gercek hata - hicbir
           filtre secmeden "Kurumları listele"ye basinca "hicbir kurum
           bulunamiyordu": form BOS filtrelerle gonderilince hasActiveFilters()
           false donuyor, sayfa listeleme yerine degismeden ayni tanitim
           (Bilgi merkezi/Öne çıkanlar) blogunu gosteriyordu - kullanicinin
           GORDUGU sey "hicbir sey olmadi/liste yok" oluyordu. Bu gizli alan
           SADECE bu form GERCEKTEN gonderildiginde (buton tiklandiginda)
           var olur; hasActiveFilters() artik bunu da sayiyor, boylece
           "Kurumları listele" HER ZAMAN gercek bir liste gosterir - digerleri
           bos birakilmis olsa bile. --}}
      <input type="hidden" name="listele" value="1">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white">
      <select name="city" aria-label="İl" class="js-city border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
      <select name="district" aria-label="İlçe" class="js-district border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
      <select name="category" aria-label="Kurum türü" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach</select>
      <select name="service" aria-label="Kurumun özellikleri" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select>
      <div class="flex items-center gap-1.5">
        <select name="price_tier" aria-label="Fiyat segmenti" class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        @include('themes._shared.partials.segment-info-icon', ['categories' => $categories, 'id' => 'segment-info-home', 'categorySelectName' => 'category'])
      </div>
      <button class="lg:col-span-6 rounded-lg text-white font-black px-4 py-2.5" style="background: {{ $colors['primary'] }};">Kurumları listele</button>
      @if($isFiltering)
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="lg:col-span-6 block text-center text-xs font-bold text-gray-400 underline">Filtreleri temizle</a>
      @endif
    </form>
  </div>
</section>

{{-- 28 Agustos 2026: kullanicinin talebi - "Öne çıkanlar"/"Ön Kayıtlı
     Kurumlar"/filtreleme sonucu bolumu artik filtrenin HEMEN ALTINDA;
     daha once burada duran "atmosfer" (baslik+gorsel+hizli aksiyon
     kartlari) bolumu ve istatistik seridi bununla YER DEGISTIRDI, simdi
     sonuclarin altina tasindi. --}}
<div id="js-home-results">
  @include('themes.bakimevleri.home._results')
</div>

<section class="relative bg-gray-950 text-white overflow-hidden">
  <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="absolute inset-0 w-full h-full object-cover opacity-90">
  <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-black/5"></div>
  <div class="relative max-w-6xl mx-auto px-4 py-12 lg:py-16">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold mb-6">
      @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
      <span>{{ $brand['name'] }} uzman rehberi</span>
    </div>
    <h1 class="max-w-3xl text-4xl md:text-6xl font-black leading-tight mb-5">{{ $section['hero_title'] }}</h1>
    <p class="max-w-2xl text-lg text-white/85 leading-relaxed mb-5">{{ $section['hero_subtitle'] }}</p>
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
  </div>
</section>


@include('themes._shared.partials.trust-stats')

@include('themes._shared.partials.discover-links')
@include('themes._shared.partials.location-filter-script')
{{-- 2 Eylul 2026: kullanicinin bildirdigi gercek hata - anasayfadaki kurum
     kartlarinin (facility-card.blade.php) favori/karsilastir/toplu-fiyat
     butonlari, bu script'i tanimlayan engagement-script.blade.php sadece
     /kurumlar ve kurum detay sayfasina dahil edildigi icin anasayfada HIC
     calismiyordu - buton goze gorunuyordu ama tiklamak hicbir sey
     yapmiyordu (giris yonlendirmesi dahil). location-filter-script zaten
     window.paintEngagementToggles() cagirmaya hazirdi, sadece bu script hic
     yuklenmiyordu. --}}
@include('themes._shared.partials.engagement-script')
@endsection


