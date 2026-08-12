@extends('layouts.brand')
@section('title', 'Ücret Rehberi - Şehre Göre Bakım Kurumu Fiyatları')
@section('meta_description', current_brand()['name'].' ile şehir ve hizmet seçerek il bazlı fiyat rehberini görüntüleyin.')
@section('content')
@php
  $brand = current_brand();
  $primary = $brand['primary_color'];
  $popularCities = ['istanbul' => 'İstanbul', 'ankara' => 'Ankara', 'izmir' => 'İzmir', 'bursa' => 'Bursa', 'antalya' => 'Antalya', 'konya' => 'Konya'];
  $defaultSectionSlug = $activeSection['slug'] ?? array_key_first($sections);
@endphp
<section style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-3xl mx-auto px-4 py-14 text-center">
    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-4 py-2 text-xs font-black mb-4">Ücret Rehberi</div>
    <h1 class="text-2xl md:text-4xl font-black mb-3">Şehrinizdeki güncel bakım ücretlerini görün</h1>
    <p class="text-white/85 max-w-xl mx-auto">Hizmet türü ve il seçin; o ile ait ortalama, en düşük ve en yüksek fiyat aralığını, listelenen kurum sayısıyla birlikte görüntüleyin.</p>
  </div>
</section>

<div class="max-w-3xl mx-auto px-4 py-12">
  <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-7 grid sm:grid-cols-2 gap-4 items-end -mt-16 relative z-10" id="price-guide-form">
    <div>
      <label class="text-sm font-black block mb-1.5 text-gray-700">Hizmet türü</label>
      <select id="pg-section" class="border rounded-lg px-3 py-2.5 w-full bg-white">
        @foreach($sections as $slug => $section)
          <option value="{{ $slug }}" @selected(($activeSection['slug'] ?? null) === $slug)>{{ $section['title'] }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm font-black block mb-1.5 text-gray-700">İl</label>
      <select id="pg-city" class="border rounded-lg px-3 py-2.5 w-full bg-white">
        @foreach($cities as $city)
          <option value="{{ $city->slug }}">{{ $city->name }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="sm:col-span-2 rounded-lg px-6 py-3 font-black text-white" style="background: {{ $primary }};">Fiyatları Görüntüle</button>
  </form>

  <div class="mt-10">
    <div class="text-sm font-black text-gray-500 mb-3">Popüler şehirler</div>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      @foreach($popularCities as $slug => $name)
        <a href="{{ brand_route('price-guide.show', ['sectionSlug' => $defaultSectionSlug, 'citySlug' => $slug]) }}" class="flex items-center gap-2 rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition font-black text-gray-950">
          <span class="inline-flex rounded-lg p-1.5" style="background: {{ $primary }}14; color: {{ $primary }};">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10 2a6 6 0 0 0-6 6c0 4.5 6 10 6 10s6-5.5 6-10a6 6 0 0 0-6-6Zm0 8.25a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5Z"/></svg>
          </span>
          {{ $name }}
        </a>
      @endforeach
    </div>
  </div>

  {{-- 12 Agustos 2026: kullanicinin talebi - "81 il/ilcede gecerli olsun".
       Yukaridaki 6 sehir disinda kalan iller SADECE JS'li <select> ile
       erisilebiliyordu (Googlebot bunu guvenilir bir link olarak takip
       etmez) - asagidaki gercek <a href> listesi 81 ilin tamamini
       taranabilir/tiklanabilir hale getirir. --}}
  <div class="mt-10">
    <div class="text-sm font-black text-gray-500 mb-3">Tüm iller</div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
      @foreach($cities as $city)
        <a href="{{ brand_route('price-guide.show', ['sectionSlug' => $defaultSectionSlug, 'citySlug' => $city->slug]) }}" class="rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:shadow-sm hover:text-primary transition">{{ $city->name }}</a>
      @endforeach
    </div>
  </div>
</div>
<script>
  var priceGuideUrlTemplate = @json(brand_route('price-guide.show', ['sectionSlug' => '__SECTION__', 'citySlug' => '__CITY__']));
  document.getElementById('price-guide-form').addEventListener('submit', function(e){
    e.preventDefault();
    var section = document.getElementById('pg-section').value;
    var city = document.getElementById('pg-city').value;
    window.location.href = priceGuideUrlTemplate.replace('__SECTION__', section).replace('__CITY__', city);
  });
</script>
@endsection
