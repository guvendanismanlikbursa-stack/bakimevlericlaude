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

  // 13 Agustos 2026: kullanicinin talebi - "hangi gorselim daha cok ilgi
  // cekiyor goremiyorum". viewedUrlTemplate verilirse, bir gorsel
  // buyutulup acildikca (PhotoSwipe'in kendi 'change' olayi - hem
  // dogrudan <a> tiklamasi hem openFacilityGalleryAt() ile acilan
  // durumlari da kapsar) o gorselin sayaci sunucuda 1 artirilir. Ayni
  // gorsel ayni oturumda tekrar tekrar sayilmasin diye basit bir
  // "gorulenler" seti tutulur.
  window.initFacilityGallery = function (containerId, viewedUrlTemplate) {
    var lightbox = new PhotoSwipeLightbox({
      gallery: '#' + containerId,
      children: 'a',
      pswpModule: PhotoSwipe,
      padding: { top: 20, bottom: 40, left: 20, right: 20 },
    });

    if (viewedUrlTemplate) {
      var seen = {};
      lightbox.on('change', function () {
        try {
          var slide = lightbox.pswp && lightbox.pswp.currSlide;
          var imageId = slide && slide.data && slide.data.element && slide.data.element.dataset.imageId;
          if (!imageId || seen[imageId]) return;
          seen[imageId] = true;
          var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
          fetch(viewedUrlTemplate.replace('__IMAGE_ID__', imageId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          }).catch(function () {});
        } catch (e) {}
      });
    }

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
