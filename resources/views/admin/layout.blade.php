<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Panel') - Ortak Admin Panel</title>
<link rel="manifest" href="{{ route('admin.manifest') }}">
<meta name="theme-color" content="#111827">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo-bakimevleri-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-bakimevleri-180.png') }}">
<script>
  if (localStorage.getItem('admin-theme') === 'dark') {
    document.documentElement.classList.add('dark');
  }
</script>
@vite('resources/css/admin.css')
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<div id="admin-sidebar-backdrop" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"></div>

<aside id="admin-sidebar" class="w-64 bg-gray-900 text-gray-200 flex-shrink-0 flex flex-col fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-200 ease-out md:static md:translate-x-0 overflow-y-auto">
  <div class="p-5 font-bold text-lg text-white border-b border-gray-800 flex items-start justify-between gap-3">
    <div>
      <div>Ortak Admin Panel</div>
      <div class="text-xs font-normal text-gray-400 mt-1">3 site · tek panel</div>
    </div>
    <button type="button" id="admin-sidebar-close" class="md:hidden text-gray-300 hover:text-white text-2xl leading-none px-1" aria-label="Menüyü kapat">&times;</button>
  </div>
  {{--
    25 Agustos 2026: kullanicinin talebi - liste tek duz akan uzun bir
    menuydu, asagi kaydirma cok fazlaydi. Her grup artik native <details>
    ile acilir/kapanir bir akordiyon - JS gerektirmez. Kullanici o an
    hangi grubun icindeki bir sayfadaysa, o grup varsayilan ACIK gelir
    (routeIs kontrolu ile "open" niteligi eklenir), boylece "neredeyim"
    hissi kaybolmaz.
  --}}
  @php
    $groupOpen = [
      'kullanicilar' => request()->routeIs('admin.users.families*') || request()->routeIs('admin.family-users.*') || request()->routeIs('admin.users.facility-users*'),
      'kurumlar' => request()->routeIs('admin.facilities.*') || request()->routeIs('admin.claims.*') || request()->routeIs('admin.registrations.*') || request()->routeIs('admin.invitations.*') || request()->routeIs('admin.broker.*') || request()->routeIs('admin.occupancy.*') || request()->routeIs('admin.job-applications.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.cities.*'),
      'donusum' => request()->routeIs('admin.offer-requests.*') || request()->routeIs('admin.visit-requests.*') || request()->routeIs('admin.visit-service.*') || request()->routeIs('admin.reviews.*') || request()->routeIs('admin.questions.*') || request()->routeIs('admin.contact-messages.*') || request()->routeIs('admin.whatsapp-clicks.*') || request()->routeIs('admin.chat.*'),
      'finans' => request()->routeIs('admin.topups.*') || request()->routeIs('admin.packages.*'),
      'icerik' => request()->routeIs('admin.content-pages.*') || request()->routeIs('admin.faqs.*') || request()->routeIs('admin.data-extractor.*') || request()->routeIs('admin.trash.*') || request()->routeIs('admin.audit-log.*') || request()->routeIs('admin.data-quality.*') || request()->routeIs('admin.platform-errors.*') || request()->routeIs('admin.account-deletions.*') || request()->routeIs('admin.scheduled-jobs.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.chat-settings.*'),
    ];
    $openErrorCount = \App\Models\PlatformError::whereNull('resolved_at')->count();
    $pendingDeletionCount = \App\Models\AccountDeletionRequest::where('status', 'pending')->count();
    $overdueJobCount = \App\Models\ScheduledJobRun::get()->filter(fn ($j) => $j->isOverdue())->count();
  @endphp
  <nav class="p-3 text-sm space-y-1 flex-1 overflow-y-auto">
    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : '' }}">Genel Bakış</a>
    <a href="{{ route('admin.site-stats.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.site-stats.*') ? 'bg-gray-700 text-white' : '' }}">Site İstatistikleri</a>
    <a href="{{ route('admin.nearby-searches.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.nearby-searches.*') ? 'bg-gray-700 text-white' : '' }}">Yakın Arama Kayıtları</a>

    <details class="group" @if($groupOpen['kullanicilar']) open @endif>
      <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
        <span>Kullanıcılar</span>
        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="space-y-1 pb-1">
        <a href="{{ route('admin.users.families') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.users.families*') || request()->routeIs('admin.family-users.*') ? 'bg-gray-700 text-white' : '' }}">Aileler</a>
        <a href="{{ route('admin.users.facility-users') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.users.facility-users*') ? 'bg-gray-700 text-white' : '' }}">Kurum Yetkilileri</a>
      </div>
    </details>

    <details class="group" @if($groupOpen['kurumlar']) open @endif>
      <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
        <span>Kurumlar</span>
        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="space-y-1 pb-1">
        <a href="{{ route('admin.facilities.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.facilities.*') ? 'bg-gray-700 text-white' : '' }}">Kurumlar</a>
        <a href="{{ route('admin.claims.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.claims.*') ? 'bg-gray-700 text-white' : '' }}"><span>Sahiplenme Başvuruları</span>@if($pendingClaimsCount > 0)<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingClaimsCount }}</span>@endif</a>
        <a href="{{ route('admin.registrations.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.registrations.*') ? 'bg-gray-700 text-white' : '' }}"><span>Kurum Kayıt Başvuruları</span>@if($pendingRegistrationsCount > 0)<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingRegistrationsCount }}</span>@endif</a>
        <a href="{{ route('admin.invitations.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.invitations.*') ? 'bg-gray-700 text-white' : '' }}">Kurum Davetleri</a>
        {{-- 25 Agustos 2026: kullanicinin talebi - kisisel aracilik/komisyon takibi icin kucuk bir CRM. --}}
        <a href="{{ route('admin.broker.facilities') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.broker.facilities*') ? 'bg-gray-700 text-white' : '' }}">Anlaşmalı Kurumlar</a>
        <a href="{{ route('admin.broker.referrals') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.broker.referrals*') ? 'bg-gray-700 text-white' : '' }}">Aracılık Yönlendirmeleri</a>
        <a href="{{ route('admin.occupancy.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.occupancy.*') ? 'bg-gray-700 text-white' : '' }}">Doluluk Durumu</a>
        <a href="{{ route('admin.job-applications.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.job-applications.*') ? 'bg-gray-700 text-white' : '' }}">İş Başvuruları</a>
        <a href="{{ route('admin.categories.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.categories.*') ? 'bg-gray-700 text-white' : '' }}">Kategoriler</a>
        <a href="{{ route('admin.cities.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.cities.*') ? 'bg-gray-700 text-white' : '' }}">Şehirler</a>
      </div>
    </details>

    <details class="group" @if($groupOpen['donusum']) open @endif>
      <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
        <span>Dönüşüm</span>
        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="space-y-1 pb-1">
        <a href="{{ route('admin.offer-requests.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.offer-requests.*') ? 'bg-gray-700 text-white' : '' }}">Teklif Talepleri</a>
        <a href="{{ route('admin.visit-requests.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.visit-requests.*') ? 'bg-gray-700 text-white' : '' }}">Ziyaret Talepleri</a>
        <a href="{{ route('admin.visit-service.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.visit-service.*') ? 'bg-gray-700 text-white' : '' }}"><span>Yakınımı Ziyaret Et Talepleri</span>@if($newVisitServiceRequestsCount > 0)<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $newVisitServiceRequestsCount }}</span>@endif</a>
        <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.reviews.*') ? 'bg-gray-700 text-white' : '' }}">Yorumlar</a>
        <a href="{{ route('admin.questions.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.questions.*') ? 'bg-gray-700 text-white' : '' }}">Aile Soruları</a>
        <a href="{{ route('admin.contact-messages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.contact-messages.*') ? 'bg-gray-700 text-white' : '' }}">İletişim Mesajları</a>
        <a href="{{ route('admin.whatsapp-clicks.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.whatsapp-clicks.*') ? 'bg-gray-700 text-white' : '' }}">WhatsApp Tıklamaları</a>
        <a href="{{ route('admin.chat.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.chat.*') ? 'bg-gray-700 text-white' : '' }}"><span>Canlı Sohbet</span>@if($unreadChatThreadsCount > 0)<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $unreadChatThreadsCount }}</span>@endif</a>
      </div>
    </details>

    <details class="group" @if($groupOpen['finans']) open @endif>
      <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
        <span>Finans</span>
        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="space-y-1 pb-1">
        <a href="{{ route('admin.topups.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.topups.*') ? 'bg-gray-700 text-white' : '' }}"><span>Bakiye Yüklemeleri</span>@if($pendingTopupsCount > 0)<span class="bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingTopupsCount }}</span>@endif</a>
        <a href="{{ route('admin.packages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700 text-white' : '' }}">Paketler</a>
      </div>
    </details>

    <details class="group" @if($groupOpen['icerik']) open @endif>
      <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
        <span>İçerik & Sistem</span>
        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="space-y-1 pb-1">
        <a href="{{ route('admin.content-pages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.content-pages.*') ? 'bg-gray-700 text-white' : '' }}">Statik Sayfalar</a>
        <a href="{{ route('admin.faqs.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.faqs.*') ? 'bg-gray-700 text-white' : '' }}">SSS</a>
        <a href="{{ route('admin.data-extractor.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.data-extractor.*') ? 'bg-gray-700 text-white' : '' }}">Veri Çekici</a>
        <a href="{{ route('admin.trash.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.trash.*') ? 'bg-gray-700 text-white' : '' }}">Çöp Kutusu</a>
        <a href="{{ route('admin.audit-log.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.audit-log.*') ? 'bg-gray-700 text-white' : '' }}">İşlem Günlüğü</a>
        <a href="{{ route('admin.data-quality.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.data-quality.*') ? 'bg-gray-700 text-white' : '' }}">Veri Denetimi</a>
        <a href="{{ route('admin.platform-errors.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.platform-errors.*') ? 'bg-gray-700 text-white' : '' }}">
          <span>Hatalar</span>
          @if($openErrorCount > 0)
            <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $openErrorCount }}</span>
          @endif
        </a>
        <a href="{{ route('admin.account-deletions.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.account-deletions.*') ? 'bg-gray-700 text-white' : '' }}">
          <span>Hesap Silme Talepleri</span>
          @if($pendingDeletionCount > 0)
            <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingDeletionCount }}</span>
          @endif
        </a>
        <a href="{{ route('admin.scheduled-jobs.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.scheduled-jobs.*') ? 'bg-gray-700 text-white' : '' }}">
          <span>Zamanlanan Görevler</span>
          @if($overdueJobCount > 0)
            <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $overdueJobCount }}</span>
          @endif
        </a>
        <a href="{{ route('admin.settings.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.settings.*') ? 'bg-gray-700 text-white' : '' }}">Ayarlar</a>
        <a href="{{ route('admin.chat-settings.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.chat-settings.*') ? 'bg-gray-700 text-white' : '' }}">Sohbet Çalışma Saatleri</a>
      </div>
    </details>

    @unless(app()->environment('production'))
      <details class="group">
        <summary class="flex items-center justify-between px-3 pt-4 pb-1 cursor-pointer list-none text-xs text-gray-500 uppercase tracking-wider hover:text-gray-300">
          <span>Test Linkleri (sadece local/staging)</span>
          <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="space-y-1 pb-1">
          <a href="/site/bakimevibul" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-emerald-400 text-xs">bakimevibul.com</a>
          <a href="/site/bakimeviara" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-purple-400 text-xs">bakimeviara.com</a>
          <a href="/site/bakimevleri" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-blue-400 text-xs">bakimevleri.com</a>
        </div>
      </details>
    @endunless
  </nav>

  <div class="p-4 border-t border-gray-800 space-y-3">
    <button type="button" id="admin-theme-toggle" class="admin-theme-toggle">
      <span id="admin-theme-toggle-label">Gece Modu</span>
      <span id="admin-theme-toggle-icon" aria-hidden="true">🌙</span>
    </button>
    @include('themes._shared.partials.notification-permission', ['buttonClass' => 'text-sm text-gray-200 hover:text-white font-semibold'])
    <div class="text-xs text-gray-400">{{ session('admin_name') }}</div>
    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="text-sm text-red-400 hover:text-red-300">Çıkış Yap</button></form>
  </div>
