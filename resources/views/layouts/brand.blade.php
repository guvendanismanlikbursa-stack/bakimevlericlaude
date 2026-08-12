<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', $brand['tagline']) · {{ $brand['name'] }}</title>
<meta name="description" content="@yield('meta_description', $brand['tagline'])">
<link rel="canonical" href="@yield('canonical', canonical_url())">
{{--
  12 Agustos 2026: kullanicinin talebi - 3 marka ayni veritabanini/ayni
  kurum envanterini paylastigi icin (category_scope hepsinde full), bir
  markanin KENDI varsayilan bolumu DISINDAKI il/ilce rehberi, fiyat
  rehberi ve kurum listeleme sayfalari, DIGER bir markada BIREBIR AYNI
  kurum listesiyle de erisilebilir - bu "3 farkli sitede 3 farkli bolumu
  index'letme" stratejisiyle dogrudan celisen bir duplicate content
  riski. Sitemap'ten cikarmak tek basina yetmez (disaridan link/manuel
  ziyaretle Google yine bulup indeksleyebilir) - bu yuzden asil/otoriter
  koruma budur: o sayfa o markanin kendi bolumu DEGILSE noindex,follow
  gonderilir (kullanicilar yine gezebilir, sadece Google o kopyayi
  indekslemez - bkz. themes._shared.location-guide/price-guide/facilities/index).
--}}
<meta name="robots" content="@yield('robots_meta', 'index,follow')">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo-'.$brand['slug'].'-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo-'.$brand['slug'].'-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-'.$brand['slug'].'-180.png') }}">
<link rel="manifest" href="{{ brand_route('manifest') }}">
<meta name="theme-color" content="{{ $brand['primary_color'] }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $brand['name'] }}">
<meta property="og:title" content="@yield('og_title', $brand['tagline'])">
<meta property="og:description" content="@yield('meta_description', $brand['tagline'])">
<meta property="og:url" content="@yield('canonical', canonical_url())">
<meta property="og:image" content="@yield('og_image', seo_og_image())">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('og_title', $brand['tagline'])">
<meta name="twitter:description" content="@yield('meta_description', $brand['tagline'])">
<meta name="twitter:image" content="@yield('og_image', seo_og_image())">
@php
  $brandSlug = $brand['slug'] ?? null;
  $ga4MeasurementId = $brandSlug ? (config('services.google_analytics.ids.'.$brandSlug) ?: config('services.google_analytics.id')) : config('services.google_analytics.id');
  $gscVerification = $brandSlug ? config('services.google_search_console.verification.'.$brandSlug) : null;
