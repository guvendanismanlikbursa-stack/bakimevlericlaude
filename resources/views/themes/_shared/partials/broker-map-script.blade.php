{{--
    14 Eylul 2026: kullanicinin talebi - anlaşmalı kurumlar haritasinin
    (bkz. broker-map.blade.php) Leaflet kutuphanesi ve baslatma script'i.
    Bilerek burada, AJAX ile degismeyen SABIT sayfa govdesinde (home.blade.php)
    bir kez yukleniyor - broker-map.blade.php'nin kendisi AJAX ile sik sik
    degistigi icin <script> etiketleri orada calismazdi (innerHTML kisitlamasi).
    window.initBrokerMap global fonksiyonu hem ilk yuklemede hem her AJAX
    guncellemesinden sonra (bkz. location-filter-script.blade.php) cagirilir.

    22 Eylul 2026: kullanicinin talebi - bir il secilince harita artik
    yuzlerce pin gosterebiliyor (on kayit+sahipli+anlaşmalı). Kalabalik/
    karisik gorunmesin diye Leaflet.markercluster eklendi (yakin pin'ler
    yakinlastirilana kadar sayi baloncugunda toplanir). Durum'a gore renk:
    anlaşmalı=mavi (buyuk, dikkat cekici), sahipli=yesil, on kayit=gri.
--}}
{{-- 20 Ekim 2026: PageSpeed raporu - Leaflet (harita) dosyaları sayfa açılırken
     yükleniyor, ilk görüntüyü geciktiriyor ve harita karoları ilk ekranda
     iniyordu. Artık harita ekrana yaklaşınca (IntersectionObserver) yüklenir. --}}
