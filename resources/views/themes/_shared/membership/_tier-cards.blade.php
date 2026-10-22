{{-- 22 Ekim 2026: kullanıcının talebi - üyelik kartları. Kartın büyük
     başlığı rozet adıdır (Farkındalık Destekçisi / Kurumsal İyilik Ortağı /
     Altın Kalpli Kurum), altında paketin adı (Ekonomik / Standart / Premium
     Üyelik). Fiyat yalnızca $showPrice true iken (kuruma özel teklif sayfası
     ve kurum paneli) görünür.
     Parametreler: $packages, $showPrice (bool), $detailAnchor (bool) --}}
@php
  $showPrice = $showPrice ?? false;
  $detailAnchor = $detailAnchor ?? true;
@endphp
<div class="grid md:grid-cols-3 gap-4">
  @foreach($packages as $package)
    <div class="bg-white flex flex-col" style="border-radius:20px;padding:24px;border:2px solid {{ $package->is_premium ? '#fcd34d' : '#f1f5f9' }};box-shadow:0 1px 3px rgba(15,23,42,.06);">
      <div style="font-size:34px;line-height:1;">{{ $package->badge_emoji }}</div>
      <div class="font-black text-gray-900" style="font-size:20px;margin-top:14px;">{{ $package->badge_label }}</div>
      <div class="text-xs font-black uppercase" style="letter-spacing:.06em;margin-top:4px;color:{{ $package->is_premium ? '#b45309' : '#a21caf' }};">{{ $package->name }} · {{ $package->months == 12 ? 'Yıllık' : $package->months.' aylık' }}</div>
      @if($showPrice)
        @if($package->hasLaunchDiscount())
          <div class="text-sm text-gray-400" style="margin-top:10px;"><span style="text-decoration:line-through;">{{ number_format($package->list_price, 0, ',', '.') }}₺</span> <span class="text-xs">liste fiyatı</span></div>
          <div class="text-xs font-black" style="color:#92400e;">Lansman fiyatı</div>
        @endif
        <div class="font-black" style="font-size:30px;color:#701a75;margin-top:6px;">{{ number_format($package->price, 0, ',', '.') }}₺ <span class="text-sm font-bold text-gray-500">/ yıl</span></div>
        @if($package->free_months_label)<div class="text-xs font-bold text-green-700">🎁 {{ $package->free_months_label }}</div>@endif
      @endif
      @if($package->tagline)<p class="font-bold text-gray-800" style="margin-top:12px;">{{ $package->tagline }}</p>@endif
      @if($package->audience)<p class="text-sm text-gray-600" style="margin-top:8px;"><strong>Kimler için?</strong> {{ $package->audience }}</p>@endif
      @if($detailAnchor)
        <a href="#paket-{{ $package->id }}" class="text-sm font-black underline" style="margin-top:auto;padding-top:14px;color:#701a75;">Paketin tüm ayrıntıları ↓</a>
      @endif
    </div>
  @endforeach
</div>