@endphp
@if($gscVerification)
<meta name="google-site-verification" content="{{ $gscVerification }}">
@endif
{{--
  12 Agustos 2026: kullanicinin talebi - onceki font-yukleme hatasi
  duzeltildikten sonra, "yazi tipi kimligi" raporundaki C secenegi
  (her markada baslik+govde font ikilisi) uygulanmaktadir:
  - bakimevleri (kurumsal/guven): baslik Fraunces (yumusak serif),
    govde IBM Plex Sans.
  - bakimevibul (sicak/samimi): tek font Plus Jakarta Sans (hem
    baslik hem govde) - raporda tek font onerilmisti.
  - bakimeviara (ozenli/dergisel): baslik Newsreader (serif), govde
    Manrope.
  Baslik fontu SADECE gercek baslik etiketlerine (h1-h4) uygulanir,
  govde fontu geri kalan her seye (p, span, div, buton vb.) miras
  kalir - boylece tek satirlik metin/rozet gibi kucuk UI parcalari
  govde fontunda okunakli kalir, sadece buyuk basliklar karakter kazanir.
--}}
@php
  $fontConfig = [
    'bakimevleri' => ['display' => "'Fraunces', serif", 'body' => "'IBM Plex Sans', sans-serif", 'google' => 'family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,900&family=IBM+Plex+Sans:wght@400;500;600;700'],
    'bakimevibul' => ['display' => "'Plus Jakarta Sans', sans-serif", 'body' => "'Plus Jakarta Sans', sans-serif", 'google' => 'family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900'],
    'bakimeviara' => ['display' => "'Newsreader', serif", 'body' => "'Manrope', sans-serif", 'google' => 'family=Newsreader:wght@500;600;700&family=Manrope:wght@400;500;600;700;800'],
  ];
  $fonts = $fontConfig[$brand['theme']] ?? $fontConfig['bakimevibul'];
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $fonts['google'] }}&display=swap">
@vite('resources/css/app.css')
<style>
  :root{ --primary: {{ $brand['primary_color'] }}; --secondary: {{ $brand['secondary_color'] }}; }
  body{ font-family: {{ $fonts['body'] }}; }
  h1, h2, h3, h4{ font-family: {{ $fonts['display'] }}; }
  .btn-primary{ background-color: var(--primary); color:#fff; }
  .btn-primary:hover{ filter: brightness(1.08); }
  .text-primary{ color: var(--primary); }
  .bg-primary{ background-color: var(--primary); }
  .border-primary{ border-color: var(--primary); }
  .badge-secondary{ background-color: var(--secondary); }
</style>
@if($ga4MeasurementId)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', @json($ga4MeasurementId), {
    page_title: document.title,
    page_location: window.location.href,
    page_path: window.location.pathname,
    send_page_view: true
  });

  (function () {
    var startedAt = Date.now();
    var maxScroll = 0;
    var sentScrollDepths = {};
    var pageMeta = {
      brand: @json($brand['slug']),
      page_path: window.location.pathname,
      page_title: document.title
    };

    function sendEvent(name, params) {
      if (typeof gtag !== 'function') return;
      gtag('event', name, Object.assign({}, pageMeta, params || {}));
    }

    function currentScrollPercent() {
      var doc = document.documentElement;
      var body = document.body;
      var scrollTop = window.scrollY || doc.scrollTop || body.scrollTop || 0;
      var scrollHeight = Math.max(body.scrollHeight, doc.scrollHeight, body.offsetHeight, doc.offsetHeight, body.clientHeight, doc.clientHeight);
      var viewport = window.innerHeight || doc.clientHeight || 0;
      var available = Math.max(1, scrollHeight - viewport);
      return Math.min(100, Math.round((scrollTop / available) * 100));
    }

    function checkScrollDepth() {
      maxScroll = Math.max(maxScroll, currentScrollPercent());
      [25, 50, 75, 90].forEach(function (depth) {
        if (maxScroll >= depth && !sentScrollDepths[depth]) {
          sentScrollDepths[depth] = true;
          sendEvent('scroll_depth', { percent_scrolled: depth });
        }
      });
    }

    function sendTimeOnPage(reason) {
      var seconds = Math.max(1, Math.round((Date.now() - startedAt) / 1000));
      sendEvent('time_on_page', {
        engagement_time_seconds: seconds,
        max_scroll_percent: maxScroll,
        event_reason: reason || 'pagehide',
        transport_type: 'beacon'
      });
    }

    window.addEventListener('scroll', checkScrollDepth, { passive: true });
    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('a[href]');
      if (!link) return;
      var url = new URL(link.href, window.location.href);
      sendEvent(url.hostname === window.location.hostname ? 'internal_link_click' : 'outbound_link_click', {
        link_url: url.href,
        link_text: (link.textContent || '').trim().slice(0, 120)
      });
    }, true);
    document.addEventListener('focusin', function (event) {
      var form = event.target && event.target.closest && event.target.closest('form');
      if (!form || form.dataset.gaFormStarted === '1') return;
      form.dataset.gaFormStarted = '1';
      sendEvent('form_start', { form_action: form.getAttribute('action') || window.location.pathname, form_method: form.getAttribute('method') || 'GET' });
    });
    document.addEventListener('submit', function (event) {
      var form = event.target;
      sendEvent('form_submit_attempt', { form_action: form.getAttribute('action') || window.location.pathname, form_method: form.getAttribute('method') || 'GET' });
    }, true);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') sendTimeOnPage('visibility_hidden');
    });
    window.addEventListener('pagehide', function () { sendTimeOnPage('pagehide'); });
    window.addEventListener('load', checkScrollDepth);
  })();
