@extends('layouts.brand')

@php
  $__title = $activeOrg
      ? "{$activeOrg->name} — {$activeOrg->service_type} ({$activeOrg->service_type_label}) ".($activeOrg->city?->name ?? '')
      : 'Organizasyon Şirketleri — Doğum Günü, Kına, Düğün ve Daha Fazlası';
  $__description = $activeOrg
      ? trim("{$activeOrg->name}, ".($activeOrg->district ? "{$activeOrg->district}/" : '').($activeOrg->city?->name ?? '')." bölgesinde {$activeOrg->service_type_label} hizmeti veriyor.".($activeOrg->phone ? " İletişim: {$activeOrg->phone}." : ''))
      : 'Doğum günü, kına, düğün, nişan ve daha birçok özel gün için organizasyon şirketlerini il, ilçe veya hizmet türüne göre bulun.';
@endphp
@section('title', $__title)
@section('meta_description', $__description)
@section('canonical', canonical_url(['city', 'district', 'service_type', 'search', 'min_rating', 'firma']))
@section('robots_meta', $publiclyVisible ? 'index,follow' : 'noindex,nofollow')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

  @unless($publiclyVisible)
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 text-sm font-semibold px-4 py-3">
      🔒 Test sayfası — henüz hiçbir menüde linklenmiyor, arama motorlarına kapalı (noindex). Sadece bu linki bilenler görebilir.
    </div>
  @endunless

  <h1 class="text-2xl font-black text-gray-950 mb-2">Organizasyon Şirketleri</h1>
  <p class="text-gray-500 mb-6">Doğum günü, kına, düğün ve daha birçok özel gün için organizasyon şirketlerini bulun.</p>

  <form method="GET" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-org-results" data-count-target="js-org-count" class="js-location-filter flex flex-wrap gap-3 mb-6">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Ne tür organizasyon/etkinlik arıyorsunuz? (örn: doğum günü, düğün, kına)" autocomplete="off" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[220px]">
    <select name="city" class="js-city border rounded-lg px-3 py-2 text-sm">
      <option value="">Tüm İller</option>
      @foreach($cities as $city)
        <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
      @endforeach
    </select>
    <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2 text-sm" @if(! request('city')) disabled @endif>
      <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
      @if(request('city'))
        @foreach($districtMap[request('city')] ?? [] as $districtName)
          <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
        @endforeach
      @endif
    </select>
    <select name="service_type" class="border rounded-lg px-3 py-2 text-sm">
      <option value="">Tüm Hizmet Türleri</option>
      @foreach($serviceTypes as $slug => $s)
        <option value="{{ $slug }}" @selected(request('service_type') === $slug)>{{ $s['title'] }} ({{ $s['label'] }})</option>
      @endforeach
    </select>
    <select name="min_rating" class="border rounded-lg px-3 py-2 text-sm">
      <option value="">Tüm Puanlar</option>
      <option value="4.5" @selected(request('min_rating') === '4.5')>★ 4.5 ve üzeri</option>
      <option value="4" @selected(request('min_rating') === '4')>★ 4.0 ve üzeri</option>
      <option value="3.5" @selected(request('min_rating') === '3.5')>★ 3.5 ve üzeri</option>
      <option value="3" @selected(request('min_rating') === '3')>★ 3.0 ve üzeri</option>
    </select>
    @if($hasFilter)
      <a href="{{ request()->url() }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
    @endif
  </form>

  <p class="text-sm text-gray-500 mb-3 {{ $hasFilter ? '' : 'hidden' }}" id="js-org-count-wrap"><span id="js-org-count">{{ $organizations?->total() ?? 0 }}</span> sonuç</p>

  <div id="js-org-results">
    @include('themes._shared.organizations._results', ['organizations' => $organizations, 'activeOrgSlug' => $activeOrgSlug])
  </div>

  @include('themes._shared.partials.location-filter-script')
</div>

@if($activeOrg)
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => $activeOrg->name,
  'telephone' => $activeOrg->phone,
  'address' => array_filter([
      '@type' => 'PostalAddress',
      'streetAddress' => $activeOrg->address,
      'addressLocality' => $activeOrg->district,
      'addressRegion' => $activeOrg->city?->name,
      'addressCountry' => 'TR',
  ]),
  'aggregateRating' => $activeOrg->rating ? array_filter([
      '@type' => 'AggregateRating',
      'ratingValue' => $activeOrg->rating,
      'reviewCount' => $activeOrg->review_count,
  ]) : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@elseif($organizations && $organizations->count())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $organizations->values()->map(function ($org, $index) {
      return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'name' => $org->name,
          'url' => brand_route('organizations.index', ['firma' => $org->slug]),
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

<script>
var ACCENT = '#db2777';
var INACTIVE_BORDER = '#e5e7eb';
var INACTIVE_CHEVRON = '#9ca3af';

function setCardOpen(card, open) {
  var detail = card.querySelector('.js-org-detail');
  var chevron = card.querySelector('.js-org-chevron');
  var header = card.querySelector('.js-org-header');

  detail.classList.toggle('hidden', !open);
  chevron.classList.toggle('rotate-180', open);
  chevron.style.color = open ? ACCENT : INACTIVE_CHEVRON;
  card.style.borderColor = open ? ACCENT : INACTIVE_BORDER;
  card.classList.toggle('shadow-lg', open);
  card.classList.toggle('shadow-pink-100', open);
  card.classList.toggle('shadow-sm', !open);
  header.classList.toggle('bg-pink-50', open);
  header.classList.toggle('bg-white', !open);
}

new MutationObserver(function () {
  document.getElementById('js-org-count-wrap').classList.remove('hidden');
}).observe(document.getElementById('js-org-results'), { childList: true });

document.getElementById('js-org-results').addEventListener('click', function (event) {
  var btn = event.target.closest('.js-org-toggle');
  if (!btn) return;
  var card = btn.closest('.js-org-card');
  var detail = card.querySelector('.js-org-detail');
  var wasOpen = !detail.classList.contains('hidden');

  document.querySelectorAll('.js-org-card').forEach(function (c) { setCardOpen(c, false); });

  if (!wasOpen) {
    setCardOpen(card, true);
    var url = new URL(window.location.href);
    url.searchParams.set('firma', card.dataset.slug);
    window.history.replaceState({}, '', url);
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  } else {
    var url2 = new URL(window.location.href);
    url2.searchParams.delete('firma');
    window.history.replaceState({}, '', url2);
  }
});
</script>
@endsection
