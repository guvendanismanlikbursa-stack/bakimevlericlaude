{{-- 12 Agustos 2026: kullanicinin talebi - anasayfayi "maksimum premium"
     seviyeye tasimak icin, filtrenin hemen altina Hakkimizda'daki gibi
     canli/gercek platform rakamlarindan olusan bir guven seridi eklendi. --}}
{{-- 24 Agustos 2026: kullanicinin PageSpeed Insights ile bildirdigi gercek
     hata - 11px gray-500 metin, gray-100 zeminde WCAG kontrast esigini
     gecemiyordu (dusuk gorme/parlak ekranda okunmasi zorlasiyordu).
     gray-600'a koyulastirildi. --}}
<div class="max-w-6xl mx-auto px-4 -mt-1 mb-2">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 py-5 border-t border-b border-gray-100">
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">{{ number_format($facilityCount, 0, ',', '.') }}</div>
      <div class="text-[11px] md:text-xs text-gray-600 mt-0.5">Listelenen kurum</div>
    </div>
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">{{ $cityCount }}/81</div>
      <div class="text-[11px] md:text-xs text-gray-600 mt-0.5">İl kapsamı</div>
    </div>
    <div class="text-center">
      {{-- 18 Ekim 2026: kullanıcının talebi - küçük sayı yerine büyüme vurgusu. --}}
      <div class="text-xl md:text-2xl font-black text-gray-950">Her gün artan</div>
      <div class="text-[11px] md:text-xs text-gray-600 mt-0.5">Doğrulanmış kurum</div>
    </div>
    {{-- 15 Ekim 2026: kullanicinin talebi - anlasmali (broker-managed)
         kurumlara gelen talepler 10-30 dakika, diger (on kayitli)
         kurumlara gelenler 30 dakika-1 saat icinde ekip tarafindan
         degerlendiriliyor (gercek operasyonel pratik) - genel/ozet rakam
         olarak en genis araligi (10dk-1sa) gosterir, formlarda (bkz.
         facilities/show.blade.php) kurum turune ozel netlestirilir. --}}
    <div class="text-center">
      <div class="text-xl md:text-2xl font-black text-gray-950">10dk-1sa</div>
      <div class="text-[11px] md:text-xs text-gray-600 mt-0.5">Taleplere dönüş süresi</div>
    </div>
  </div>
</div>
