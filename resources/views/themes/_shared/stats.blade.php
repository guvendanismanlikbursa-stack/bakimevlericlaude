@extends('layouts.brand')
@section('title', 'Türkiye İstatistikleri'.($activeSection ? ' - '.$activeSection['title'] : '').' | İl Bazlı Kurum Dağılımı')
@section('meta_description', current_brand()['name'].' ile Türkiye genelinde kurumların il bazlı dağılımını ve yoğunluğunu keşfedin.')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-2">Türkiye İstatistikleri</h1>
  <p class="text-sm text-gray-500 mb-6">Platformda yayında olan kurumların il bazlı dağılımı{{ $activeSection ? ' — '.$activeSection['title'] : '' }}.</p>

  <div class="grid sm:grid-cols-3 gap-2 mb-8">
    <a href="{{ brand_route('stats.index') }}" class="rounded-xl border px-4 py-3 text-sm font-black text-center {{ ! $activeSection ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700' }}">Tüm Bölümler</a>
    @foreach($sections as $slug => $section)
      <a href="{{ brand_route('stats.index', ['bolum' => $slug]) }}" class="rounded-xl border px-4 py-3 text-sm font-black text-center {{ ($activeSection['slug'] ?? null) === $slug ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700' }}">{{ $section['title'] }}</a>
    @endforeach
  </div>

  <div class="grid sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <div class="text-xs text-gray-500">Toplam Kurum</div>
      <div class="text-2xl font-black mt-1">{{ number_format($grandTotal) }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <div class="text-xs text-gray-500">Kurumu Olan İl</div>
      <div class="text-2xl font-black mt-1">{{ $citiesWithData }} / {{ $totalCities }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <div class="text-xs text-gray-500">En Yoğun İl</div>
      <div class="text-2xl font-black mt-1">{{ $rows->first()->city_name ?? '—' }}</div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-8">
    <h2 class="font-black mb-1">Haritalı dağılım</h2>
    <p class="text-xs text-gray-400 mb-4">Rengi koyu iller, kurum sayısı yüksek illerdir. Bir ilin üzerine gelin, tıklayarak o ilin kurumlarını listeleyin.</p>
    <div class="relative">
      <div id="js-turkiye-harita" class="w-full [&_svg]:w-full [&_svg]:h-auto">
        {!! file_exists(public_path('images/turkiye-harita.svg')) ? file_get_contents(public_path('images/turkiye-harita.svg')) : '' !!}
      </div>
      <div id="js-harita-tooltip" class="hidden absolute z-10 pointer-events-none bg-gray-950 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg shadow-lg whitespace-nowrap"></div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="font-black mb-4">İl bazlı dağılım (sıralı liste)</h2>
    <div class="space-y-2">
      @forelse($rows as $row)
        <a href="{{ brand_route('facilities.index', ['city' => $row->city_slug] + ($activeSection ? ['bolum' => $activeSection['slug']] : [])) }}" class="flex items-center gap-3 group">
          <span class="w-28 text-sm font-semibold text-gray-700 truncate">{{ $row->city_name }}</span>
          <span class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
            <span class="block h-full bg-primary rounded-full" style="width: {{ max(4, round($row->total / $maxCity * 100)) }}%"></span>
          </span>
          <span class="w-10 text-right text-sm font-black text-gray-900">{{ $row->total }}</span>
        </a>
      @empty
        <p class="text-sm text-gray-400">Henüz veri yok.</p>
      @endforelse
    </div>
  </div>
</div>

<style>
  /* Harita SVG'sinin kendi CSS'i yok (bkz. kaynak repo); stil tamamen burada
     tanimlaniyor. #kibris Turkiye ili degil, veri/tiklama disi, notr gri. */
  #js-turkiye-harita svg path { fill: #e5e7eb; stroke: #fff; stroke-width: 0.6; transition: fill .15s ease; }
  #js-turkiye-harita svg #turkiye > g[id] { cursor: pointer; }
  #js-turkiye-harita svg #turkiye > g[id].is-hover path { stroke: #111827; stroke-width: 1.2; }
  #js-turkiye-harita svg #kibris path { fill: #f3f4f6; cursor: default; }
</style>
<script>
(function () {
  var counts = @json($mapCounts);
  var names = @json($cityNames);
  var maxCount = Math.max(1, Math.max.apply(null, Object.values(counts)));
  var brandColor = '{{ $brand['primary_color'] ?? '#1e6f5c' }}';
  var listUrl = @json(brand_route('facilities.index'));
  var activeBolum = @json($activeSection['slug'] ?? null);
  var svgHost = document.getElementById('js-turkiye-harita');
  var tooltip = document.getElementById('js-harita-tooltip');
  if (!svgHost) return;

  function hexToRgb(hex) {
    var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return m ? [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)] : [30, 111, 92];
  }
  var rgb = hexToRgb(brandColor);

  // Harita SVG'sinde her il, <g id="turkiye"> altinda kendi il slug'iyla
  // (istanbul dahil) ayri bir <g id="{slug}"> grubudur; Kibris ayri bir
  // kardes <g id="kibris"> altinda oldugu icin bu secici onu otomatik disarida tutar.
  svgHost.querySelectorAll('svg #turkiye > g[id]').forEach(function (group) {
    var slug = group.getAttribute('id');
    var name = names[slug] || slug;
    var count = counts[slug] || 0;
    var ratio = count > 0 ? Math.min(1, 0.18 + (count / maxCount) * 0.82) : 0;
    if (ratio > 0) {
      group.style.fill = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + ratio.toFixed(2) + ')';
    }
    group.addEventListener('mousemove', function (e) {
      var hostRect = svgHost.getBoundingClientRect();
      tooltip.textContent = name + ': ' + count + ' kurum';
      tooltip.style.left = (e.clientX - hostRect.left + 16) + 'px';
      tooltip.style.top = (e.clientY - hostRect.top + 8) + 'px';
      tooltip.classList.remove('hidden');
      group.classList.add('is-hover');
    });
    group.addEventListener('mouseleave', function () {
      tooltip.classList.add('hidden');
      group.classList.remove('is-hover');
    });
    group.addEventListener('click', function () {
      if (!counts.hasOwnProperty(slug)) return;
      var url = listUrl + '?city=' + slug;
      if (activeBolum) url += '&bolum=' + activeBolum;
      window.location.href = url;
    });
  });
})();
</script>
@endsection
