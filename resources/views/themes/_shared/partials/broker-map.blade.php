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

    22 Eylul 2026: kullanicinin talebi - bir IL SECILDIGINDE harita artik
    sadece anlaşmalı degil, o ildeki TUM kayitli kurumlari (ön kayıt/
    sahipli/anlaşmalı) 3 farkli renk tonunda gosterir (bkz.
    $registeredMapFacilities, HomeController). Il SECILMEMISKEN eski
    davranis (sadece anlaşmalı, Turkiye geneli) DEGISMEDEN kalir -
    kullanicinin "ulke genelinde binlerce pin birden goruntuleyip
    karisiklik olur mu" endisesi icin BILEREK boyle ayrildi.
--}}
@php
  $ilSeciliHaritaModu = ($registeredMapFacilities ?? collect())->isNotEmpty();

  if ($ilSeciliHaritaModu) {
      $brokerMapPoints = $registeredMapFacilities->map(function ($f) {
          $durum = $f->is_broker_managed ? 'broker_managed' : ($f->is_claimed ? 'claimed' : 'pre_registered');

          return [
              'name' => $f->name,
              'lat' => (float) $f->lat,
              'lng' => (float) $f->lng,
              'url' => brand_route('facilities.show', ['slug' => $f->slug]),
              'status' => $durum,
          ];
      })->values();
  } else {
      $brokerMapPoints = ($brokerFacilities ?? collect())->map(function ($f) {
          return [
              'name' => $f->name,
              'lat' => (float) $f->lat,
              'lng' => (float) $f->lng,
              'url' => brand_route('facilities.show', ['slug' => $f->slug]),
              'status' => 'broker_managed',
          ];
      })->values();
  }
@endphp
@if($brokerMapPoints->isNotEmpty())
  <section class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-4">
      <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Haritada Keşfedin</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-950">{{ $ilSeciliHaritaModu ? 'Bölgedeki Kurumlar Haritası' : 'Anlaşmalı Kurumlar Haritası' }}</h2>
      @if($ilSeciliHaritaModu)
        {{-- 22 Eylul 2026: kullanicinin talebi - anlaşmalı kurumlari
             tesvik edici bir aciklama, harita haric aile buraya bakinca
             "anlaşmalı = daha guvenilir/oncelikli" mesaji gorsun. --}}
        {{-- 22 Eylul 2026 (2): kullanicinin ACIK talebi uzerine renkler
             DEGISTI - anlaşmalı=YESIL, sahipli=MAVI, on kayit=TURUNCU. --}}
        <p class="text-sm text-gray-600 mt-1">
          Bölgenizdeki kayıtlı kurumları harita üzerinde görün.
          <span class="inline-flex items-center gap-1 font-bold" style="color:#16a34a;">🤝 Anlaşmalı</span> kurumlar
          yerinde ziyaret edilmiş, ekibimizce doğrulanmıştır ve taleplerinize öncelikli yanıt verir.
        </p>
        <div class="flex flex-wrap items-center gap-4 mt-3 text-xs font-semibold text-gray-600">
          <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full" style="background:#16a34a;"></span> Anlaşmalı</span>
          <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full" style="background:#2563eb;"></span> Sahiplenilmiş</span>
          <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full" style="background:#f97316;"></span> Kurumlar</span>
        </div>
      @else
        <p class="text-sm text-gray-600 mt-1">Anlaşmalı kurumlarımızı harita üzerinde görün, bir pine tıklayarak kurumun sayfasına ulaşın.</p>
      @endif
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
