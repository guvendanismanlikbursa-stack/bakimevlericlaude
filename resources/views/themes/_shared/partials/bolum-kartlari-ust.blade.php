{{-- 25 Ekim 2026: kullanıcının talebi - Doktorlar sayfasına girince üstteki bölüm kartları
     kaybolmamalı. Anasayfadaki 4'lü kart sırası burada da gösterilir; "Özel Doktorlar" seçili.
     Diğer üç kart anasayfada o bölümü açar. Her sitenin kendi kart görünümü korunur
     (sınıflar ilgili home.blade.php'deki seçili olmayan kartla aynı). --}}
@php
  $temaKart = current_brand()['theme'] ?? 'bakimevleri';
  $kartSinifi = [
      'bakimevleri' => 'section-card-beam relative border rounded-xl p-4 overflow-hidden transition text-white border-white/14 hover:brightness-110',
      'bakimevibul' => 'relative section-card-beam rounded-lg border p-4 min-h-[118px] overflow-hidden transition hover:shadow-lg hover:-translate-y-0.5 text-white border-gray-200',
      'bakimeviara' => 'group relative section-card-beam rounded-2xl border p-4 min-h-[112px] overflow-hidden transition hover:-translate-y-0.5 hover:shadow-xl text-white border-gray-100 shadow-sm',
  ][$temaKart] ?? 'section-card-beam relative border rounded-xl p-4 overflow-hidden transition text-white';
  $ikonSinifi = $temaKart === 'bakimeviara' ? 'rounded-xl' : 'rounded-lg';
  $baslikSinifi = $temaKart === 'bakimevibul' ? 'font-extrabold' : 'font-black';
  $ayrac = $temaKart === 'bakimevleri' ? ' · ' : ', ';
  $kose = ['bakimevleri' => 12, 'bakimevibul' => 8, 'bakimeviara' => 16][$temaKart] ?? 12;
@endphp
<div class="grid sm:grid-cols-3 gap-3 bolum-kartlari-4" style="margin-bottom:22px;">
  @foreach(service_sections() as $slug => $item)
    <a href="{{ brand_route('home', ['bolum' => $slug]) }}" class="{{ $kartSinifi }}" style="--beam-color: {{ $item['theme']['secondary'] }};">
      <img src="{{ $item['hero_image_card'] ?? $item['hero_image'] }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/78 via-black/35 to-black/10"></div>
      <div class="relative flex items-center justify-between gap-2">
        <span class="bk-ikon inline-flex {{ $ikonSinifi }} p-2 bg-white/15">@include('themes._shared.partials.section-icon', ['section' => $item, 'class' => $temaKart === 'bakimevleri' ? 'w-6 h-6' : 'w-7 h-7'])</span>
      </div>
      <div class="bk-baslik relative {{ $baslikSinifi }} mt-3">{{ $item['title'] }}</div>
      <div class="bk-alt relative text-xs mt-1 text-white/85">{{ implode($ayrac, array_slice($item['features'], 0, 2)) }}</div>
    </a>
  @endforeach
  @include('themes._shared.partials.doktor-bolum-karti', ['kose' => $kose, 'secili' => true])
</div>
