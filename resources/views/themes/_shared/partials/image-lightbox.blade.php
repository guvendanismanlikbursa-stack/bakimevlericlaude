{{-- 16 Temmuz 2026: kurum galerisi gorselleri (hem kurum inceleme sayfasi hem
     kurum paneli) sadece statik <img> olarak duruyordu, buyutme/kaydirma yoktu.
     PhotoSwipe (MIT lisansli, bagimsiz - CDN degil, public/vendor altinda
     kendi sunucumuzdan servis ediliyor) ile profesyonel bir lightbox eklendi:
     dokunmatik kaydirma, klavye oklari, pinch-zoom, sayac. --}}
<link rel="stylesheet" href="{{ asset('assets/photoswipe/photoswipe.css') }}">
<script src="{{ asset('assets/photoswipe/photoswipe.umd.min.js') }}"></script>
<script src="{{ asset('assets/photoswipe/photoswipe-lightbox.umd.min.js') }}"></script>
<script>
  window.facilityGalleries = window.facilityGalleries || {};

  window.initFacilityGallery = function (containerId) {
    var lightbox = new PhotoSwipeLightbox({
      gallery: '#' + containerId,
      children: 'a',
      pswpModule: PhotoSwipe,
      padding: { top: 20, bottom: 40, left: 20, right: 20 },
    });
    lightbox.init();
    window.facilityGalleries[containerId] = lightbox;
    return lightbox;
  };

  // Galeride olmayan (ana gorsel/yan izgara gibi ayni gorsellerin buyuk
  // kopyalari) bir tiklamayi, ayni galerinin dogru index'inden acar - boylece
  // ayni gorsel PhotoSwipe'a iki kez "ayri gorsel" olarak eklenmez.
  window.openFacilityGalleryAt = function (containerId, index) {
    var gallery = window.facilityGalleries[containerId];
    if (gallery) gallery.loadAndOpen(index);
  };
</script>
