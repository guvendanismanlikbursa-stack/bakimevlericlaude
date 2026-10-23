{{-- 23 Ekim 2026: kullanıcının talebi - Kurumlar listesinde kurumun bizdeki
     sitesinin şekli: yok / taslak / standart / profesyonel (bitiş tarihiyle). --}}
@php $ws = $facility->website; @endphp
@if(! $ws)
  <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#f3f4f6;color:#6b7280;">Bizim site: yok</span>
@elseif($ws->isPro())
  <span style="display:inline-block;font-size:11px;font-weight:800;padding:2px 9px;border-radius:9999px;background:#fdf4e3;color:#8a6a35;">Bizim site: ★ Profesyonel{{ $ws->pro_until ? ' · '.$ws->pro_until->format('d.m.Y').'’e kadar' : '' }}{{ $ws->isLive() ? '' : ' (taslak)' }}</span>
@elseif($ws->isLive())
  <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#dcfce7;color:#166534;">Bizim site: Standart (yayında){{ $ws->plan === 'profesyonel' ? ' · Profesyonel süresi doldu' : '' }}</span>
@else
  <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#fef3c7;color:#92400e;">Bizim site: taslak</span>
@endif
