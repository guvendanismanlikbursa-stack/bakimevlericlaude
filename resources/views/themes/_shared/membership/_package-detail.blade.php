{{-- 22 Ekim 2026: kullanıcının talebi - bir üyelik paketinin tüm ayrıntısı.
     Her paket kendi içinde eksiksizdir; görüşmede tek başına okunabilsin diye
     ortak maddeler her pakette tekrar edilir. İçerik admin "Paketler"
     ekranındaki "Kimler için?", "Paket ayrıntıları" ve "Kapanış cümlesi"
     alanlarından gelir.
     Parametreler: $package, $showPrice (bool) --}}
@php
  $showPrice = $showPrice ?? false;
  $accent = $package->is_premium ? '#b45309' : '#a21caf';
@endphp
<article id="paket-{{ $package->id }}" class="bg-white scroll-mt-24" style="border-radius:20px;border:2px solid {{ $package->is_premium ? '#fcd34d' : '#f1f5f9' }};padding:24px;">
  <div class="flex items-center gap-3 flex-wrap">
    <span style="font-size:34px;line-height:1;">{{ $package->badge_emoji }}</span>
    <div>
      <div class="text-xs font-black uppercase" style="letter-spacing:.06em;color:{{ $accent }};">{{ $package->name }} · {{ $package->months == 12 ? 'Yıllık' : $package->months.' aylık' }}</div>
      <h3 class="font-black text-gray-900" style="font-size:22px;">{{ $package->badge_label }}</h3>
    </div>
  </div>
  @if($package->tagline)<p class="font-bold text-gray-800" style="margin-top:12px;font-size:17px;">{{ $package->tagline }}</p>@endif
  @if($package->audience)<p class="text-gray-700" style="margin-top:8px;"><strong>Kimler için?</strong> {{ $package->audience }}</p>@endif
  <p class="text-sm text-gray-600" style="margin-top:8px;"><strong>Ücret:</strong>
    @if($showPrice)
      <span class="font-black" style="color:#701a75;">{{ number_format($package->price, 0, ',', '.') }}₺ / yıl</span>
    @else
      Kurum panelinizde ve size özel teklif sayfasında gösterilir.
    @endif
  </p>

  @foreach($package->featureGroups() as $group)
    @if($group['title'])<h4 class="font-black text-gray-900" style="margin-top:20px;margin-bottom:8px;">{{ $group['title'] }}</h4>@endif
    <ul class="space-y-2">
      @foreach($group['items'] as $item)
        <li class="text-sm text-gray-700" style="display:flex;gap:8px;"><span style="color:{{ $accent }};font-weight:900;">•</span><span>@if($item['text'])<strong class="text-gray-900">{{ $item['name'] }}:</strong> {{ $item['text'] }}@else {{ $item['name'] }}@endif</span></li>
      @endforeach
    </ul>
  @endforeach

  <div style="margin-top:20px;border-radius:14px;padding:14px 16px;background:#fdf4ff;">
    <div class="font-black text-gray-900">{{ $package->badge_emoji }} {{ $package->badge_label }} rozeti</div>
    <p class="text-sm text-gray-700" style="margin-top:4px;">Üyelik ücretinizin bir bölümü, kimsesi olmayan ve bakım ücretini ödeyemeyen ihtiyaç sahibi yaşlıların bakımı için harcanır. Kurumunuzun profilinde, arama sonuçlarındaki kartında ve internet sitesinde "{{ $package->badge_label }}" rozeti yer alır. Aileler, üyeliğinizle bir yaşlının bakımına destek olduğunuzu görür. Üyelik ücretleriyle bakımı yapılan hastalarımızın sayısı her ay İyilik Hareketi sayfamızda yayınlanır.</p>
  </div>

  @if($package->closing_text)<p class="font-bold text-gray-900" style="margin-top:16px;">{{ $package->closing_text }}</p>@endif
</article>
