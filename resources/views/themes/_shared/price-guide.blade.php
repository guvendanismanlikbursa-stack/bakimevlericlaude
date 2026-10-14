@extends('layouts.brand')
@php
  // 26 Agustos 2026: kullanicinin talebi - "bakimevi fiyatlari" gibi
  // gercek arama terimleri icin bkz. config/brands.php seo_title alani
  // ("Bakimevi", "Kres" vb.) - $section['title'] ("Yasli Bakim" gibi)
  // kimsenin aramadigi bir terim. bkz. location-guide.blade.php ayni
  // tarihli/mantikli duzeltme.
  $topicTitle = $category->name ?? $section['seo_title'] ?? $section['title'];
  $placeTitle = ($districtName ?? null) ? $city->name.' / '.$districtName : $city->name;
  $breadcrumbItems = [
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => $section['title'].' Fiyatları', 'url' => brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug])],
  ];
  if ($category) {
      $breadcrumbItems[] = ['name' => $category->name.' Fiyatları', 'url' => brand_route('price-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $category->slug])];
  }
  if ($districtName ?? null) {
      $breadcrumbItems[] = ['name' => $districtName, 'url' => url()->current()];
  }

  // 13 Ekim 2026: kullanicinin talebi - pazar arastirmasinda tespit edilen,
  // sik aranan ama platformda karsiligi olmayan uzun kuyruklu ifadeler
  // ("calisan anne kres destegi", "7/24 hemsireli yasli bakim evi") -
  // location-guide.blade.php'deki AYNI mantik, bolume ozel GERCEK bilgi.
  $extraFaq = [];
  if ($section['slug'] === 'cocuk') {
      $extraFaq[] = [
          $placeTitle.' bölgesinde çalışan anne/baba için kreş desteği var mı?',
          'Bazı belediyeler ve işletmeler çalışan ebeveynler için kreş desteği/indirimi sunabilir; bu, kurumdan kuruma ve dönemden döneme değişir. '.$placeTitle.' bölgesindeki kurumlarla doğrudan görüşerek güncel kreş desteği ve erken kayıt fırsatlarını öğrenebilirsiniz.',
      ];
  } elseif ($section['slug'] === 'yasli-bakim') {
      $extraFaq[] = [
          $placeTitle.' bölgesinde 7/24 hemşireli yaşlı bakım evi var mı?',
          $placeTitle.' bölgesindeki kurumların çoğu 7/24 hemşire/bakım personeli desteği sunar; Alzheimer/demans veya yatalak hasta bakımı gibi özel ihtiyaçlar için bu hizmetin kapsamını kuruma doğrudan sormanızı öneririz.',
      ];
  }
  $faqPreview = [...$extraFaq, ...(site_section_content($brand['slug'], $section['slug'])['faq_preview'] ?? [])];
@endphp
@section('title', $placeTitle.' '.$topicTitle.' Fiyatları - Ücret Rehberi')
@section('og_title', $placeTitle.' '.$topicTitle.' Fiyatları')
@section('meta_description', $placeTitle.' bölgesinde '.$topicTitle.' kurumlarının ortalama, en düşük ve en yüksek fiyat bilgileri.')
@section('og_image', seo_og_image($section))
{{-- 12 Ekim 2026: kullanicinin acik talebi - "fiyat soranlara siteler
     cikmali 3 sitede de". Daha once bir markanin KENDI bolumu disindaki
     fiyat rehberi sayfalari kopya icerik riskine karsi noindex idi (bkz.
     location-guide.blade.php'deki AYNI kural, o sayfa icin DEGISTIRILMEDI).
     Kullanicinin bilerek verdigi karar: fiyat sayfalarinda kopya icerik
     riskini gozardi edip her 3 markanin da "X fiyatlari" aramalarinda
     gorunmesi tercih edildi. --}}
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => $breadcrumbItems])
  @include('themes._shared.partials.itemlist-jsonld', ['facilities' => $facilities])
  @include('themes._shared.partials.faqpage-jsonld', ['questions' => $faqPreview])
@endsection
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="text-xs font-black text-primary mb-2">Ücret Rehberi</div>
  <h1 class="text-3xl font-black text-gray-950 mb-2">{{ $placeTitle }} {{ $topicTitle }} Fiyatları</h1>
  <p class="text-sm text-gray-500 mb-3">{{ $placeTitle }} bölgesinde yayında olan {{ $stats['total'] }} {{ $topicTitle }} kurumunun fiyat özeti. Fiyatlar kurumlar tarafından girilir, kesin ücret için kuruma teklif talebi gönderin.</p>
  @if(!empty($guideContent['intro']))
    <p class="text-sm text-gray-700 font-semibold mb-3">{{ $guideContent['intro'] }}</p>
  @endif
  @if($category && !empty($category->seo_description))
    <p class="text-sm text-gray-600 mb-8">{{ $category->seo_description }}</p>
  @else
    <div class="mb-8"></div>
  @endif

  @if($sectionCategories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-8">
      <a href="{{ brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ! $category ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">Tümü</a>
      @foreach($sectionCategories as $sectionCategory)
        <a href="{{ brand_route('price-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $sectionCategory->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ $category?->id === $sectionCategory->id ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">{{ $sectionCategory->name }}</a>
      @endforeach
    </div>
  @endif

  @if(($nearDistricts ?? collect())->isNotEmpty())
    <div class="mb-8">
      <div class="text-xs font-black text-gray-500 mb-2">Yakın ilçe fiyat rehberleri</div>
      <div class="flex flex-wrap gap-2">
        @foreach($nearDistricts as $district)
          <a href="{{ $category ? brand_route('price-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $category->slug, 'districtSlug' => Str::slug($district)]) : brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'districtSlug' => Str::slug($district)]) }}" class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:shadow-sm">{{ $district }}</a>
        @endforeach
      </div>
    </div>
  @endif

  @if($stats['priced_count'] > 0)
    <div class="grid sm:grid-cols-3 gap-4 mb-10">
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">Ortalama Başlangıç Ücreti</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['avg_min'], 0, ',', '.') }} TL</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En Düşük</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['min'], 0, ',', '.') }} TL</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En Yüksek</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['max'], 0, ',', '.') }} TL</div>
      </div>
    </div>

    @if(count($tierCounts))
      <div class="flex flex-wrap gap-2 mb-10">
        @foreach(['ekonomik' => ['🟢','Ekonomik','bg-green-100 text-green-800'], 'standart' => ['🔵','Standart','bg-blue-100 text-blue-800'], 'premium' => ['🟣','Premium','bg-purple-100 text-purple-800'], 'ultra_premium' => ['🟡','Ultra Premium','bg-amber-100 text-amber-800']] as $key => [$emoji, $label, $classes])
          @if(($tierCounts[$key] ?? 0) > 0)
            <span class="{{ $classes }} text-xs font-semibold px-3 py-1.5 rounded-full">{{ $emoji }} {{ $label }}: {{ $tierCounts[$key] }} kurum</span>
          @endif
        @endforeach
      </div>
    @endif
  @else
    <div class="bg-amber-50 border border-amber-100 text-amber-800 text-sm rounded-xl p-4 mb-10">Bu il için henüz fiyat bilgisi girilmiş kurum yok. Aşağıdaki kurumlardan doğrudan teklif isteyebilirsiniz.</div>
  @endif

  <h2 class="font-black text-xl mb-4">{{ $placeTitle }} {{ $topicTitle }} Kurumları</h2>
  @if($facilities->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Bu il için henüz yayında kurum yok.</p>
    </div>
  @else
    <div class="grid md:grid-cols-4 gap-6">
      @foreach($facilities as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
    <div class="mt-8">
      @include('partials.pagination-info', ['paginator' => $facilities])
      {{ $facilities->links() }}
    </div>
  @endif

  {{-- 13 Ekim 2026: kullanicinin talebi - JSON-LD'deki SSS gorunur icerikle
       de eslesmeli (Google'in "gizli/sadece schema'da olan icerik" karsiti
       politikasina uymak icin), bkz. yukaridaki $faqPreview ayni tarihli yorum. --}}
  @if(!empty($faqPreview))
    <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mt-8">
      <h2 class="text-lg font-black text-gray-950 mb-4">Sıkça sorulan sorular</h2>
      <div class="space-y-4">
        @foreach($faqPreview as [$question, $answer])
          <div>
            <div class="font-bold text-gray-900">{{ $question }}</div>
            <p class="text-gray-600 text-sm leading-relaxed mt-1">{{ $answer }}</p>
          </div>
        @endforeach
      </div>
    </div>
  @endif
</div>
@endsection
