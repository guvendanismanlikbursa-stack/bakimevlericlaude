{{-- 22 Ekim 2026: kullanıcının talebi - üyelik kartları. Kartın büyük
     başlığı rozet adıdır (Farkındalık Destekçisi / Kurumsal İyilik Ortağı /
     Altın Kalpli Kurum), altında paketin adı (Ekonomik / Standart / Premium
     Üyelik). Fiyat yalnızca $showPrice true iken (kuruma özel teklif sayfası)
     görünür. Parametreler: $packages, $showPrice (bool), $detailAnchor (bool) --}}
@include('themes._shared.membership._styles')
@php
  $showPrice = $showPrice ?? false;
  $detailAnchor = $detailAnchor ?? true;
@endphp
<div class="ih-grid3">
  @foreach($packages as $package)
    <div class="ih-card ih-tier {{ $package->is_premium ? 'is-premium' : '' }}">
      <div class="ih-tier-emoji">{{ $package->badge_emoji }}</div>
      <div class="ih-tier-title">{{ $package->badge_label }}</div>
      <div class="ih-tier-sub">{{ $package->name }} · {{ $package->months == 12 ? 'Yıllık' : $package->months.' aylık' }}</div>
      @if($showPrice)
        @if($package->hasLaunchDiscount())
          <div style="font-size:14px;color:#9ca3af;margin-top:12px;"><span style="text-decoration:line-through;">{{ number_format($package->list_price, 0, ',', '.') }}₺</span> liste fiyatı · <b style="color:#92400e;">Lansman fiyatı</b></div>
        @endif
        <div class="ih-tier-price">{{ number_format($package->price, 0, ',', '.') }}₺ <span style="font-size:14px;font-weight:700;color:#6b7280;">/ {{ $package->months == 12 ? 'yıl' : $package->months.' ay' }}</span></div>
        @if($package->free_months_label)<div style="font-size:12px;font-weight:800;color:#15803d;">🎁 {{ $package->free_months_label }}</div>@endif
      @endif
      @if($package->tagline)<p class="ih-tier-tag">{{ $package->tagline }}</p>@endif
      @if($package->audience)<p class="ih-tier-aud"><b style="color:#111827;">Kimler için?</b> {{ $package->audience }}</p>@endif
      @if($detailAnchor)
        <a href="#paket-{{ $package->id }}" class="ih-tier-link">Paketin tüm ayrıntıları ↓</a>
      @endif
    </div>
  @endforeach
</div>