<script>
window.__leafletYukle = function () {
  if (window.__leafletSoz) return window.__leafletSoz;
  function css(href) {
    var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; l.crossOrigin = '';
    document.head.appendChild(l);
  }
  function js(src, sri) {
    return new Promise(function (ok, hata) {
      var s = document.createElement('script'); s.src = src; s.crossOrigin = '';
      if (sri) s.integrity = sri;
      s.onload = ok; s.onerror = hata; document.head.appendChild(s);
    });
  }
  css('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
  css('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css');
  css('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css');
  window.__leafletSoz = js('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=')
    .then(function () { return js('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js').catch(function () {}); });
  return window.__leafletSoz;
};
</script>
<style>
.broker-map-label{font-weight:800;font-size:12px;padding:2px 6px;}
/* 22 Eylul 2026: kullanicinin talebi - duz daire "topluigne" gibi
   gorunuyordu, normal harita pini (damla/teardrop) sekli istendi. Klasik
   CSS damla hilesi: kare, ust-sol HARIC uc kosesi yuvarlatilip -45 derece
   dondurulur - alt-sol uc sivri kalir (koordinatin tam oldugu nokta),
   ust taraf yuvarlak gorunur. Wrapper'in ALT-ORTASI (iconAnchor) bu sivri
   uca denk gelecek sekilde boyutlandirildi. */
.rm-pin-wrap{position:relative; width:100%; height:100%;}
.rm-pin{
  position:absolute; top:0; left:50%;
  border-radius:50% 50% 50% 0;
  border:2px solid #fff;
  box-shadow:0 2px 5px rgba(0,0,0,.5);
  transform:rotate(-45deg);
  transform-origin:center;
}
/* 22 Eylul 2026 (2): kullanicinin ACIK talebi uzerine renkler DEGISTI -
   anlaşmalı=YESIL, sahipli=MAVI, on kayit=TURUNCU (gri "belli olmuyor"
   diye reddedildi). */
.rm-pin-broker{width:30px;height:30px;margin-left:-15px;background:#16a34a;}
.rm-pin-claimed{width:24px;height:24px;margin-left:-12px;background:#2563eb;}
.rm-pin-pre{width:22px;height:22px;margin-left:-11px;background:#f97316;}
.rm-pin-emoji{
  position:absolute; top:6px; left:50%;
  transform:translateX(-50%);
  font-size:13px; line-height:1;
}
/* 22 Eylul 2026 (2): kullanicinin talebi - "ana sayfadaki gibi net
   belirgin olmali, pin uzerinde kurum ismi yazmali" - eskiden sadece
   anlaşmalı kurumlarda kalici etiket vardi, simdi TUMUNDE. */
.rm-label{font-weight:800;font-size:11px;padding:2px 7px;white-space:nowrap;}
.rm-label-broker{background:#16a34a;color:#fff;border-color:#16a34a!important;}
.rm-label-claimed{background:#2563eb;color:#fff;border-color:#2563eb!important;}
.rm-label-pre{background:#f97316;color:#fff;border-color:#f97316!important;}
</style>
<script>
window.initBrokerMap = function () {
  var el = document.getElementById('broker-map');
  if (!el) return;
  if (!window.L) {
    // Harita kütüphanesi henüz yüklenmedi: harita ekrana yaklaşınca yükle.
    var hazirla = function () { window.__leafletYukle().then(window.initBrokerMap); };
    if (!('IntersectionObserver' in window)) { hazirla(); return; }
    if (el.__haritaGozlemci) return;
    el.__haritaGozlemci = new IntersectionObserver(function (girdiler, gozlemci) {
      if (girdiler.some(function (g) { return g.isIntersecting; })) { gozlemci.disconnect(); hazirla(); }
    }, { rootMargin: '400px' });
    el.__haritaGozlemci.observe(el);
    return;
  }

  var data = [];
  try { data = JSON.parse(el.dataset.brokerFacilities || '[]'); } catch (e) { data = []; }

  if (el._brokerMapInstance) {
    el._brokerMapInstance.remove();
    el._brokerMapInstance = null;
  }
  if (!data.length) return;

  var map = L.map(el).setView([39.0, 35.0], 6);
  el._brokerMapInstance = map;
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18,
  }).addTo(map);

  // 22 Eylul 2026: kullanicinin talebi - gercek harita pini (damla) sekli,
  // durum bazli renk/boyut. Anlaşmalı kurumlar en buyuk + 🤝 emoji ile
  // bilerek en dikkat cekici, on kayitli kurumlar en sade/kucuk.
  // iconAnchor [genislik/2, yukseklik] wrapper'in ALT-ORTASINI (pin'in
  // sivri ucu) tam koordinata oturtur.
  function iconFor(status) {
    if (status === 'broker_managed') {
      return L.divIcon({
        className: '', html: '<div class="rm-pin-wrap"><div class="rm-pin rm-pin-broker"></div><div class="rm-pin-emoji">🤝</div></div>',
        iconSize: [30, 39], iconAnchor: [15, 39],
      });
    }
    if (status === 'claimed') {
      return L.divIcon({
        className: '', html: '<div class="rm-pin-wrap"><div class="rm-pin rm-pin-claimed"></div></div>',
        iconSize: [24, 31], iconAnchor: [12, 31],
      });
    }
    return L.divIcon({
      className: '', html: '<div class="rm-pin-wrap"><div class="rm-pin rm-pin-pre"></div></div>',
      iconSize: [22, 28], iconAnchor: [11, 28],
    });
  }

  function labelClassFor(status) {
    if (status === 'broker_managed') return 'rm-label rm-label-broker';
    if (status === 'claimed') return 'rm-label rm-label-claimed';
    return 'rm-label rm-label-pre';
  }

  var hasCluster = !!window.L.markerClusterGroup;
  var layer = hasCluster ? L.markerClusterGroup({ maxClusterRadius: 45, disableClusteringAtZoom: 15 }) : L.featureGroup();

  var markers = [];
  data.forEach(function (f) {
    if (!f.lat || !f.lng) return;
    var marker = L.marker([f.lat, f.lng], { icon: iconFor(f.status) });
    // 22 Eylul 2026 (2): kullanicinin ACIK talebi - "ana sayfadaki gibi
    // net belirgin olmali, pinin uzerinde kurum ismi yazmali" - artik
    // TUM durumlarda (sadece anlaşmalı degil) kalici, renkli etiket var.
    // Kalabalik sorunu zaten kumeleme (markercluster) ile cozuluyor -
    // pin'ler ancak yeterince ayrisinca tek tek gorunur, o noktada
    // etiketler okunabilir kalir.
    marker.bindTooltip(f.name, { permanent: true, direction: 'top', className: labelClassFor(f.status), offset: [0, -10] });
    marker.on('click', function () { window.location.href = f.url; });
    marker.on('add', function () {
      var iconEl = marker.getElement();
      if (iconEl) iconEl.style.cursor = 'pointer';
    });
    layer.addLayer(marker);
    markers.push(marker);
  });
  map.addLayer(layer);

  if (markers.length) {
    var group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.3), { maxZoom: 13 });
  }

  // 14 Eylul 2026: kullanicinin talebi - mobil/masaustu gecisinde (ekran
  // dondurme, pencere boyutu degisimi) harita container'i Tailwind'in
  // duyarli yukseklik siniflariyla (h-72 sm:h-96 md:h-[420px]) boyut
  // degistirir; Leaflet bunu kendiliginden fark etmez, invalidateSize
  // cagirilmazsa harita bos/kirpilmis gorunebilir.
  setTimeout(function () { map.invalidateSize(); }, 150);
};

// 14 Eylul 2026: resize dinleyicisi TEK SEFER kayit edilir (her AJAX
// guncellemesinde initBrokerMap tekrar cagrildigi icin, dinleyici o
// fonksiyonun icine konsaydi her seferinde bir yenisi eklenip birikirdi) -
// her zaman O ANKI aktif harita instance'ina (el._brokerMapInstance) erisir.
window.addEventListener('resize', function () {
  var el = document.getElementById('broker-map');
  if (el && el._brokerMapInstance) el._brokerMapInstance.invalidateSize();
});

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', window.initBrokerMap);
} else {
  window.initBrokerMap();
}
</script>
