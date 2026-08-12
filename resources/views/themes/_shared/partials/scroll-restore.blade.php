{{--
  28 Temmuz 2026: sekme/filtre/durum linkleri (ayni sayfa, sadece query-string
  degisen <a> linkleri veya GET formlari - ornegin admin'deki "Bekleyenler /
  Onaylananlar / Reddedilenler" sekmeleri, kurum davetleri gruplari, veya
  public sitedeki bolum secim kartlari) TAM SAYFA YENILEMESI yaptigi icin
  tarayici otomatik olarak sayfayi en ustten actiyordu - kullanici listenin
  ortasindaki bir sekmeye bastiginda sayfanin tepesine firlatiliyordu.

  Bu script, ayni SAYFA YOLUNA (pathname) giden bir link'e/GET forma
  tiklandigi anda mevcut scroll konumunu sessionStorage'a yazar, yeni sayfa
  yuklendiginde (yol ayniysa) o konuma geri doner. Farkli bir sayfaya giden
  linkler (or. bir kurumun detay sayfasi) etkilenmez - orada sayfanin en
  ustten acilmasi zaten dogru/beklenen davranistir.
--}}
<script>
(function () {
  var STORAGE_KEY = 'scrollRestore:v1';

  function samePathAsCurrent(url) {
    try {
      var a = document.createElement('a');
      a.href = url;
      return a.hostname === window.location.hostname && a.pathname === window.location.pathname;
    } catch (e) {
      return false;
    }
  }

  function saveScroll() {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        path: window.location.pathname,
        y: window.scrollY,
      }));
    } catch (e) {}
  }

  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var link = e.target.closest('a[href]');
    if (!link || link.target === '_blank') return;
    if (!samePathAsCurrent(link.href)) return;
    saveScroll();
  }, true);

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || (form.method || 'get').toUpperCase() !== 'GET') return;
    var action = form.getAttribute('action') || window.location.href;
    if (!samePathAsCurrent(action)) return;
    saveScroll();
  }, true);

  try {
    var saved = sessionStorage.getItem(STORAGE_KEY);
    if (saved) {
      sessionStorage.removeItem(STORAGE_KEY);
      var data = JSON.parse(saved);
      if (data && data.path === window.location.pathname && data.y > 0) {
        var restore = function () { window.scrollTo(0, data.y); };
        restore();
        requestAnimationFrame(restore);
        setTimeout(restore, 80);
      }
    }
  } catch (e) {}
})();
</script>
