@extends('layouts.brand')

{{-- 25 Ekim 2026: kullanıcının talebi - Doktorlar bölümünde maksimum görünürlük: her doktorun
     kendi adresi (/doktor/{slug}). Bilgiler herkese açık kaynaklardan; üstünlük iddiası yok,
     sıralama/öne çıkarma yok (hekim reklam yasağı). Asıl adres bakimevleri.com. --}}
@php
  $e = $expert;
  $adSoyad = trim($e->title.' '.$e->name);
  $yer = trim(($e->district ? $e->district.', ' : '').($city->name ?? ''), ', ');
  $etiket = $e->branch_label ?: $e->branch;
  $__title = $adSoyad.' – '.trim(($e->district ?: ($city->name ?? '')).' '.($etiket ?: 'Doktor')).($city ? ' | '.$city->name : '');
  $__description = \Illuminate\Support\Str::limit(trim($adSoyad.', '.($yer ? $yer.' bölgesinde ' : '').($e->branch ? $e->branch.($e->branch_label ? ' ('.$e->branch_label.')' : '').' alanında' : '').' hizmet veriyor. Klinik, adres, telefon ve yol tarifi bilgileri.'), 158);
  $telLink = $e->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $e->phone) : null;
  $harita = $e->google_maps_url ?: ($e->lat && $e->lng ? 'https://www.google.com/maps?q='.$e->lat.','.$e->lng : null);
  $kart = 'background:rgba(255,255,255,.94);border:1px solid #d5ebe8;border-radius:18px;padding:18px;';
  $bilgi = 'background:#fff;border:1px solid #eef2f2;border-radius:12px;padding:10px 12px;';
  $link = 'display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #d5ebe8;border-radius:999px;padding:7px 13px;font-size:13.5px;font-weight:700;color:#0f172a;text-decoration:none;';
@endphp
@section('title', $__title)
@section('meta_description', $__description)
@section('canonical', $kanonik)
@section('robots_meta', $publiclyVisible ? 'index,follow' : 'noindex,nofollow')