</script>
@endif
@if(config('services.meta_pixel.id'))
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', @json(config('services.meta_pixel.id')));
  fbq('track', 'PageView');
</script>
@endif
</head>
@php
  $theme = $brand['theme'];
  $bodyClass = $theme === 'bakimevleri' ? 'bg-gray-100 text-gray-800' : ($theme === 'bakimeviara' ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-800');
  // 12 Agustos 2026: kullanicinin talebi - kafa karistirici bir kesif:
  // "Secim Asistani/Karar Sihirbazi/Baslar" linkleri HER SAYFADA markanin
  // SABIT varsayilan bolumune gidiyordu, kullanicinin O AN gezindigi
  // bolumu (ör. "Cocuk") hic dikkate almadan - bir aile "Cocuk" bakim
  // sayfasindayken bu linke tiklarsa kendini "Yasli Bakim" sihirbazinda
  // buluyordu. Artik varsa mevcut sayfanin bolumunu ($activeSection ana
  // sayfa/kurum listesinde, $serviceSection kurum detay sayfasinda) kullanir,
  // yoksa (ör. iletisim/SSS gibi bolum-bagimsiz sayfalarda) markanin
  // varsayilanina duser.
  $defaultSection = ($activeSection['slug'] ?? null) ?? ($serviceSection['slug'] ?? null) ?? ($brand['default_section'] ?? array_key_first(service_sections()));
@endphp
<body class="{{ $bodyClass }}">

@if(session('impersonator_admin_id'))
<div class="bg-amber-500 text-amber-950 text-sm font-semibold px-4 py-2 flex items-center justify-between gap-3 flex-wrap sticky top-0 z-50">
  <span>⚠ Şu an <strong>{{ session('facility_user_name') ?? session('family_user_name') ?? 'bu kullanıcı' }}</strong> adına, admin olarak görüntülüyorsunuz.</span>
  <form method="POST" action="{{ route('impersonation.stop') }}" class="m-0">
    @csrf
    <button class="bg-amber-950 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Admin Paneline Dön</button>
  </form>
</div>
@endif

@if($theme === 'bakimevleri')
<header class="bg-gray-950 text-white sticky top-0 z-30 border-b border-white/10">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
    <a href="{{ brand_route('home') }}" class="flex items-center gap-2 text-xl font-black tracking-tight">
      <img src="{{ asset('images/logo-'.$brand['slug'].'-64.png') }}" alt="{{ $brand['name'] }}" class="w-9 h-9 flex-shrink-0">
      <span>{{ $brand['logo_text'] }}</span>
    </a>
    <nav class="hidden lg:flex gap-5 text-sm font-semibold text-white/78">
      <a href="{{ brand_route('home') }}" class="hover:text-white">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="hover:text-white">Seçim Asistanı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="hover:text-white">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="hover:text-white">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="hover:text-white">Kurumları Bul</a>
      <a href="{{ brand_route('contact.create') }}" class="hover:text-white">İletişim</a>
    </nav>
    <div class="flex items-center gap-3 text-sm">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="font-semibold text-white/80 hover:text-white hidden sm:inline">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="font-semibold text-white/80 hover:text-white hidden sm:inline">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="font-semibold text-white/80 hover:text-white hidden sm:inline">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="font-semibold text-white/80 hover:text-white hidden sm:inline">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="relative font-semibold text-white/80 hover:text-white hidden sm:inline">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}" class="hidden sm:inline">@csrf<button class="font-semibold text-white/80 hover:text-white">Çıkış Yap</button></form>
      @endif
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="text-sm px-4 py-2 rounded-md font-black" style="background: {{ $brand['secondary_color'] }}; color:#fff;">Başla</a>
      <button type="button" id="js-mobile-menu-toggle" class="lg:hidden text-white text-2xl leading-none px-1" aria-label="Menüyü aç">&#9776;</button>
    </div>
  </div>
  <div id="js-mobile-menu" class="hidden lg:hidden bg-gray-950 border-t border-white/10">
    <nav class="max-w-6xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm font-semibold text-white/80">
      <a href="{{ brand_route('home') }}" class="py-2 hover:text-white">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="py-2 hover:text-white">Seçim Asistanı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="py-2 hover:text-white">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="py-2 hover:text-white">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="py-2 hover:text-white">Kurumları Bul</a>
      <a href="{{ brand_route('contact.create') }}" class="py-2 hover:text-white">İletişim</a>
      <hr class="border-white/10 my-1">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="py-2 hover:text-white">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="py-2 hover:text-white">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="py-2 hover:text-white">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="py-2 hover:text-white">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="py-2 hover:text-white">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        @if(session('facility_user_id'))<a href="{{ brand_route('facility.profile.edit') }}" class="py-2 hover:text-white">Profili Düzenle</a>@else<a href="{{ brand_route('family.profile.edit') }}" class="py-2 hover:text-white">Hesap Bilgilerim</a>@endif
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}">@csrf<button class="py-2 hover:text-white">Çıkış Yap</button></form>
      @endif
    </nav>
  </div>
