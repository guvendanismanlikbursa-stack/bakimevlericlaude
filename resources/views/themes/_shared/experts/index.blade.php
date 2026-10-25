@extends('layouts.brand')

@php
  // 7 Ekim 2026: kullanicinin talebi - bir doktor karti acik haldeyken
  // (?uzman=slug) sayfa basligi/aciklamasi o doktoru yansitsin (paylasim/
  // Google icin), degilse genel bolum basligi kullanilsin.
  $__title = $activeExpert
      ? "{$activeExpert->title} {$activeExpert->name} — {$activeExpert->branch} ({$activeExpert->branch_label}) ".($activeExpert->city?->name ?? '')
      : 'Özel Doktorlar — Çocuk ve Yaşlı Sağlığı Alanında Uzman Hekimler';
  $__description = $activeExpert
      ? trim("{$activeExpert->title} {$activeExpert->name}, ".($activeExpert->district ? "{$activeExpert->district}/" : '').($activeExpert->city?->name ?? '')." bölgesinde {$activeExpert->branch_label} olarak hizmet veriyor.".($activeExpert->phone ? " İletişim: {$activeExpert->phone}." : ''))
      : 'Çocuk ve yaşlı sağlığı alanında kendi muayenehanesinde çalışan özel doktorları il, ilçe veya branşa göre bulun.';
