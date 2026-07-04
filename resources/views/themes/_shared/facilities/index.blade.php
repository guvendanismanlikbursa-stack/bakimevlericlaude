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
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-black text-gray-950">Kurumlar Bul</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $activeSection['title'] ?? 'Tüm bölümler' }} kapsamında {{ $facilities->total() }} kurum listeleniyor.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ brand_route('engagement.wizard', $activeSection ? ['bolum' => $activeSection['slug']] : []) }}" class="btn-primary rounded-xl px-4 py-3 text-sm font-black">Karar sihirbaz</a>
        <a href="{{ brand_route('engagement.compare') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">Kar&#351;&#305;la&#351;t&#305;r</a>
        <a href="{{ brand_route('engagement.favorites') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">Favoriler</a>
        <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'] ?? null, 'pre_registered' => 1])) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">&Ouml;n Kay&#305;tl&#305; Kurumlar</a>
        <button type="button" id="js-nearby-button" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700">📍 Yak&#305;n&#305;mdaki Kurumlar</button>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-2 mb-5">
      <a href="{{ brand_route('facilities.index') }}" class="rounded-xl border px-4 py-3 text-sm font-black {{ ! $activeSection ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700' }}">T&uuml;m Kurumlar</a>
      @foreach($sections as $slug => $section)
        <a href="{{ brand_route('facilities.index', ['bolum' => $slug]) }}" class="inline-flex items-center rounded-xl border px-4 py-3 text-sm font-black {{ ($activeSection['slug'] ?? null) === $slug ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700' }}">
          @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4 mr-1'])<span>{{ $section['title'] }}</span>
        </a>
      @endforeach
    </div>

    @if(count($nearbyFacilities ?? []))
      <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
        <h2 class="font-black text-gray-950 mb-3">📍 Size En Yakın Kurumlar</h2>
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
    @endif

    <form method="GET" data-district-map='@json($districtMap)' class="js-location-filter bg-white rounded-xl shadow-sm p-4 grid sm:grid-cols-2 lg:grid-cols-7 gap-3 mb-8 border border-gray-100">
      @if($activeSection)<input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">@endif
      @if(request('pre_registered'))<input type="hidden" name="pre_registered" value="1">@endif
      <select name="city" class="js-city border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">&#304;l se&ccedil;in</option>@foreach($cities as $city)<option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>@endforeach</select>
      <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2.5 text-sm bg-white" disabled><option value="">nce i&#304;l se&ccedil;in</option></select>
      <select name="category" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurum t&uuml;r&uuml;</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach</select>
      <select name="service" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">&Ouml;zellik</option>@foreach($sectionServices as $service)<option value="{{ $service }}" @selected(request('service') === $service)>{{ $service }}</option>@endforeach</select>
      <select name="price_max" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Maksimum b&uuml;t&ccedil;e</option>@foreach([10000 => '10.000 TL ve alt', 20000 => '20.000 TL ve alt', 30000 => '30.000 TL ve alt', 50000 => '50.000 TL ve alt'] as $value => $label)<option value="{{ $value }}" @selected((string) request('price_max') === (string) $value)>{{ $label }}</option>@endforeach</select>
      <select name="price_tier" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">T&uuml;m segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}" @selected(request('price_tier') === $value)>{{ $label }}</option>@endforeach</select>
      <button class="btn-primary rounded-lg px-4 py-2.5 font-black">Filtrele</button>
    </form>

    @php
      $listedFacilities = $facilities->getCollection();
      $selectedCity = $cities->firstWhere('slug', request('city'));
      $locationGroups = $listedFacilities->groupBy(fn ($facility) => $facility->city->name . ' / ' . $facility->district);
    @endphp
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 mb-8">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
        <div>
          <div class="text-sm font-black text-primary mb-1">Konum görünümü</div>
          <h2 class="text-2xl font-black text-gray-950">Harita mant&#305;&#287;&#305;nda b&ouml;lgesel da&#287;&#305;l&#305;m</h2>
          <p class="text-sm text-gray-500 mt-1">Listelenen kurumlar&#305; &#351;ehir ve il&ccedil;e k&uuml;melerine g&ouml;re h&#305;zl&#305;ca taray&#305;n.</p>
        </div>
        @if($activeSection && $selectedCity)
          <a href="{{ brand_route('location-guide.show', ['sectionSlug' => $activeSection['slug'], 'citySlug' => $selectedCity->slug]) }}" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-black text-gray-700 hover:shadow-sm">{{ $selectedCity->name }} rehberini a&ccedil;</a>
        @endif
      </div>
      <div class="grid md:grid-cols-3 gap-3">
        @forelse($locationGroups as $place => $items)
          @php $first = $items->first(); @endphp
          <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'] ?? null, 'city' => $first->city->slug, 'district' => $first->district])) }}" class="rounded-xl border border-gray-100 bg-gray-50 p-4 hover:bg-white hover:shadow-sm transition">
            <div class="font-black text-gray-950">{{ $place }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ $items->count() }} kurum listede</div>
            <div class="mt-3 h-2 rounded-full bg-gray-200 overflow-hidden"><div class="h-full rounded-full bg-primary" style="width: {{ min(100, 18 + ($items->count() * 14)) }}%"></div></div>
          </a>
        @empty
          <div class="md:col-span-3 rounded-xl border border-dashed border-gray-200 p-6 text-center text-gray-500">Konum görünümü için önce kurum listesi olmalı.</div>
        @endforelse
      </div>
    </section>
    @if($facilities->isEmpty())
      <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
        <div class="text-4xl font-black mb-4">0</div>
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('facilities.index') }}" class="text-primary underline mt-2 inline-block">Filtreleri temizle</a>
      </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
      @foreach($facilities as $facility)
        @php $section = service_section_for_scope($facility->category->brand_scope); $cardImage = facility_card_image($facility, $section); @endphp
        <article class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden border border-gray-100 group">
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
            <div class="h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center">
              <img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            </div>
            <div class="p-4">
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                @if($section)<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $section['title'] }}</span>@endif
                @if($facility->is_featured)<span class="badge-secondary text-white text-xs font-semibold px-2 py-0.5 rounded-full">&Ouml;ne &ccedil;&#305;kan</span>@endif
                @if($facility->is_claimed)<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Onayl&#305;</span>@endif
                @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
              </div>
              <h2 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h2>
              <p class="text-sm text-gray-500 mb-3">{{ $facility->city->name }}  {{ $facility->district }}  {{ $facility->category->name }}</p>
              <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $facility->description }}</p>
              <div class="flex items-center justify-between">
                <span class="text-amber-500 font-black text-sm"> {{ number_format($facility->rating, 1) }}</span>
                @if($facility->price_min)<span class="text-gray-700 font-black text-sm">{{ number_format($facility->price_min,0,',','.') }} TL<span class="text-gray-400 font-normal">/ay</span></span>@else<span class="text-primary text-sm font-black">Fiyat iste</span>@endif
              </div>
            </div>
          </a>
          <div class="px-4 pb-4 grid grid-cols-2 gap-2">
            @if(! $facility->is_claimed && $facility->source === 'google_maps_veri_cekici')
              <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 text-center hover:bg-gray-50">&#304;ncele</a>
              <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="rounded-lg px-3 py-2 text-sm font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Sahiplen</a>
            @else
              <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}">Favori</button>
              <button type="button" class="js-engagement-toggle rounded-lg px-3 py-2 text-sm font-black text-white" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};" data-mode="compare" data-id="{{ $facility->id }}">Kar&#351;&#305;la&#351;t&#305;r</button>
            @endif
          </div>
        </article>
      @endforeach
    </div>

    <div class="mt-8">{{ $facilities->links() }}</div>
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