</header>
@elseif($theme === 'bakimeviara')
<header class="bg-white/92 backdrop-blur sticky top-0 z-30 border-b border-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
    <a href="{{ brand_route('home') }}" class="flex items-center gap-2 text-xl font-black rounded-full pl-1.5 pr-3 py-1" style="color: {{ $brand['primary_color'] }}; background: {{ $brand['secondary_color'] }}22;">
      <img src="{{ asset('images/logo-'.$brand['slug'].'-64.png') }}" alt="{{ $brand['name'] }}" class="w-8 h-8 flex-shrink-0">
      <span>{{ $brand['logo_text'] }}</span>
    </a>
    <nav class="hidden lg:flex gap-1 text-sm font-bold bg-gray-50 rounded-full p-1">
      <a href="{{ brand_route('home') }}" class="px-3 py-2 rounded-full hover:bg-white">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="px-3 py-2 rounded-full hover:bg-white">Aile Sihirbazı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="px-3 py-2 rounded-full hover:bg-white">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="px-3 py-2 rounded-full hover:bg-white">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="px-3 py-2 rounded-full hover:bg-white">Kurumlar</a>
    </nav>
    <div class="flex items-center gap-3 text-sm">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="font-bold hover:text-primary hidden sm:inline">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="font-bold hover:text-primary hidden sm:inline">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="font-bold hover:text-primary hidden sm:inline">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="font-bold hover:text-primary hidden sm:inline">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="relative font-bold hover:text-primary hidden sm:inline">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}" class="hidden sm:inline">@csrf<button class="font-bold hover:text-primary">Çıkış Yap</button></form>
      @endif
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="btn-primary text-sm px-4 py-2 rounded-full font-black">Başla</a>
      <button type="button" id="js-mobile-menu-toggle" class="lg:hidden text-gray-700 text-2xl leading-none px-1" aria-label="Menüyü aç">&#9776;</button>
    </div>
  </div>
  <div id="js-mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100">
    <nav class="max-w-6xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm font-bold text-gray-700">
      <a href="{{ brand_route('home') }}" class="py-2 hover:text-primary">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="py-2 hover:text-primary">Aile Sihirbazı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="py-2 hover:text-primary">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="py-2 hover:text-primary">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="py-2 hover:text-primary">Kurumlar</a>
      <hr class="border-gray-100 my-1">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="py-2 hover:text-primary">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="py-2 hover:text-primary">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="py-2 hover:text-primary">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="py-2 hover:text-primary">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="py-2 hover:text-primary">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        @if(session('facility_user_id'))<a href="{{ brand_route('facility.profile.edit') }}" class="py-2 hover:text-primary">Profili Düzenle</a>@else<a href="{{ brand_route('family.profile.edit') }}" class="py-2 hover:text-primary">Hesap Bilgilerim</a>@endif
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}">@csrf<button class="py-2 hover:text-primary">Çıkış Yap</button></form>
      @endif
    </nav>
  </div>