@endphp
@section('title', $__title)
@section('meta_description', $__description)
@section('canonical', canonical_url(['city', 'district', 'branch', 'search', 'min_rating', 'uzman']))
@section('robots_meta', $publiclyVisible ? 'index,follow' : 'noindex,nofollow')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

  @unless($publiclyVisible)
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 text-sm font-semibold px-4 py-3">
      🔒 Test sayfası — henüz hiçbir menüde linklenmiyor, arama motorlarına kapalı (noindex). Sadece bu linki bilenler görebilir.
    </div>
  @endunless

  <h1 class="text-2xl font-black text-gray-950 mb-2">Özel Doktorlar</h1>
  <p class="text-gray-500 mb-6">Çocuk ve yaşlı sağlığı alanında kendi muayenehanesinde çalışan özel doktorları bulun.</p>

  <form method="GET" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-expert-results" data-count-target="js-expert-count" class="js-location-filter flex flex-wrap gap-3 mb-6">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Doktor adı veya branş ile ara..." autocomplete="off" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[180px]">
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
    <select name="branch" class="border rounded-lg px-3 py-2 text-sm">
      <option value="">Tüm Branşlar</option>
      @foreach($branches as $slug => $b)
        <option value="{{ $slug }}" @selected(request('branch') === $slug)>{{ $b['title'] }} ({{ $b['label'] }})</option>
      @endforeach
    </select>
    {{-- 7 Ekim 2026: kullanicinin talebi - Google puanina gore filtre. --}}
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

  <p class="text-sm text-gray-500 mb-3 {{ $hasFilter ? '' : 'hidden' }}" id="js-expert-count-wrap"><span id="js-expert-count">{{ $experts?->total() ?? 0 }}</span> sonuç</p>

  <div id="js-expert-results">
    @include('themes._shared.experts._results', ['experts' => $experts, 'activeExpertSlug' => $activeExpertSlug])
  </div>

  {{-- 25 Ekim 2026: kullanıcının talebi - hoş geldiniz yazısının altında bilgilendirme ve
       İyilik Hareketi destekçisi hekimlere teşekkür, alt alta. Teşekkür listesi bilerek sade:
       yalnızca unvan, isim, şehir; fotoğraf, telefon, bağlantı yok (reklam yasağı). --}}
  <div style="margin-top:28px;">
  <div role="note" style="margin-bottom:12px;border:1px solid #cfe3e1;background:#f3faf9;border-radius:14px;padding:14px 16px;color:#1f2937;font-size:14px;line-height:1.6;">
    <div style="font-weight:800;color:#0d6e6a;margin-bottom:4px;">ℹ️ Bilgilendirme</div>
    Bu sayfada yer alan hekim bilgileri, yalnızca herkese açık kaynaklardaki güncel bilgilerden derlenmiştir. Amacımız, ailelerin aradıkları hekime daha kolay ve kısa sürede ulaşabilmesine yardımcı olmaktır. Bu bölümde hiçbir hekimin reklamı yapılmamaktadır; sıralama alfabetiktir ve hiçbir hekim öne çıkarılmaz. Bilgilerinizde düzeltme ya da kaldırma talebi için <a href="{{ route('contact.create') }}" style="color:#0d6e6a;font-weight:700;text-decoration:underline;">bize ulaşabilirsiniz</a>.
  </div>
  @if($destekciler->isNotEmpty())
    <section style="border:1px solid #f1e4c8;background:#fffaf0;border-radius:14px;padding:14px 16px;color:#1f2937;font-size:14px;line-height:1.6;">
      <div style="font-weight:800;color:#8a6a35;margin-bottom:4px;">💛 Teşekkür</div>
      <p style="margin:0 0 10px;">İyilik Hareketi'ne destek veren hekimlerimize teşekkür ederiz. Liste alfabetiktir; bir tanıtım ya da sıralama değildir.</p>
      <ul style="margin:0;padding:0;list-style:none;display:grid;gap:4px 18px;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
        @foreach($destekciler as $d)
          <li style="overflow-wrap:anywhere;">{{ trim(($d->title ? $d->title.' ' : '').$d->name) }}@if($d->city)<span style="color:#6b7280;"> · {{ $d->city->name }}</span>@endif</li>
        @endforeach
      </ul>
    </section>
  @endif
  </div>

  {{-- 7 Ekim 2026: kullanicinin bildirdigi gercek hata - "filtreleme hic
  calismiyor". Kok neden: bu script document.getElementById('js-expert-results')
  cagirisini TARAYICI o div'i DAHA PARSE ETMEDEN (script etiketi HTML
  kaynaginda div'den ONCEYDI) senkron calistiriyordu - resultsEl hep null
  kaliyor, runFilter() sessizce hicbir sey yapmadan donuyordu (hata da
  vermiyordu). Script artik sonuc div'inden SONRA calisir. --}}
  @include('themes._shared.partials.location-filter-script')
</div>

@if($activeExpert)
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'Physician',
  'name' => trim($activeExpert->title.' '.$activeExpert->name),
  'medicalSpecialty' => $activeExpert->branch,
  'telephone' => $activeExpert->phone,
  'address' => array_filter([
      '@type' => 'PostalAddress',
      'streetAddress' => $activeExpert->address,
      'addressLocality' => $activeExpert->district,
      'addressRegion' => $activeExpert->city?->name,
      'addressCountry' => 'TR',
  ]),
  'aggregateRating' => $activeExpert->rating ? array_filter([
      '@type' => 'AggregateRating',
      'ratingValue' => $activeExpert->rating,
      'reviewCount' => $activeExpert->review_count,
  ]) : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@elseif($experts && $experts->count())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $experts->values()->map(function ($expert, $index) {
      return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'name' => trim($expert->title.' '.$expert->name),
          'url' => brand_route('experts.index', ['uzman' => $expert->slug]),
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

<script>
var ACCENT = '#0d9488';
var INACTIVE_BORDER = '#e5e7eb';
var INACTIVE_CHEVRON = '#9ca3af';

function setCardOpen(card, open) {
  var detail = card.querySelector('.js-expert-detail');
  var chevron = card.querySelector('.js-expert-chevron');
  var header = card.querySelector('.js-expert-header');

  // 7 Ekim 2026: kullanicinin talebi - avatar rengi artik unvan/rutbeye
  // gore SABIT (bkz. Expert::titleRankColor()), acik/kapali durumuna gore
  // DEGISMEMELI - bu yuzden avatar burada artik hic ele alinmiyor.
  detail.classList.toggle('hidden', !open);
  chevron.classList.toggle('rotate-180', open);
  chevron.style.color = open ? ACCENT : INACTIVE_CHEVRON;
  card.style.borderColor = open ? ACCENT : INACTIVE_BORDER;
  card.classList.toggle('shadow-lg', open);
  card.classList.toggle('shadow-teal-100', open);
  card.classList.toggle('shadow-sm', !open);
  header.classList.toggle('bg-teal-50', open);
  header.classList.toggle('bg-white', !open);
}

// 7 Ekim 2026: kullanicinin talebi - isim/il/ilce/brans AJAX ile anlik
// filtrelendiginde kart listesi tamamen yeniden yazildigi (innerHTML)
// icin, tek tek karta tek seferlik event listener baglamak yerine
// SABIT (hic yeniden yazilmayan) #js-expert-results kapsayicisina TEK
// bir delegasyon listener'i bagliyoruz - boylece AJAX sonrasi yeni
// gelen kartlar da sayfa yenilenmeden calisir.
// AJAX ile sonuc degistiginde "X sonuç" satirini da gorunur yap
// (ilk yuklemede filtre yoksa gizliydi).
new MutationObserver(function () {
  document.getElementById('js-expert-count-wrap').classList.remove('hidden');
}).observe(document.getElementById('js-expert-results'), { childList: true });

// 25 Ekim 2026: kart sayacı (yalnızca admin panelinde görünür).
function kartOlay(slug, tur) {
  try {
    if (!navigator.sendBeacon || !slug) return;
    var v = new FormData(); v.append('_token', @json(csrf_token())); v.append('slug', slug); v.append('tur', tur);
    navigator.sendBeacon(@json(brand_route('experts.card-event')), v);
  } catch (e) {}
}
document.getElementById('js-expert-results').addEventListener('click', function (event) {
  var tel = event.target.closest('a[href^="tel:"]');
  if (tel && tel.closest('.js-expert-card')) { kartOlay(tel.closest('.js-expert-card').dataset.slug, 'arama'); }
});
document.getElementById('js-expert-results').addEventListener('click', function (event) {
  var btn = event.target.closest('.js-expert-toggle');
  if (!btn) return;
  var card = btn.closest('.js-expert-card');
  var detail = card.querySelector('.js-expert-detail');
  var wasOpen = !detail.classList.contains('hidden');

  document.querySelectorAll('.js-expert-card').forEach(function (c) { setCardOpen(c, false); });

  if (!wasOpen) {
    kartOlay(card.dataset.slug, 'acilma');
    setCardOpen(card, true);
    var url = new URL(window.location.href);
    url.searchParams.set('uzman', card.dataset.slug);
    window.history.replaceState({}, '', url);
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  } else {
    var url2 = new URL(window.location.href);
    url2.searchParams.delete('uzman');
    window.history.replaceState({}, '', url2);
  }
});
</script>
@endsection
