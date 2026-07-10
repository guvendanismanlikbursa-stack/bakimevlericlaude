@extends('layouts.brand')
@php
  $topicTitle = $category->name ?? $section['title'];
  $placeTitle = ($districtName ?? null) ? $city->name.' / '.$districtName : $city->name;
  $breadcrumbItems = [
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => $section['title'].' Fiyatları', 'url' => brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug])],
  ];
  if ($category) {
      $breadcrumbItems[] = ['name' => $category->name.' Fiyatları', 'url' => url()->current()];
  }
@endphp
@section('title', $placeTitle.' '.$topicTitle.' Fiyatları - Ücret Rehberi')
@section('og_title', $placeTitle.' '.$topicTitle.' Fiyatları')
@section('meta_description', $placeTitle.' bölgesinde '.$topicTitle.' kurumlarının ortalama, en düşük ve en yüksek fiyat bilgileri.')
@section('og_image', seo_og_image($section))
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => $breadcrumbItems])
@endsection
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="text-xs font-black text-primary mb-2">Ücret Rehberi</div>
  <h1 class="text-3xl font-black text-gray-950 mb-2">{{ $placeTitle }} {{ $topicTitle }} Fiyatları</h1>
  <p class="text-sm text-gray-500 mb-8">{{ $placeTitle }} bölgesinde yayında olan {{ $stats['total'] }} {{ $topicTitle }} kurumunun fiyat özeti. Fiyatlar kurumlar tarafından girilir, kesin ücret için kuruma teklif talebi gönderin.</p>

  @if($sectionCategories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-8">
      <a href="{{ brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ! $category ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">Tümü</a>
      @foreach($sectionCategories as $sectionCategory)
        <a href="{{ brand_route('price-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $sectionCategory->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ $category?->id === $sectionCategory->id ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">{{ $sectionCategory->name }}</a>
      @endforeach
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
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($facilities as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
    <div class="mt-8">
      @include('partials.pagination-info', ['paginator' => $facilities])
      {{ $facilities->links() }}
    </div>
  @endif
</div>
@endsection
