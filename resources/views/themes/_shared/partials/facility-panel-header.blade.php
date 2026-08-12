@php $primary = current_brand()['primary_color']; @endphp
{{-- 12 Agustos 2026: kurum paneli alt sayfalari (profil, bakiye, paketler,
     sorular, bildirimler, sifre) icin ortak, renkli baslik seridi. --}}
<div style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-4xl mx-auto px-4 py-7">
    <a href="{{ brand_route('facility.dashboard') }}" class="text-sm text-white/70 hover:text-white">← Panele dön</a>
    <h1 class="text-xl md:text-2xl font-black mt-2">{{ $title }}</h1>
    @if($subtitle ?? null)<p class="text-white/80 text-sm mt-1">{{ $subtitle }}</p>@endif
  </div>
</div>