@section('content')
<div style="background-color:#f4faf9;background-image:radial-gradient(ellipse 60% 45% at 8% 0%, rgba(13,148,136,.14), transparent 70%),linear-gradient(180deg,#e9f6f4 0%,#f4faf9 35%,#ffffff 100%);min-height:70vh;margin-bottom:-4rem;padding-bottom:4rem;">
<div class="max-w-5xl mx-auto px-4 py-8">
  <nav aria-label="Konum" style="font-size:13px;color:#6b7280;margin-bottom:12px;display:flex;flex-wrap:wrap;gap:4px;">
    @foreach($kirinti as $k)
      @if(! $loop->last)<a href="{{ $k['url'] }}" style="color:#0d6e6a;text-decoration:none;font-weight:600;">{{ $k['ad'] }}</a><span aria-hidden="true">›</span>@else<span>{{ $k['ad'] }}</span>@endif
    @endforeach
  </nav>

  <div style="{{ $kart }}display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;">
    <div style="flex:none;height:64px;min-width:64px;padding:0 10px;border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px;letter-spacing:.04em;background:{{ $e->titleRankColor() }};">{{ $e->titleAbbreviation() }}</div>
    <div style="flex:1 1 260px;min-width:0;">
      <h1 style="font-size:clamp(22px,3.4vw,30px);font-weight:900;color:#0f172a;line-height:1.2;overflow-wrap:anywhere;">{{ $adSoyad }}</h1>
      <p style="color:#475569;margin-top:6px;font-size:15px;">{{ $e->branch }}@if($e->branch_label) <span style="color:#94a3b8;">({{ $e->branch_label }})</span>@endif @if($yer) · {{ $yer }}@endif</p>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">
        @if($e->rating)<span style="font-size:13px;font-weight:800;color:#b45309;background:#fffbeb;border-radius:999px;padding:4px 10px;">Google’da ★ {{ number_format($e->rating, 1, ',', '') }}@if($e->review_count) ({{ $e->review_count }} yorum)@endif</span>@endif
        @if($e->isSupporter())<span style="font-size:12px;font-weight:800;color:#be185d;background:#fdf2f8;border:1px solid #fbcfe8;border-radius:999px;padding:4px 10px;">💗 İyilik Hareketi Destekçisi</span>@endif
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:16px;">
        @if($telLink)<a href="{{ $telLink }}" style="display:inline-flex;align-items:center;gap:8px;background:#16a34a;color:#fff;font-weight:900;border-radius:12px;padding:11px 18px;text-decoration:none;">📞 {{ $e->phone }}</a>@endif
        @if($harita)<a href="{{ $harita }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0f172a;font-weight:800;border:1.5px solid #d5ebe8;border-radius:12px;padding:10px 16px;text-decoration:none;">📍 Yol tarifi</a>@endif
        @if($siteAdresi)<a href="{{ $siteAdresi }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0d6e6a;font-weight:800;border:1.5px solid #0d9488;border-radius:12px;padding:10px 16px;text-decoration:none;">🌐 Web sitesi</a>@endif
      </div>
    </div>
  </div>

  <div style="display:grid;gap:16px;margin-top:16px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <section style="{{ $kart }}">
      <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">Muayenehane bilgileri</h2>
      <div style="display:grid;gap:8px;font-size:14.5px;">
        @if($e->clinic_name)<div style="{{ $bilgi }}"><span style="display:block;font-size:12px;color:#94a3b8;">Klinik / Hastane</span><b style="color:#0f172a;">{{ $e->clinic_name }}</b></div>@endif
        @if($e->address)<div style="{{ $bilgi }}"><span style="display:block;font-size:12px;color:#94a3b8;">Adres</span><b style="color:#0f172a;">{{ $e->address }}</b></div>@endif
        @if($yer)<div style="{{ $bilgi }}"><span style="display:block;font-size:12px;color:#94a3b8;">İlçe / İl</span><b style="color:#0f172a;">{{ $yer }}</b></div>@endif
        @if($e->phone)<div style="{{ $bilgi }}"><span style="display:block;font-size:12px;color:#94a3b8;">Telefon</span><b style="color:#0f172a;">{{ $e->phone }}</b></div>@endif
      </div>
      @if($e->description)<p style="margin-top:12px;color:#334155;font-size:14.5px;line-height:1.65;">{{ $e->description }}</p>@endif
    </section>

    <section style="{{ $kart }}">
      <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">{{ $e->branch ?: 'Bu alanda' }} — hangi durumlarda başvurulur?</h2>
      <ul style="margin:0;padding:0;list-style:none;display:grid;gap:8px;">
        @foreach(array_slice($durumlar, 0, 6) as [$baslik, $metin])
          <li style="font-size:14px;color:#334155;line-height:1.5;"><b style="color:#0f172a;">{{ $baslik }}:</b> {{ $metin }}</li>
        @endforeach
      </ul>
      <p style="margin-top:10px;font-size:12.5px;color:#64748b;">Genel bilgilendirmedir; tanı ve tedavi için muayene gerekir.</p>
    </section>
  </div>

  @if($e->reviews && count($e->reviews))
    <section style="{{ $kart }}margin-top:16px;">
      <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center;margin-bottom:8px;">
        <h2 style="font-size:17px;font-weight:900;color:#0f172a;">Google’daki son yorumlar</h2>
        @if($e->google_maps_url)<a href="{{ $e->google_maps_url }}" target="_blank" rel="noopener" style="font-size:13px;font-weight:800;color:#0d6e6a;">Tüm yorumlar →</a>@endif
      </div>
      <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
        @foreach(array_slice($e->reviews, 0, 6) as $r)
          @if(! empty($r['body']))
            <div style="{{ $bilgi }}">
              <div style="display:flex;justify-content:space-between;gap:8px;font-size:12.5px;"><b style="color:#0f172a;">{{ $r['author_name'] ?? 'Google Kullanıcısı' }}</b><span style="color:#94a3b8;">{{ $r['relative_time_text'] ?? '' }}</span></div>
              @if(! empty($r['rating']))<div style="color:#f59e0b;font-size:12px;">{{ str_repeat('★', (int) $r['rating']) }}{{ str_repeat('☆', 5 - (int) $r['rating']) }}</div>@endif
              <p style="font-size:14px;color:#475569;margin-top:4px;line-height:1.55;overflow-wrap:anywhere;">{{ \Illuminate\Support\Str::limit($r['body'], 320) }}</p>
            </div>
          @endif
        @endforeach
      </div>
    </section>
  @endif

  @if($e->lat && $e->lng)
    <section style="{{ $kart }}margin-top:16px;padding:8px;">
      <iframe title="{{ $adSoyad }} konum" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q={{ $e->lat }},{{ $e->lng }}&z=15&output=embed" style="width:100%;height:300px;border:0;border-radius:12px;display:block;"></iframe>
    </section>
  @endif

  @if($ayniYer->isNotEmpty() || $city)
    <section style="{{ $kart }}margin-top:16px;">
      <h2 style="font-size:17px;font-weight:900;color:#0f172a;margin-bottom:10px;">{{ $brans ? ($e->district ?: $city->name).' bölgesindeki diğer '.mb_strtolower($brans['cogul'], 'UTF-8') : 'Aynı bölgedeki diğer doktorlar' }}</h2>
      @if($ayniYer->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:8px;">@foreach($ayniYer as $d)<a href="{{ brand_route('experts.show', $d->slug) }}" style="{{ $link }}">{{ trim($d->title.' '.$d->name) }}</a>@endforeach</div>
      @endif
      <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:12px;font-size:14px;font-weight:800;">
        @if($city && $ilceSlug && $bransSlug)<a href="{{ brand_route('experts.landing', ['il' => $city->slug, 'a' => $ilceSlug, 'b' => $bransSlug]) }}" style="color:#0d6e6a;">{{ $e->district }} {{ $brans['cogul'] }} →</a>@endif
        @if($city && $bransSlug)<a href="{{ brand_route('experts.landing', ['il' => $city->slug, 'a' => $bransSlug]) }}" style="color:#0d6e6a;">{{ $city->name }} {{ $brans['cogul'] }} →</a>@endif
        @if($city && $ilceSlug)<a href="{{ brand_route('experts.landing', ['il' => $city->slug, 'a' => $ilceSlug]) }}" style="color:#0d6e6a;">{{ $e->district }} özel doktorları →</a>@endif
      </div>
    </section>
  @endif

  <div role="note" style="margin-top:16px;border:1px solid #cfe3e1;background:#f3faf9;border-radius:14px;padding:12px 16px;color:#1f2937;font-size:13.5px;line-height:1.6;">
    ℹ️ Bu sayfadaki bilgiler herkese açık güncel kaynaklardan derlenmiştir ve yalnızca ailelerin aradıkları hekime kolayca ulaşması amacını taşır; reklam değildir. Düzeltme ya da kaldırma talebi için <a href="{{ route('contact.create') }}" style="color:#0d6e6a;font-weight:700;text-decoration:underline;">bize ulaşabilirsiniz</a>.
  </div>
</div>
</div>

<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org',
  '@type' => 'Physician',
  'name' => $adSoyad,
  'url' => $kanonik,
  'medicalSpecialty' => $e->branch ?: null,
  'telephone' => $e->phone ?: null,
  'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $e->address, 'addressLocality' => $e->district, 'addressRegion' => $city?->name, 'addressCountry' => 'TR']),
  'geo' => ($e->lat && $e->lng) ? ['@type' => 'GeoCoordinates', 'latitude' => $e->lat, 'longitude' => $e->lng] : null,
  'areaServed' => $yer ?: null,
  'sameAs' => array_values(array_filter([$siteAdresi])) ?: null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($kirinti)->values()->map(fn ($k, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $k['ad'], 'item' => $k['asil']])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
