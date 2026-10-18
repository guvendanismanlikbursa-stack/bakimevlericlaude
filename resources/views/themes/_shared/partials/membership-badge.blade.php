{{-- 18 Ekim 2026: kullanıcının talebi - İyilik Hareketi üyelik rozeti.
     Program yayında ve kurumun bir üyelik paketi varsa paketin rozeti
     (Farkındalık Destekçisi / Kurumsal İyilik Ortağı / Altın Kalpli Kurum /
     Vizyoner İyilik Elçisi), aksi halde eski "Yardımsever Kurum" rozeti
     aynen gösterilir. Bu partial yalnızca aktif abone kurumlar için çağrılır. --}}
@php
  $membershipBadge = $facility->membershipBadge();
  $badgePad = ($size ?? 'sm') === 'md' ? 'px-2 py-1' : 'px-2 py-0.5';
  $badgeStyles = [
      '💛' => 'background:#fef3c7;color:#92400e;',
      '⭐' => 'background:#fef3c7;color:#92400e;',
      '💚' => 'background:#dcfce7;color:#166534;',
  ];
@endphp
@if($membershipBadge)
  @php($badgeStyle = $badgeStyles[$membershipBadge['emoji']] ?? 'background:#fae8ff;color:#a21caf;')
  {{-- Kurum kartı bütünüyle bir <a> içinde - iç içe <a> HTML'i bozar, bu
       yüzden kartta ($linked yok) yalnızca açıklamalı etiket, profilde link. --}}
  @if(($linked ?? false) && membership_live())
    <a href="{{ brand_route('pages.membership') }}" title="Bu kurum, üyeliğiyle kimsesiz yaşlı hastaların bakımına katkı sağlıyor. İyilik Hareketi hakkında bilgi alın." class="text-xs font-black {{ $badgePad }} rounded-full inline-block" style="{{ $badgeStyle }}">{{ $membershipBadge['emoji'] }} {{ $membershipBadge['label'] }}</a>
  @else
    <span title="Bu kurum, üyeliğiyle kimsesiz yaşlı hastaların bakımına katkı sağlıyor." class="text-xs font-black {{ $badgePad }} rounded-full" style="{{ $badgeStyle }}">{{ $membershipBadge['emoji'] }} {{ $membershipBadge['label'] }}</span>
  @endif
@else
  <span class="text-xs font-black {{ $badgePad }} rounded-full" style="background:#fae8ff;color:#a21caf;">💜 Yardımsever Kurum</span>
@endif
