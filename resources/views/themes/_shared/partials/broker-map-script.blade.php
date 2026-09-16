{{--
    14 Eylul 2026: kullanicinin talebi - anlaşmalı kurumlar haritasinin
    (bkz. broker-map.blade.php) Leaflet kutuphanesi ve baslatma script'i.
    Bilerek burada, AJAX ile degismeyen SABIT sayfa govdesinde (home.blade.php)
    bir kez yukleniyor - broker-map.blade.php'nin kendisi AJAX ile sik sik
    degistigi icin <script> etiketleri orada calismazdi (innerHTML kisitlamasi).
    window.initBrokerMap global fonksiyonu hem ilk yuklemede hem her AJAX
    guncellemesinden sonra (bkz. location-filter-script.blade.php) cagirilir.
--}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>.broker-map-label{font-weight:800;font-size:12px;padding:2px 6px;}</style>
<script>
window.initBrokerMap = function () {
  var el = document.getElementById('broker-map');
  if (!el || !window.L) return;

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

  var markers = [];
  data.forEach(function (f) {
    if (!f.lat || !f.lng) return;
    var marker = L.marker([f.lat, f.lng]).addTo(map);
    marker.bindTooltip(f.name, { permanent: true, direction: 'top', className: 'broker-map-label' });
    marker.on('click', function () { window.location.href = f.url; });
    marker.getElement && marker.on('add', function () {
      var iconEl = marker.getElement();
      if (iconEl) iconEl.style.cursor = 'pointer';
    });
    markers.push(marker);
  });

  if (markers.length) {
    var group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.3), { maxZoom: 11 });
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
