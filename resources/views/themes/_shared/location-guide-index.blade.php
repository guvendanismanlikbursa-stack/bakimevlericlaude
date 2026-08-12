@extends('layouts.brand')
@php
  $colors = $section['theme'];
  $title = $section['title'].' Rehberi - Türkiye\'nin 81 İlinde Kurum Karşılaştırma';
@endphp
@section('title', $title)
@section('og_title', $title)
@section('meta_description', $brand['name'].' ile Türkiye\'nin 81 ilinde '.$section['title'].' kurumlarını (il ve ilçe bazında) karşılaştırın, ücretsiz teklif alın.')
@if($section['slug'] !== ($brand['default_section'] ?? null))
  @section('robots_meta', 'noindex,follow')
@endif
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => [
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => $section['title'].' - İl Rehberi', 'url' => brand_route('location-guide.index', ['sectionSlug' => $section['slug']])],
  ]])
@endsection
@section('content')

<section style="background: linear-gradient(135deg, {{ $colors['primary'] }}, {{ $colors['primary'] }}cc);" class="text-white">
  <div class="max-w-4xl mx-auto px-4 py-14 text-center">
    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-4 py-2 text-xs font-black mb-4">İl / İlçe Rehberi</div>
    <h1 class="text-2xl md:text-4xl font-black mb-3">{{ $section['title'] }} - Türkiye'nin 81 İlinde Kurum Rehberi</h1>
    <p class="text-white/85 max-w-2xl mx-auto">{{ $section['hero_subtitle'] ?? '' }} Aşağıdan ilinizi seçin, size en yakın kurumları ve bölgesel fiyat aralıklarını görün.</p>
  </div>
</section>

<div class="max-w-5xl mx-auto px-4 py-12">
  @if(count($sections) > 1)
    <div class="flex flex-wrap gap-2 justify-center mb-10">
      @foreach($sections as $slug => $s)
        <a href="{{ brand_route('location-guide.index', ['sectionSlug' => $slug]) }}" class="rounded-full px-4 py-2 text-sm font-black {{ $slug === $section['slug'] ? 'text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}" style="{{ $slug === $section['slug'] ? 'background:'.$s['theme']['primary'].';' : '' }}">{{ $s['title'] }}</a>
      @endforeach
    </div>
  @endif

  <h2 class="text-xl font-black text-gray-950 mb-5 text-center">İle göre {{ mb_strtolower($section['title']) }} kurumları</h2>
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    @foreach($cities as $city)
      <a href="{{ brand_route('location-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug]) }}" class="rounded-xl border border-gray-100 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm hover:shadow-md hover:text-primary transition">{{ $city->name }}</a>
    @endforeach
  </div>

  <div class="mt-10 text-center">
    <a href="{{ brand_route('price-guide.index') }}" class="text-sm font-black text-primary hover:underline">Şehre göre ücret rehberini görüntüle →</a>
  </div>
</div>
@endsection
