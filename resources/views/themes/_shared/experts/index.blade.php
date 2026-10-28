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
@php
  // 25 Ekim 2026: arama motoru düzeni - asıl adres her zaman bakimevleri.com; il/ilçe/branş
  // açılış sayfaları kendi başlığını taşır; sorgu parametreli filtre sonuçları açılış
  // sayfalarının kopyası olduğu için dizine alınmaz (bağlantılar izlenir).
  $seo = $seo ?? [];
  if ($seo) {
      $__title = $seo['title'];
      $__description = $seo['aciklama'];
      $__kanonik = $seo['kanonik'];
  } elseif ($activeExpert) {
      $__kanonik = \App\Support\DoktorSeo::asilAdres('/doktor/'.$activeExpert->slug);
  } else {
      $__kanonik = \App\Support\DoktorSeo::asilAdres('/uzmanlar');
  }
  $__robots = ! $publiclyVisible ? 'noindex,nofollow' : (($seo || $activeExpert || ! $hasFilter) ? 'index,follow' : 'noindex,follow');
@endphp
@section('title', $__title)
@section('meta_description', $__description)
@section('canonical', $__kanonik)
@section('robots_meta', $__robots)

@section('content')
{{-- 25 Ekim 2026: kullanıcının talebi - sade arka plan yerine doktorlar bölümüne uygun, hafif
     sağlık temalı zemin (yumuşak turkuaz geçiş + çok soluk artı / nabız deseni). Inline stil. --}}
