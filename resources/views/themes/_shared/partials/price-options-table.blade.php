{{--
  26 Agustos 2026: oda tipi/yas grubu/program suresi fiyat tablolari AYNI
  yapiyi kullanir. Parametreler: $optionsTitle, $optionsItems (Facility'nin
  ilgili iliskisi - roomTypes/ageGroups/programTypes).
--}}
@if($optionsItems->isNotEmpty())
<div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="px-4 py-3 border-b border-gray-100 font-black text-gray-950 text-sm">{{ $optionsTitle }}</div>
  <div class="divide-y divide-gray-100">
    @foreach($optionsItems as $item)
      <div class="flex items-center justify-between px-4 py-2.5 text-sm">
        <span class="text-gray-700">{{ $item->label() }}</span>
        <span class="font-bold text-gray-950">
          {{-- 7 Eylul 2026: kullanicinin talebi - bkz. price-locked.blade.php ayni tarihli yorum. --}}
          @if(! session('family_user_id'))
            @include('themes._shared.partials.price-locked', ['class' => 'text-xs font-bold text-gray-500'])
          @elseif($item->price_min && $item->price_max)
            {{ number_format($item->price_min,0,',','.') }} - {{ number_format($item->price_max,0,',','.') }} TL
          @else
            {{ number_format($item->price_min ?: $item->price_max,0,',','.') }} TL'den başlıyor
          @endif
        </span>
      </div>
    @endforeach
  </div>
</div>
@endif