</aside>

<script>
  (function () {
    var root = document.documentElement;
    var btn = document.getElementById('admin-theme-toggle');
    var label = document.getElementById('admin-theme-toggle-label');
    var icon = document.getElementById('admin-theme-toggle-icon');

    function sync() {
      var isDark = root.classList.contains('dark');
      label.textContent = isDark ? 'Normal Mod' : 'Gece Modu';
      icon.textContent = isDark ? '☀️' : '🌙';
    }

    btn.addEventListener('click', function () {
      root.classList.toggle('dark');
      localStorage.setItem('admin-theme', root.classList.contains('dark') ? 'dark' : 'light');
      sync();
    });

    sync();
  })();
</script>

<div class="flex-1 flex flex-col min-w-0">

  <header class="md:hidden sticky top-0 z-20 bg-gray-900 text-white flex items-center gap-3 px-4 py-3 shadow">
    <button type="button" id="admin-sidebar-open" class="text-2xl leading-none px-1" aria-label="Menüyü aç">&#9776;</button>
    <div class="font-bold text-sm truncate">@yield('title', 'Panel')</div>
  </header>

  <main class="flex-1 p-4 md:p-8 overflow-auto min-w-0">
    @if(session('success'))
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-6">
        {{ session('success') }}
        @if(session('claim_whatsapp_link'))
          <a href="{{ session('claim_whatsapp_link') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-2 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-700">📱 Giriş Bilgilerini WhatsApp'tan Gönder</a>
        @endif
      </div>
    @endif
    @if(session('error'))<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-6">{{ session('error') }}</div>@endif
    @if(session('image_warning'))<div class="bg-amber-100 text-amber-800 px-4 py-3 rounded-lg text-sm mb-6">{{ session('image_warning') }}</div>@endif
    @if($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-6"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @yield('content')
  </main>

</div>

@include('themes._shared.partials.scroll-restore')

<script>
  (function () {
    var sidebar = document.getElementById('admin-sidebar');
    var backdrop = document.getElementById('admin-sidebar-backdrop');
    var openBtn = document.getElementById('admin-sidebar-open');
    var closeBtn = document.getElementById('admin-sidebar-close');

    function openSidebar() {
      sidebar.classList.remove('-translate-x-full');
      backdrop.classList.remove('hidden');
    }
    function closeSidebar() {
      sidebar.classList.add('-translate-x-full');
      backdrop.classList.add('hidden');
    }

    openBtn && openBtn.addEventListener('click', openSidebar);
    closeBtn && closeBtn.addEventListener('click', closeSidebar);
    backdrop && backdrop.addEventListener('click', closeSidebar);
    sidebar && sidebar.querySelectorAll('nav a').forEach(function (a) {
      a.addEventListener('click', closeSidebar);
    });

    // Sayfadaki her tabloyu, gorunumu bozmadan yatay kaydirilabilir bir
    // sarmalayiciya alir - admin ekranlarinin tamami tek tek elden
    // gecirilmeden dar (mobil) ekranlarda tablo tasmasini onler.
    document.querySelectorAll('table').forEach(function (table) {
      var parent = table.parentElement;
      if (parent && parent.classList.contains('admin-table-scroll')) return;
      var wrapper = document.createElement('div');
      wrapper.className = 'admin-table-scroll overflow-x-auto';
      parent.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    });
  })();
</script>

</body>
</html>