<div style="background-color:#f4faf9;background-image:radial-gradient(ellipse 60% 45% at 8% 0%, rgba(13,148,136,.16), transparent 70%),radial-gradient(ellipse 50% 40% at 100% 18%, rgba(37,99,235,.10), transparent 70%),radial-gradient(ellipse 55% 40% at 50% 100%, rgba(13,148,136,.10), transparent 70%),url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='120'%20height='120'%20viewBox='0%200%20120%20120'%3E%3Cpath%20d='M18%2012h4v6h6v4h-6v6h-4v-6h-6v-4h6z'%20fill='%230d9488'%20fill-opacity='0.07'/%3E%3Cpath%20d='M80%2070h3v5h5v3h-5v5h-3v-5h-5v-3h5z'%20fill='%232563eb'%20fill-opacity='0.06'/%3E%3Cpath%20d='M0%20100h34l6-12%208%2022%207-16%204%206h61'%20fill='none'%20stroke='%230d9488'%20stroke-opacity='0.06'%20stroke-width='2'%20stroke-linejoin='round'/%3E%3C/svg%3E&quot;),linear-gradient(180deg,#e9f6f4 0%,#f4faf9 35%,#ffffff 100%);background-repeat:no-repeat,no-repeat,no-repeat,repeat,no-repeat;min-height:70vh;margin-bottom:-4rem;padding-bottom:4rem;">
<div class="max-w-5xl mx-auto px-4 py-8">

  @unless($publiclyVisible)
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 text-sm font-semibold px-4 py-3">
      🔒 Test sayfası — henüz hiçbir menüde linklenmiyor, arama motorlarına kapalı (noindex). Sadece bu linki bilenler görebilir.
    </div>
  @endunless

  {{-- 28 Ekim 2026: kullanıcının talebi - TTB md. 5 cümlesi doktorun kendi sitesi içindir; rehber sayfasında bu metin. --}}
  @include('themes._shared.partials.hekim-uyari', ['metin' => 'Sayfa içeriği sadece bilgilendirme amaçlıdır, reklam ve yönlendirme kesinlikle yasaktır.'])

  {{-- 25 Ekim 2026: bölüm kartları bu sayfada da görünsün (bölüm yayındayken). --}}
  @if(doctors_live())@include('themes._shared.partials.bolum-kartlari-ust')@endif

  @if($seo)
    <nav aria-label="Konum" style="font-size:13px;color:#6b7280;margin-bottom:10px;display:flex;flex-wrap:wrap;gap:4px;">
      @foreach($seo['kirinti'] as $k)
        @if(! $loop->last)<a href="{{ $k['url'] }}" style="color:#0d6e6a;text-decoration:none;font-weight:600;">{{ $k['ad'] }}</a><span aria-hidden="true">›</span>@else<span>{{ $k['ad'] }}</span>@endif
      @endforeach
    </nav>
    <h1 class="text-2xl font-black text-gray-950 mb-2">{{ $seo['h1'] }}</h1>
    <p class="text-gray-600 mb-6" style="max-width:820px;line-height:1.65;">{{ $seo['giris'] }}</p>
  @else
    <h1 class="text-2xl font-black text-gray-950 mb-2">Özel Doktorlar</h1>
    <p class="text-gray-500 mb-6">Çocuk ve yaşlı sağlığı alanında kendi muayenehanesinde çalışan özel doktorları bulun.</p>
  @endif

  <form method="GET" action="{{ brand_route('experts.index') }}" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-expert-results" data-count-target="js-expert-count" class="js-location-filter flex flex-wrap gap-3 mb-6" style="background:rgba(255,255,255,.92);border:1px solid #d5ebe8;border-radius:16px;padding:12px;box-shadow:0 6px 20px -12px rgba(13,110,106,.35);">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Doktor adı veya branş ile ara..." autocomplete="off" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[180px]" style="max-width:100%;">
    <select name="city" class="js-city border rounded-lg px-3 py-2 text-sm" style="max-width:100%;min-width:0;text-overflow:ellipsis;">
      <option value="">Tüm İller</option>
      @foreach($cities as $city)
        <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
      @endforeach
    </select>
    <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2 text-sm" style="max-width:100%;min-width:0;text-overflow:ellipsis;" @if(! request('city')) disabled @endif>
      <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
      @if(request('city'))
        @foreach($districtMap[request('city')] ?? [] as $districtName)
          <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
        @endforeach
      @endif
    </select>
    <select name="branch" class="border rounded-lg px-3 py-2 text-sm" style="max-width:100%;min-width:0;text-overflow:ellipsis;">
      <option value="">Tüm Branşlar</option>
      @foreach($branches as $slug => $b)
        <option value="{{ $slug }}" @selected(request('branch') === $slug)>{{ $b['title'] }} ({{ $b['label'] }})</option>
      @endforeach
    </select>
    @if(doctor_reviews_visible())
    {{-- 7 Ekim 2026: kullanicinin talebi - Google puanina gore filtre. --}}
    <select name="min_rating" class="border rounded-lg px-3 py-2 text-sm" style="max-width:100%;min-width:0;text-overflow:ellipsis;">
      <option value="">Tüm Puanlar</option>
      <option value="4.5" @selected(request('min_rating') === '4.5')>★ 4.5 ve üzeri</option>
      <option value="4" @selected(request('min_rating') === '4')>★ 4.0 ve üzeri</option>
      <option value="3.5" @selected(request('min_rating') === '3.5')>★ 3.5 ve üzeri</option>
      <option value="3" @selected(request('min_rating') === '3')>★ 3.0 ve üzeri</option>
    </select>
    @endif
    @if($hasFilter)
      <a href="{{ request()->url() }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
    @endif
  </form>

  <p class="text-sm text-gray-500 mb-3 {{ $hasFilter ? '' : 'hidden' }}" id="js-expert-count-wrap"><span id="js-expert-count">{{ $experts?->total() ?? 0 }}</span> sonuç</p>

  <div id="js-expert-results">
    @include('themes._shared.experts._results', ['experts' => $experts, 'activeExpertSlug' => $activeExpertSlug])
  </div>

  {{-- 25 Ekim 2026: arama motoru sayfaları - ilgili ilçe / branş bağlantıları ve sık sorulan sorular. --}}
  @php $linkKutu = 'display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #d5ebe8;border-radius:999px;padding:7px 13px;font-size:13.5px;font-weight:700;color:#0f172a;text-decoration:none;'; @endphp
  @if($seo)
    <div style="margin-top:28px;display:grid;gap:18px;">
      @if($seo['bransLinkleri']->isNotEmpty())
        <section style="background:rgba(255,255,255,.9);border:1px solid #d5ebe8;border-radius:16px;padding:16px;">
          <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">Bu bölgedeki diğer branşlar</h2>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">@foreach($seo['bransLinkleri'] as $l)<a href="{{ $l['url'] }}" style="{{ $linkKutu }}">{{ $l['ad'] }} <span style="color:#0d9488;">{{ $l['adet'] }}</span></a>@endforeach</div>
        </section>
      @endif
      @if($seo['ilceLinkleri']->isNotEmpty())
        <section style="background:rgba(255,255,255,.9);border:1px solid #d5ebe8;border-radius:16px;padding:16px;">
          <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">{{ $seo['tumIl'] ? 'Yakın ilçeler' : 'İlçelere göre' }}</h2>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">@foreach($seo['ilceLinkleri'] as $l)<a href="{{ $l['url'] }}" style="{{ $linkKutu }}">{{ $l['ad'] }} <span style="color:#0d9488;">{{ $l['adet'] }}</span></a>@endforeach</div>
          @if($seo['tumIl'])<a href="{{ $seo['tumIl']['url'] }}" style="display:inline-block;margin-top:12px;font-weight:800;color:#0d6e6a;font-size:14px;">Tüm {{ $seo['tumIl']['ad'] }} →</a>@endif
        </section>
      @endif
      <section style="background:rgba(255,255,255,.9);border:1px solid #d5ebe8;border-radius:16px;padding:16px;">
        <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:6px;">Sık sorulan sorular</h2>
        @foreach($seo['sss'] as $s)
          <details style="border-top:1px solid #eef2f2;padding:10px 0;" @if($loop->first) open @endif>
            <summary style="cursor:pointer;font-weight:800;color:#0f172a;font-size:15px;">{{ $s['soru'] }}</summary>
            <p style="margin-top:6px;color:#475569;font-size:14.5px;line-height:1.6;">{{ $s['cevap'] }}</p>
          </details>
        @endforeach
      </section>
    </div>
  @elseif(! $hasFilter && ($populer->isNotEmpty() || $populerIller->isNotEmpty()))
    <div style="margin-top:28px;display:grid;gap:18px;">
      @if($populer->isNotEmpty())
        <section style="background:rgba(255,255,255,.9);border:1px solid #d5ebe8;border-radius:16px;padding:16px;">
          <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">Popüler aramalar</h2>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($populer as $p)<a href="{{ brand_route('experts.landing', ['il' => $p['il_slug'], 'a' => $p['ilce_slug'], 'b' => $p['brans_slug']]) }}" style="{{ $linkKutu }}">{{ $p['ilce'] }} {{ \App\Support\DoktorSeo::BRANSLAR[$p['brans_slug']]['cogul'] }} <span style="color:#0d9488;">{{ $p['toplam'] }}</span></a>@endforeach
          </div>
        </section>
      @endif
      @if($populerIller->isNotEmpty())
        <section style="background:rgba(255,255,255,.9);border:1px solid #d5ebe8;border-radius:16px;padding:16px;">
          <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">İllere göre özel doktorlar</h2>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($populerIller as $p)<a href="{{ brand_route('experts.landing', ['il' => $p['il_slug']]) }}" style="{{ $linkKutu }}">{{ $p['il_ad'] }} <span style="color:#0d9488;">{{ $p['toplam'] }}</span></a>@endforeach
          </div>
        </section>
      @endif
    </div>
  @endif

  {{-- 25 Ekim 2026: kullanıcının talebi - hoş geldiniz yazısının altında bilgilendirme ve
       İyilik Hareketi destekçisi hekimlere teşekkür, alt alta. Teşekkür listesi bilerek sade:
       yalnızca unvan, isim, şehir; fotoğraf, telefon, bağlantı yok (reklam yasağı). --}}
  <div style="margin-top:28px;">
  <div role="note" style="margin-bottom:12px;border:1px solid #cfe3e1;background:#f3faf9;border-radius:14px;padding:14px 16px;color:#1f2937;font-size:14px;line-height:1.6;">
    <div style="font-weight:800;color:#0d6e6a;margin-bottom:4px;">ℹ️ Bilgilendirme</div>
    Bu sayfada yer alan hekim bilgileri, yalnızca herkese açık kaynaklardaki güncel bilgilerden derlenmiştir. Amacımız, ailelerin aradıkları hekime daha kolay ve kısa sürede ulaşabilmesine yardımcı olmaktır. Bu bölümde hiçbir hekimin reklamı yapılmamaktadır; sıralama alfabetiktir ve hiçbir hekim öne çıkarılmaz. Bilgilerinizde düzeltme ya da kaldırma talebi için <a href="{{ route('contact.create') }}" style="color:#0d6e6a;font-weight:700;text-decoration:underline;">bize ulaşabilirsiniz</a>.
  </div>
  {{-- 28 Ekim 2026: İyilik Hareketi destekçi hekim teşekkür listesi kaldırıldı - TTB md. 13:
       hekimler aracılık yapan platformlara üye olamaz; üyelik/destekçilik izlenimi verilmez. --}}
  </div>

  {{-- 7 Ekim 2026: kullanicinin bildirdigi gercek hata - "filtreleme hic
  calismiyor". Kok neden: bu script document.getElementById('js-expert-results')
  cagirisini TARAYICI o div'i DAHA PARSE ETMEDEN (script etiketi HTML
  kaynaginda div'den ONCEYDI) senkron calistiriyordu - resultsEl hep null
  kaliyor, runFilter() sessizce hicbir sey yapmadan donuyordu (hata da
  vermiyordu). Script artik sonuc div'inden SONRA calisir. --}}
  @include('themes._shared.partials.location-filter-script')
</div>
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
  'url' => \App\Support\DoktorSeo::asilAdres('/doktor/'.$activeExpert->slug),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@if($seo)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($seo['kirinti'])->values()->map(fn ($k, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $k['ad'], 'item' => $k['asil']])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($seo['sss'])->map(fn ($s) => ['@type' => 'Question', 'name' => $s['soru'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $s['cevap']]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@if(! $activeExpert && $experts && $experts->count())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $experts->values()->map(function ($expert, $index) {
      return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'name' => trim($expert->title.' '.$expert->name),
          'url' => \App\Support\DoktorSeo::asilAdres('/doktor/'.$expert->slug),
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
