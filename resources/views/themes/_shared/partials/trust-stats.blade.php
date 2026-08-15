{{-- 12 Agustos 2026: kullanicinin talebi - anasayfayi "maksimum premium"
     seviyeye tasimak icin, filtrenin hemen altina Hakkimizda'daki gibi
     canli/gercek platform rakamlarindan olusan bir guven seridi eklendi. --}}
<div class="max-w-6xl mx-auto px-4 -mt-1 mb-2">
  <div class="grid grid-cols-3 gap-3 md:gap-6 py-5 border-t border-b border-gray-100">
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">{{ number_format($facilityCount, 0, ',', '.') }}</div>
      <div class="text-[11px] md:text-xs text-gray-500 mt-0.5">Listelenen kurum</div>
    </div>
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">{{ $cityCount }}/81</div>
      <div class="text-[11px] md:text-xs text-gray-500 mt-0.5">İl kapsamı</div>
    </div>
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">{{ number_format($claimedCount, 0, ',', '.') }}</div>
      <div class="text-[11px] md:text-xs text-gray-500 mt-0.5">Doğrulanmış kurum</div>
    </div>
  </div>
</div>
