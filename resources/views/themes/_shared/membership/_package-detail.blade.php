{{-- 22 Ekim 2026: kullanıcının talebi - bir üyelik paketinin tüm ayrıntısı,
     açılır-kapanır kart olarak. Her paket kendi içinde eksiksizdir (ortak
     maddeler her pakette tekrar edilir). İçerik admin "Paketler" ekranındaki
     "Kimler için?", "Paket ayrıntıları" ve "Kapanış cümlesi" alanlarından
     gelir. Parametreler: $package, $showPrice (bool), $open (bool) --}}
@include('themes._shared.membership._styles')
@php
  $showPrice = $showPrice ?? false;
  $accent = $package->is_premium ? '#b45309' : '#a21caf';
@endphp
<details id="paket-{{ $package->id }}" class="ih-card ih-acc" style="scroll-margin-top:96px;{{ $package->is_premium ? 'border:2px solid #fcd34d;' : '' }}" @if($open ?? false) open @endif>
  <summary>
    <span style="font-size:34px;line-height:1;flex:none;">{{ $package->badge_emoji }}</span>
    <span style="min-width:0;">
      <span style="display:block;font-size:12px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;color:{{ $accent }};">{{ $package->name }} · {{ $package->months == 12 ? 'Yıllık' : $package->months.' aylık' }}</span>
      <span style="display:block;font-size:clamp(18px,3.4vw,22px);font-weight:900;color:#111827;line-height:1.25;">{{ $package->badge_label }}</span>
      @if($package->tagline)<span style="display:block;font-size:14px;color:#4b5563;margin-top:2px;">{{ $package->tagline }}</span>@endif
    </span>
    <span class="ih-chev" aria-hidden="true">⌄</span>
  </summary>
  <div class="ih-acc-body">
    @if($package->audience)<p style="margin-top:18px;line-height:1.7;color:#374151;"><b style="color:#111827;">Kimler için?</b> {{ $package->audience }}</p>@endif
    <p style="margin-top:8px;font-size:14px;color:#4b5563;"><b style="color:#111827;">Ücret:</b>
      @if($showPrice)
        <b style="color:#701a75;">{{ number_format($package->price, 0, ',', '.') }}₺ / {{ $package->months == 12 ? 'yıl' : $package->months.' ay' }}</b>
      @else
        Kurum panelinizde ve size özel teklif sayfasında gösterilir.
      @endif
    </p>

    @foreach($package->featureGroups() as $group)
      @if($group['title'])<div class="ih-group-title">{{ $group['title'] }}</div>@endif
      <ul class="ih-items">
        @foreach($group['items'] as $item)
          <li><span>@if($item['text'])<b>{{ $item['name'] }}:</b> {{ $item['text'] }}@else {{ $item['name'] }}@endif</span></li>
        @endforeach
      </ul>
    @endforeach

    <div style="margin-top:22px;border-radius:16px;padding:16px 18px;background:#fdf4ff;">
      <div style="font-weight:900;color:#111827;">{{ $package->badge_emoji }} {{ $package->badge_label }} rozeti</div>
      <p style="margin-top:6px;font-size:14px;line-height:1.7;color:#4b5563;">Üyelik ücretinizin bir bölümü, kimsesi olmayan ve bakım ücretini ödeyemeyen ihtiyaç sahibi yaşlıların bakımı için harcanır. Kurumunuzun profilinde, arama sonuçlarındaki kartında ve internet sitesinde "{{ $package->badge_label }}" rozeti yer alır. Aileler, üyeliğinizle bir yaşlının bakımına destek olduğunuzu görür. Üyelik ücretleriyle bakımı yapılan hastalarımızın sayısı her ay İyilik Hareketi sayfamızda yayınlanır.</p>
    </div>

    @if($package->closing_text)<p style="margin-top:18px;font-weight:800;color:#111827;line-height:1.6;">{{ $package->closing_text }}</p>@endif
  </div>
</details>
