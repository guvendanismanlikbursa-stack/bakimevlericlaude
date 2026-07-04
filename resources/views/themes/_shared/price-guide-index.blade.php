@extends('layouts.brand')
@section('title', 'Ücret Rehberi - Şehre Göre Bakım Kurumu Fiyatları')
@section('meta_description', current_brand()['name'].' ile şehir ve hizmet seçerek il bazlı fiyat rehberini görüntüleyin.')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-2">Ücret Rehberi</h1>
  <p class="text-sm text-gray-500 mb-8">Şehir ve hizmet türü seçin, o ile ait güncel fiyat özetini görün.</p>

  <form method="GET" class="bg-white rounded-xl shadow-sm p-6 space-y-4" id="price-guide-form">
    <div>
      <label class="text-sm font-medium block mb-1">Hizmet türü</label>
      <select id="pg-section" class="border rounded-lg px-3 py-2 w-full">
        @foreach($sections as $slug => $section)
          <option value="{{ $slug }}" @selected(($activeSection['slug'] ?? null) === $slug)>{{ $section['title'] }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">İl</label>
      <select id="pg-city" class="border rounded-lg px-3 py-2 w-full">
        @foreach($cities as $city)
          <option value="{{ $city->slug }}">{{ $city->name }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn-primary rounded-lg px-6 py-3 font-black">Fiyatları Görüntüle</button>
  </form>
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
