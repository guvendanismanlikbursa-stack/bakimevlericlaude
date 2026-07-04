<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Panel') - Ortak Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<aside class="w-64 bg-gray-900 text-gray-200 flex-shrink-0 flex flex-col">
  <div class="p-5 font-bold text-lg text-white border-b border-gray-800">
    <div>Ortak Admin Panel</div>
    <div class="text-xs font-normal text-gray-400 mt-1">3 site · tek panel</div>
  </div>
  <nav class="p-3 text-sm space-y-1 flex-1 overflow-y-auto">
    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : '' }}">Genel Bakış</a>
    <a href="{{ route('admin.site-stats.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.site-stats.*') ? 'bg-gray-700 text-white' : '' }}">Site İstatistikleri</a>

    <div class="text-xs text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1">Kurumlar</div>
    <a href="{{ route('admin.facilities.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.facilities.*') && request('claim_status') !== 'unclaimed' ? 'bg-gray-700 text-white' : '' }}">Kurumlar</a>
    <a href="{{ route('admin.facilities.index', ['claim_status' => 'unclaimed']) }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.facilities.*') && request('claim_status') === 'unclaimed' ? 'bg-gray-700 text-white' : '' }}">Ön Kayıtlı Kurumlar</a>
    <a href="{{ route('admin.claims.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.claims.*') ? 'bg-gray-700 text-white' : '' }}"><span>Sahiplenme Başvuruları</span>@if($pendingClaimsCount > 0)<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingClaimsCount }}</span>@endif</a>
    <a href="{{ route('admin.categories.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.categories.*') ? 'bg-gray-700 text-white' : '' }}">Kategoriler</a>
    <a href="{{ route('admin.cities.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.cities.*') ? 'bg-gray-700 text-white' : '' }}">Şehirler</a>

    <div class="text-xs text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1">Dönüşüm</div>
    <a href="{{ route('admin.offer-requests.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.offer-requests.*') ? 'bg-gray-700 text-white' : '' }}">Teklif Talepleri</a>
    <a href="{{ route('admin.visit-requests.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.visit-requests.*') ? 'bg-gray-700 text-white' : '' }}">Ziyaret Talepleri</a>
    <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.reviews.*') ? 'bg-gray-700 text-white' : '' }}">Yorumlar</a>
    <a href="{{ route('admin.questions.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.questions.*') ? 'bg-gray-700 text-white' : '' }}">Aile Soruları</a>
    <a href="{{ route('admin.contact-messages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.contact-messages.*') ? 'bg-gray-700 text-white' : '' }}">İletişim Mesajları</a>
    <a href="{{ route('admin.whatsapp-clicks.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.whatsapp-clicks.*') ? 'bg-gray-700 text-white' : '' }}">WhatsApp Tıklamaları</a>

    <div class="text-xs text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1">Finans</div>
    <a href="{{ route('admin.topups.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.topups.*') ? 'bg-gray-700 text-white' : '' }}"><span>Bakiye Yüklemeleri</span>@if($pendingTopupsCount > 0)<span class="bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingTopupsCount }}</span>@endif</a>
    <a href="{{ route('admin.packages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700 text-white' : '' }}">Paketler</a>

    <div class="text-xs text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1">İçerik & Sistem</div>
    <a href="{{ route('admin.content-pages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.content-pages.*') ? 'bg-gray-700 text-white' : '' }}">Statik Sayfalar</a>
    <a href="{{ route('admin.faqs.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.faqs.*') ? 'bg-gray-700 text-white' : '' }}">SSS</a>
    <a href="{{ route('admin.data-extractor.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.data-extractor.*') ? 'bg-gray-700 text-white' : '' }}">Veri &Ccedil;ekici</a>
    <a href="{{ route('admin.trash.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.trash.*') ? 'bg-gray-700 text-white' : '' }}">Çöp Kutusu</a>
    <a href="{{ route('admin.audit-log.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.audit-log.*') ? 'bg-gray-700 text-white' : '' }}">İşlem Günlüğü</a>
    <a href="{{ route('admin.settings.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.settings.*') ? 'bg-gray-700 text-white' : '' }}">Ayarlar</a>

    <div class="text-xs text-gray-500 uppercase tracking-wider px-3 pt-4 pb-1">Test Linkleri</div>
    <a href="/site/bakimevibul" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-emerald-400 text-xs">bakimevibul.com</a>
    <a href="/site/bakimeviara" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-purple-400 text-xs">bakimeviara.com</a>
    <a href="/site/bakimevleri" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-gray-800 text-blue-400 text-xs">bakimevleri.com</a>
  </nav>

  <div class="p-4 border-t border-gray-800">
    <div class="text-xs text-gray-400 mb-2">{{ session('admin_name') }}</div>
    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="text-sm text-red-400 hover:text-red-300">Çıkış Yap</button></form>
  </div>
</aside>

<main class="flex-1 p-8 overflow-auto">
  @if(session('success'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-6">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-6"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>

</body>
</html>