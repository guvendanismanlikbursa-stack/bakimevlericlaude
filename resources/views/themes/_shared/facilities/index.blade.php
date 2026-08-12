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
    @php $selectedCity = $cities->firstWhere('slug', request('city')); $sectionColors = $activeSection['theme']; @endphp
    {{-- 12 Agustos 2026: kullanicinin talebi - listeleme sayfasi duz siyah
         baslikla acilan, "premium" hissi vermeyen bir sayfaydi; artik
         aktif bolumun rengiyle boyali kisa bir baslik seridi ile aciliyor,
         detay/anasayfa sayfalarindaki gorsel dille tutarli. --}}
    <div class="rounded-2xl p-6 md:p-8 mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between" style="background: linear-gradient(135deg, {{ $sectionColors['primary'] }}, {{ $sectionColors['primary'] }}cc);">
      <div class="text-white">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-3 py-1 text-xs font-black mb-3">
          @include('themes._shared.partials.section-icon', ['section' => $activeSection, 'class' => 'w-4 h-4'])
          <span>{{ $activeSection['title'] }}</span>
        </div>
        <h1 class="text-2xl md:text-3xl font-black">Kurumları Bul</h1>
        <p class="text-sm text-white/80 mt-1"><span id="js-result-count" class="font-black text-white">{{ $facilities->total() }}</span> kurum listeleniyor.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ brand_route('engagement.wizard', ['bolum' => $activeSection['slug']]) }}" class="rounded-xl bg-white px-4 py-3 text-sm font-black" style="color: {{ $sectionColors['primary'] }};">Karar Sihirbazı</a>
        <a href="{{ brand_route('engagement.compare') }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-black text-white">Karşılaştır</a>
        @if(session('family_user_id'))
          <a href="{{ brand_route('engagement.favorites') }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-black text-white">Favoriler</a>
        @endif
        <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'] ?? null, 'pre_registered' => 1])) }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-black text-white">Ön Kayıtlı Kurumlar</a>
        <button type="button" id="js-nearby-button" class="rounded-xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-black text-white">📍 Yakınımdaki Kurumlar</button>
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

    <form method="GET" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-facility-results" data-count-target="js-result-count" class="js-location-filter bg-white rounded-xl shadow-sm p-4 grid sm:grid-cols-2 lg:grid-cols-7 gap-3 mb-8 border border-gray-100">
      <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">
      @if(request('pre_registered'))<input type="hidden" name="pre_registered" value="1">@endif
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara" class="border rounded-lg px-3 py-2.5 text-sm bg-white">
      <select name="city" aria-label="İl" class="js-city border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>@endforeach</select>
      <select name="district" aria-label="İlçe" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2.5 text-sm bg-white" disabled><option value="">Önce il seçin</option></select>
      <select name="category" aria-label="Kurum türü" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurum türü</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach</select>
      <select name="service" aria-label="Kurumun özellikleri" class="border rounded-lg px-3 py-2.5 text-sm bg-white"><option value="">Kurumun özellikleri</option>@foreach($sectionServices as $service)<option value="{{ $service }}" @selected(request('service') === $service)>{{ $service }}</option>@endforeach</select>
      <span class="flex items-center gap-1.5">
        <select name="price_tier" aria-label="Fiyat segmenti" class="border rounded-lg px-3 py-2.5 text-sm bg-white flex-1"><option value="">Tüm segmentler</option>@foreach(['ekonomik' => '🟢 Ekonomik', 'standart' => '🔵 Standart', 'premium' => '🟣 Premium', 'ultra_premium' => '🟡 Ultra Premium'] as $value => $label)<option value="{{ $value }}" @selected(request('price_tier') === $value)>{{ $label }}</option>@endforeach</select>
        @include('themes._shared.partials.segment-info-icon', ['categories' => $categories, 'id' => 'segment-info-filter', 'categorySelectName' => 'category'])
      </span>
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
          <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $activeSection['slug'], 'city' => $group->city_slug, 'district' => $group->district])) }}" class="rounded-xl border border-gray-100 bg-gray-50 p-4 hover:bg-white hover:shadow-md transition flex items-start gap-3">
            <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $sectionColors['soft'] }}; color: {{ $sectionColors['primary'] }};">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10 1.5c-4 0-7 3.1-7 6.9 0 4.9 6 9.6 6.3 9.8a1 1 0 0 0 1.4 0c.3-.2 6.3-4.9 6.3-9.8 0-3.8-3-6.9-7-6.9Zm0 9.5a2.6 2.6 0 1 1 0-5.2 2.6 2.6 0 0 1 0 5.2Z"/></svg>
            </span>
            <div class="min-w-0">
              <div class="font-black text-gray-950 truncate">{{ $group->city_name }} / {{ $group->district }}</div>
              <div class="text-sm text-gray-500 mt-1">{{ $group->total }} kurum</div>
              <div class="mt-3 h-2 rounded-full bg-gray-200 overflow-hidden"><div class="h-full rounded-full" style="background: {{ $sectionColors['primary'] }}; width: {{ min(100, 18 + ($group->total * 14)) }}%"></div></div>
            </div>
          </a>
        @empty
          <div class="md:col-span-3 rounded-xl border border-dashed border-gray-200 p-6 text-center text-gray-500">Konum görünümü için önce kurum listesi olmalı.</div>
        @endforelse
      </div>
    </section>
    <div id="js-facility-results">
      @include('themes._shared.facilities._results', ['facilities' => $facilities, 'activeSection' => $activeSection, 'sectionBreakdown' => $sectionBreakdown ?? []])
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
@if($activeSection ?? null)
  @include('themes._shared.partials.itemlist-jsonld', ['facilities' => $facilities])
@endif
@endsection