</header>
@else
<header class="bg-white shadow-sm sticky top-0 z-30">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
    <a href="{{ brand_route('home') }}" class="flex items-center gap-2 text-xl font-black text-primary">
      <img src="{{ asset('images/logo-'.$brand['slug'].'-64.png') }}" alt="{{ $brand['name'] }}" class="w-9 h-9 flex-shrink-0">
      <span>{{ $brand['logo_text'] }}</span>
    </a>
    <nav class="hidden lg:flex gap-5 text-sm font-semibold">
      <a href="{{ brand_route('home') }}" class="hover:text-primary">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="hover:text-primary">Karar Sihirbazı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="hover:text-primary">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="hover:text-primary">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="hover:text-primary">Kurumları Bul</a>
      <a href="{{ brand_route('contact.create') }}" class="hover:text-primary">İletişim</a>
    </nav>
    <div class="flex items-center gap-3 text-sm">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="font-semibold hover:text-primary hidden sm:inline">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="font-semibold hover:text-primary hidden sm:inline">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="font-semibold hover:text-primary hidden sm:inline">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="font-semibold hover:text-primary hidden sm:inline">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="relative font-semibold hover:text-primary hidden sm:inline">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}" class="hidden sm:inline">@csrf<button class="font-semibold hover:text-primary">Çıkış Yap</button></form>
      @endif
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="btn-primary text-sm px-4 py-2 rounded-lg font-bold">Başla</a>
      <button type="button" id="js-mobile-menu-toggle" class="lg:hidden text-gray-700 text-2xl leading-none px-1" aria-label="Menüyü aç">&#9776;</button>
    </div>
  </div>
  <div id="js-mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100 shadow-sm">
    <nav class="max-w-6xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm font-semibold text-gray-700">
      <a href="{{ brand_route('home') }}" class="py-2 hover:text-primary">Ana Sayfa</a>
      <a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="py-2 hover:text-primary">Karar Sihirbazı</a>
      <a href="{{ brand_route('engagement.compare') }}" class="py-2 hover:text-primary">Karşılaştır</a>
      @if(session('family_user_id'))<a href="{{ brand_route('engagement.favorites') }}" class="py-2 hover:text-primary">Favoriler</a>@endif
      <a href="{{ brand_route('facilities.index') }}" class="py-2 hover:text-primary">Kurumları Bul</a>
      <a href="{{ brand_route('contact.create') }}" class="py-2 hover:text-primary">İletişim</a>
      <hr class="border-gray-100 my-1">
      @if(session('family_user_id'))<a href="{{ brand_route('family.dashboard') }}" class="py-2 hover:text-primary">Aile Panelim</a>@else<a href="{{ brand_route('family.login') }}" class="py-2 hover:text-primary">Aile Girişi</a>@endif
      @if(session('facility_user_id'))<a href="{{ brand_route('facility.dashboard') }}" class="py-2 hover:text-primary">Kurum Panelim</a>@else<a href="{{ brand_route('facility.login') }}" class="py-2 hover:text-primary">Kurum Girişi</a>@endif
      @if(session('facility_user_id') || session('family_user_id'))
        <a href="{{ session('facility_user_id') ? brand_route('facility.notifications.index') : brand_route('family.notifications.index') }}" class="py-2 hover:text-primary">Bildirimler @if($unreadNotificationsCount > 0)<span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>@endif</a>
        @if(session('facility_user_id'))<a href="{{ brand_route('facility.profile.edit') }}" class="py-2 hover:text-primary">Profili Düzenle</a>@else<a href="{{ brand_route('family.profile.edit') }}" class="py-2 hover:text-primary">Hesap Bilgilerim</a>@endif
        <form method="POST" action="{{ session('facility_user_id') ? brand_route('facility.logout') : brand_route('family.logout') }}">@csrf<button class="py-2 hover:text-primary">Çıkış Yap</button></form>
      @endif
    </nav>
  </div>
</header>
@endif

