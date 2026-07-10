@extends('layouts.brand')
@section('title', ($activeSection['title'] ?? 'Kurumlar').(request('city') ? ' - '.optional($cities->firstWhere('slug', request('city')))->name : '').' | Kurumları Bul')
@section('meta_description', ($activeSection['hero_subtitle'] ?? 'Bakım kurumlarını il, ilçe, hizmet ve bütçeye göre karşılaştırın.'))
@section('content')
@php
  $brand = current_brand();
  $theme = $brand['theme'];
  $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
  $pageClass = $theme === 'bakimevleri' ? 'bg-gray-100' : ($theme === 'bakimeviara' ? 'bg-white' : 'bg-gray-50');
@endphp
<div class="{{ $pageClass }}">
  <div class="max-w-6xl mx-auto px-4 py-10">
@if(! $activeSection)
    {{-- Bolum secilmeden gelindi (ana nav'daki "Kurumları Bul"): tum kurumlari
         tek listede gostermek yerine once bir bolum secmesi isteniyor. --}}
    <div class="text-center max-w-2xl mx-auto mb-10">
      <h1 class="text-3xl font-black text-gray-950 mb-2">Hangi bölümle ilgileniyorsunuz?</h1>
      <p class="text-sm text-gray-500">Kurum listesini görmek için önce bir bölüm seçin; sonuçları şehir, ilçe ve hizmete göre daraltabilirsiniz.</p>
    </div>
    <div class="grid sm:grid-cols-3 gap-4 max-w-4xl mx-auto">
      @foreach($sections as $slug => $section)
        <a href="{{ brand_route('facilities.index', ['bolum' => $slug]) }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition p-6 text-center">
          <span class="inline-flex rounded-xl p-3 mb-4" style="background: {{ $section['theme']['soft'] ?? '#f3f4f6' }}; color: {{ $section['theme']['primary'] ?? '#111827' }};">
            @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-7 h-7'])
          </span>
          <div class="font-black text-lg text-gray-950 mb-2">{{ $section['title'] }}</div>
          <p class="text-sm text-gray-500">{{ $section['hero_subtitle'] ?? '' }}</p>
        </a>
      @endforeach
    </div>
@else
    @php $selectedCity = $cities->firstWhere('slug', request('city')); @endphp
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-black text-gray-950">Kurumları Bul</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $activeSection['title'] }} kapsamında <span id="js-result-count">{{ $facilities->total() }}</span> kurum listeleniyor.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ brand_route('engagement.wizard', ['bolum' => $activeSection['slug']]) }}" class="btn-primary rounded-xl px-4 py-3 text-sm font-black">Karar Sihirbazı</a>
        <a href="{{ brand_route('engagement.compare') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">Karşılaştır</a>
        @if(session('family_user_id'))
          <a href="{{ brand_route('engagement.favorites') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">Favoriler</a>
        @endif
        <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'] ?? null, 'pre_registered' => 1])) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">Ön Kayıtlı Kurumlar</a>
        <button type="button" id="js-nearby-button" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">📍 Yakınımdaki Kurumlar</button>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-2 mb-5">
      @foreach($sections as $slug => $section)
        <a href="{{ brand_route('facilities.index', ['bolum' => $slug]) }}" class="inline-flex items-center rounded-xl border px-4 py-3 text-sm font-black {{ ($activeSection['slug'] ?? null) === $slug ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700' }}">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4 mr-1'])<span>{{ $section['title'] }}</span>
        </a>
      @endforeach
    </div>

    @if(count($nearbyFacilities ?? []))
      <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
        <h2 class="font-black text-gray-950 mb-1">📍 Size En Yakın Kurumlar</h2>
        <p class="text-xs text-gray-400 mb-3">Konumunuzu paylaştığınız kurumların gerçek mesafesine göre sıralanmıştır.</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          @foreach($nearbyFacilities as $entry)
            <a href="{{ brand_route('facilities.show', ['slug' => $entry['facility']->slug]) }}" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 hover:bg-gray-50">
              <span>
                <span class="block font-bold text-sm text-gray-950">{{ $entry['facility']->name }}</span>
                <span class="block text-xs text-gray-500">{{ $entry['facility']->city->name }}  {{ $entry['facility']->district }}</span>
              </span>
              <span class="text-xs font-black text-primary whitespace-nowrap ml-2">{{ number_format($entry['distance_km'], 1) }} km</span>
            </a>
          @endforeach
        </div>
      </div>
    @elseif(request()->filled('lat'))
      <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-6 text-sm text-amber-800">
        Konumunuza yakın koordinatı kayıtlı bir kurum bulunamadı; bunun yerine size en yakın <strong>il</strong> baz alınarak aşağıdaki liste gösteriliyor.
      </div>
    @endif

    <form method="GET" data-district-map='@json($districtMap)' data-auto-submit="1" data-count-url="{{ brand_route('facilities.count') }}" class="js-location-filter bg-white rounded-xl shadow-sm p-4 grid sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-8 border border-gray-100">
      <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">
      @if(request('pre_registered'))<input type="hidden" name="pre_registered" value="1">@endif
      <select name="city" class="js-city border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>@endforeach</select>
      <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2.5 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
      <select name="category" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach</select>
      <select name="service" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}" @selected(request('service') === $service)>{{ $service }}</option>@endforeach</select>
      <select name="price_tier" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}" @selected(request('price_tier') === $value)>{{ $label }}</option>@endforeach</select>
      <button class="btn-primary rounded-lg px-4 py-2.5 font-black">Filtrele</button>
    </form>

    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 mb-8">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
        <div>
          <div class="text-sm font-black text-primary mb-1">Konum görünümü</div>
          <h2 class="text-2xl font-black text-gray-950">Harita mantığında bölgesel dağılım</h2>
          <p class="text-sm text-gray-500 mt-1">Listelenen kurumları şehir ve ilçe kümelerine göre hızlıca tarayın.</p>
        </div>
        @if($selectedCity)
          <a href="{{ brand_route('location-guide.show', ['sectionSlug' => $activeSection['slug'], 'citySlug' => $selectedCity->slug]) }}" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-black text-gray-700 hover:shadow-sm">{{ $selectedCity->name }} rehberini aç</a>
        @endif
      </div>
      <div class="grid md:grid-cols-3 gap-3">
        @forelse($regionGroups as $group)
          <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'], 'city' => $group->city_slug, 'district' => $group->district])) }}" class="rounded-xl border border-gray-100 bg-gray-50 p-4 hover:bg-white hover:shadow-sm transition">
            <div class="font-black text-gray-950">{{ $group->city_name }} / {{ $group->district }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ $group->total }} kurum</div>
            <div class="mt-3 h-2 rounded-full bg-gray-200 overflow-hidden"><div class="h-full rounded-full bg-primary" style="width: {{ min(100, 18 + ($group->total * 14)) }}%"></div></div>
          </a>
        @empty
          <div class="md:col-span-3 rounded-xl border border-dashed border-gray-200 p-6 text-center text-gray-500">Konum görünümü için önce kurum listesi olmalı.</div>
        @endforelse
      </div>
    </section>
    @if($facilities->isEmpty())
      <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
        <div class="text-4xl font-black mb-4">0</div>
        @if(request()->filled('budget'))
          <p>Aramış olduğunuz {{ number_format((float) request('budget'), 0, ',', '.') }} TL bütçede kurum bulunamadı.</p>
        @else
          <p>Kriterlere uygun kurum bulunamadı.</p>
        @endif
        <a href="{{ brand_route('facilities.index', ['bolum' => $activeSection['slug']]) }}" class="text-primary underline mt-2 inline-block">Filtreleri temizle</a>
      </div>
    @endif

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
</div>
@include('themes._shared.partials.location-filter-script')
@include('themes._shared.partials.engagement-script')
<script>
(function(){
  const button = document.getElementById('js-nearby-button');
  if (!button) return;
  const nearbyUrl = @json(brand_route('nearby.locate'));
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  button.addEventListener('click', function(){
    if (!navigator.geolocation) {
      alert('Tarayıcınız konum paylaşımını desteklemiyor.');
      return;
    }
    button.textContent = 'Konum alınıyor...';
    navigator.geolocation.getCurrentPosition(function(position){
      fetch(nearbyUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ lat: position.coords.latitude, lng: position.coords.longitude })
      }).then(r => r.json()).then(function(data){
        if (data.ok) {
          window.location.href = data.redirect_url;
        } else {
          button.textContent = '📍 Yakınımdaki Kurumlar';
          alert('En yakın il bulunamadı.');
        }
      }).catch(function(){ button.textContent = '📍 Yakınımdaki Kurumlar'; });
    }, function(){
      button.textContent = '📍 Yakınımdaki Kurumlar';
      alert('Konum izni verilmedi. En yakın kurumları görmek için tarayıcı konum iznini açabilirsiniz.');
    });
  });
})();
</script>
@endsection
