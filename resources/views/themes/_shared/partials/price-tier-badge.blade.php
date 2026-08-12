{{-- 16 Temmuz 2026: fiyat araligi (price_min-price_max) birden fazla segmenti
     kaplayabildigi icin artik TEK degil, kesisen TUM segment rozetleri
     gosterilir (bkz. Facility::priceTiers()). --}}
@foreach($facility->priceTiers() as $tier)
  <span class="{{ $tier['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">{{ $tier['emoji'] }} {{ $tier['label'] }}</span>
@endforeach
