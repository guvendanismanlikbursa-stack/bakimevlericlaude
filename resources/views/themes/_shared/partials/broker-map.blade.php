{{--
    14 Eylul 2026: kullanicinin talebi - anlaşmalı (is_broker_managed)
    kurumlari haritada pin ile gosterir. Il secilmemisse Turkiye geneli,
    il secilmisse sadece o ile ait kurumlar gorunur (bkz. HomeController -
    $brokerFacilities zaten dogru filtrelenmis gelir). Pin'e tiklaninca
    kurumun detay sayfasi acilir, pin'in ustunde kurum adi surekli yazili
    durur. Harita init script'i (window.initBrokerMap) home.blade.php'de
    BIR KEZ tanimlanir - AJAX anlik filtrelemede bu partial'in HTML'i
    innerHTML ile degistigi icin (script tag'leri otomatik calismaz),
    location-filter-script.blade.php her guncellemeden sonra bu global
    fonksiyonu yeniden cagirir.
--}}
@php
  $brokerMapPoints = ($brokerFacilities ?? collect())->map(function ($f) {
      return [
          'name' => $f->name,
          'lat' => (float) $f->lat,
          'lng' => (float) $f->lng,
          'url' => brand_route('facilities.show', ['slug' => $f->slug]),
      ];
  })->values();
@endphp
@if($brokerMapPoints->isNotEmpty())
  <section class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-4">
      <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Haritada Keşfedin</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-950">Anlaşmalı Kurumlar Haritası</h2>
      <p class="text-sm text-gray-600 mt-1">Anlaşmalı kurumlarımızı harita üzerinde görün, bir pine tıklayarak kurumun sayfasına ulaşın.</p>
    </div>
    {{-- 14 Eylul 2026: kullanicinin talebi - mobilde de masaustunde de
         kullanilabilir olmali. Leaflet dokunmatik ekranlarda parmakla
         yakinlastirma/uzaklastirmayi (pinch-zoom) ve suruklemeyi varsayilan
         olarak zaten destekler; burada sadece kucuk ekranda haritanin cok
         yer kaplamamasi icin yukseklik duyarli hale getirildi. --}}
    <div id="broker-map"
         class="rounded-xl overflow-hidden border border-gray-200 h-72 sm:h-96 md:h-[420px]"
         style="width:100%;"
         data-broker-facilities="{{ $brokerMapPoints->toJson() }}"
    ></div>
  </section>
@endif
