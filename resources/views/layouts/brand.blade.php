<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', $brand['tagline']) · {{ $brand['name'] }}</title>
<meta name="description" content="@yield('meta_description', $brand['tagline'])">
<link rel="canonical" href="@yield('canonical', canonical_url())">
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
@vite('resources/css/app.css')
<style>
  :root{ --primary: {{ $brand['primary_color'] }}; --secondary: {{ $brand['secondary_color'] }}; }
  body{ font-family: {{ $brand['theme'] === 'bakimeviara' ? "'Poppins', sans-serif" : ($brand['theme'] === 'bakimevleri' ? "'Roboto', sans-serif" : "'Inter', sans-serif") }}; }
  .btn-primary{ background-color: var(--primary); color:#fff; }
  .btn-primary:hover{ filter: brightness(1.08); }
  .text-primary{ color: var(--primary); }
  .bg-primary{ background-color: var(--primary); }
  .border-primary{ border-color: var(--primary); }
  .badge-secondary{ background-color: var(--secondary); }
</style>
</head>
@php
  $theme = $brand['theme'];
  $bodyClass = $theme === 'bakimevleri' ? 'bg-gray-100 text-gray-800' : ($theme === 'bakimeviara' ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-800');
  $defaultSection = $brand['default_section'] ?? array_key_first(service_sections());
@endphp
<body class="{{ $bodyClass }}">

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
      @endif
    </nav>
  </div>
</header>
@endif

@if(session('success'))
<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div></div>
@endif
@if(session('info'))
<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-blue-100 text-blue-800 px-4 py-3 rounded-lg text-sm">{{ session('info') }}</div></div>
@endif
@if($errors->any())
<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<main>
  {{ $slot ?? '' }}
  @yield('content')
</main>

<footer class="{{ $theme === 'bakimeviara' ? 'bg-gray-50 text-gray-600 border-t border-gray-100' : 'bg-gray-950 text-gray-300' }} mt-16">
  <div class="max-w-6xl mx-auto px-4 py-10 grid md:grid-cols-4 gap-8 text-sm">
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

@include('themes._shared.partials.whatsapp-button')
@include('themes._shared.partials.pwa-install-button')
@include('themes._shared.partials.cookie-consent')
@include('themes._shared.partials.organization-jsonld')
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