{{-- 12 Agustos 2026: kullanicinin talebi - "premium hissi" en cok kucuk
     anlarda hissedilir; teklif talebi/sahiplenme/kayit/iletisim/yorum gibi
     TUM formlar bu TEK paylasilan bildirimi kullaniyor, bu yuzden burada
     yapilan tek bir iyilestirme site genelindeki her "gonderildi" anini
     ayni anda yukseltiyor. Duz renkli bir bant yerine ikonlu, golgeli,
     birkaç saniye sonra kendiliginden kaybolan (ama elle de kapatilabilen)
     bir bildirim karti. --}}
@if(session('success') || session('info') || session('error'))
  @php
    $flashType = session('success') ? 'success' : (session('info') ? 'info' : 'error');
    $flashText = session('success') ?: (session('info') ?: session('error'));
    $flashStyles = [
      'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#065f46', 'icon' => '#10b981', 'symbol' => '✓'],
      'info' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e40af', 'icon' => '#3b82f6', 'symbol' => 'ℹ'],
      'error' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#991b1b', 'icon' => '#ef4444', 'symbol' => '!'],
    ][$flashType];
  @endphp
  <div id="js-flash-toast" class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-5 sm:left-auto z-50 sm:max-w-sm animate-[flash-in_.35s_ease-out]" role="status">
    <div class="flex items-start gap-3 rounded-2xl border shadow-xl p-4" style="background: {{ $flashStyles['bg'] }}; border-color: {{ $flashStyles['border'] }};">
      <span class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center font-black text-white text-sm" style="background: {{ $flashStyles['icon'] }};">{{ $flashStyles['symbol'] }}</span>
      <p class="text-sm font-semibold flex-1" style="color: {{ $flashStyles['text'] }};">{{ $flashText }}</p>
      <button type="button" onclick="document.getElementById('js-flash-toast').remove()" class="shrink-0 text-lg leading-none opacity-50 hover:opacity-100" style="color: {{ $flashStyles['text'] }};" aria-label="Kapat">×</button>
    </div>
  </div>
  <style>
    @keyframes flash-in { 0% { transform: translateY(-12px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
  </style>
  <script>
    setTimeout(function () {
      var el = document.getElementById('js-flash-toast');
      if (el) { el.style.transition = 'opacity .4s ease'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 400); }
    }, 6000);
  </script>
@endif
@if($errors->any())
<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<main>
  {{ $slot ?? '' }}
  @yield('content')
</main>

<footer class="{{ $theme === 'bakimeviara' ? 'bg-gray-50 text-gray-600 border-t border-gray-100' : 'bg-gray-950 text-gray-300' }} mt-16">
  <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
    <div>
      <div class="flex items-center gap-2 {{ $theme === 'bakimeviara' ? 'text-gray-950' : 'text-white' }} font-black text-lg mb-2">
        <img src="{{ asset('images/logo-'.$brand['slug'].'-64.png') }}" alt="{{ $brand['name'] }}" class="w-7 h-7 flex-shrink-0">
        <span>{{ $brand['logo_text'] }}</span>
      </div>
      <p>{{ $brand['tagline'] }}</p>
    </div>
    <div>
      <div class="{{ $theme === 'bakimeviara' ? 'text-gray-950' : 'text-white' }} font-bold mb-2">Keşfet</div>
      <ul class="space-y-1">
        <li><a href="{{ brand_route('care-advisor.form') }}" class="hover:text-primary">Bakım Danışmanı</a></li>
        <li><a href="{{ brand_route('engagement.wizard', ['bolum' => $defaultSection]) }}" class="hover:text-primary">Karar sihirbazı</a></li>
        <li><a href="{{ brand_route('engagement.compare') }}" class="hover:text-primary">Karşılaştırma</a></li>
        @if(session('family_user_id'))<li><a href="{{ brand_route('engagement.favorites') }}" class="hover:text-primary">Favoriler</a></li>@endif
        <li><a href="{{ brand_route('location-guide.index', ['sectionSlug' => $defaultSection]) }}" class="hover:text-primary">İl / İlçe Rehberi</a></li>
        <li><a href="{{ brand_route('price-guide.index') }}" class="hover:text-primary">Ücret Rehberi</a></li>
        <li><a href="{{ brand_route('guides.index') }}" class="hover:text-primary">Bakım Rehberi</a></li>
        <li><a href="{{ brand_route('stats.index') }}" class="hover:text-primary">Türkiye İstatistikleri</a></li>
        <li><a href="{{ brand_route('faq.index') }}" class="hover:text-primary">Sıkça Sorulan Sorular</a></li>
      </ul>
    </div>
    <div>
      <div class="{{ $theme === 'bakimeviara' ? 'text-gray-950' : 'text-white' }} font-bold mb-2">Kurum Vitrini</div>
      <ul class="space-y-1">
        <li><a href="{{ brand_route('discovery.verified') }}" class="hover:text-primary">Doğrulanmış Kurumlar</a></li>
        <li><a href="{{ brand_route('discovery.new') }}" class="hover:text-primary">Yeni Eklenen Kurumlar</a></li>
        <li><a href="{{ brand_route('discovery.recent-updated') }}" class="hover:text-primary">Son Güncellenen Kurumlar</a></li>
        <li><a href="{{ brand_route('discovery.recent-claimed') }}" class="hover:text-primary">Son Sahiplenilen Kurumlar</a></li>
        <li><a href="{{ brand_route('discovery.most-viewed') }}" class="hover:text-primary">En Çok Görüntülenenler</a></li>
        <li><a href="{{ brand_route('discovery.most-searched') }}" class="hover:text-primary">En Çok Aranan Bölgeler</a></li>
        <li><a href="{{ brand_route('discovery.recent-photos') }}" class="hover:text-primary">Son Eklenen Fotoğraflar</a></li>
      </ul>
      <div class="{{ $theme === 'bakimeviara' ? 'text-gray-950' : 'text-white' }} font-bold mb-2 mt-5">Kurumsal</div>
      <ul class="space-y-1">
        <li><a href="{{ brand_route('pages.show', ['slug' => 'hakkimizda']) }}" class="hover:text-primary">Hakkımızda</a></li>
        <li><a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" class="hover:text-primary">KVKK</a></li>
        <li><a href="{{ brand_route('pages.show', ['slug' => 'gizlilik-politikasi']) }}" class="hover:text-primary">Gizlilik Politikası</a></li>
        <li><a href="{{ brand_route('pages.show', ['slug' => 'kullanim-sartlari']) }}" class="hover:text-primary">Kullanım Şartları</a></li>
        <li><a href="{{ brand_route('pages.show', ['slug' => 'cerez-politikasi']) }}" class="hover:text-primary">Çerez Politikası</a></li>
        <li><a href="{{ brand_route('contact.create') }}" class="hover:text-primary">İletişim</a></li>
      </ul>
    </div>
    <div>
      <div class="{{ $theme === 'bakimeviara' ? 'text-gray-950' : 'text-white' }} font-bold mb-2">Diğer Sitelerimiz</div>
      <ul class="space-y-1">
        @foreach(config('brands.brands') as $slug => $b)
          @if($slug !== $brand['slug'])
            <li><a href="{{ route('brand.home', ['brand' => $slug]) }}" class="hover:text-primary">{{ $b['name'] }}</a></li>
          @endif
        @endforeach
      </ul>
    </div>
  </div>
  <div class="text-center text-xs py-4 {{ $theme === 'bakimeviara' ? 'border-t border-gray-200' : 'border-t border-white/10' }}">© {{ date('Y') }} {{ $brand['logo_text'] }}. Tüm hakları saklıdır.</div>
</footer>

@include('themes._shared.partials.support-chat-widget')
@include('themes._shared.partials.pwa-install-button')
@include('themes._shared.partials.cookie-consent')
@include('themes._shared.partials.panel-notification-alerts')
@include('themes._shared.partials.organization-jsonld')
@include('themes._shared.partials.scroll-restore')
@yield('breadcrumb_jsonld')

<script>
(function () {
  var toggle = document.getElementById('js-mobile-menu-toggle');
  var menu = document.getElementById('js-mobile-menu');
  if (!toggle || !menu) return;
  toggle.addEventListener('click', function () {
    menu.classList.toggle('hidden');
  });
  menu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { menu.classList.add('hidden'); });
  });
})();
</script>

</body>
</html>




