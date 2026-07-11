<div id="js-pwa-install-wrap" hidden class="fixed bottom-24 right-5 z-40 flex items-center gap-1 shadow-lg rounded-full" style="background: {{ $brand['primary_color'] }};">
  <button
    type="button"
    id="js-pwa-install-button"
    aria-label="Uygulamayı Yükle"
    class="flex items-center gap-2 pl-4 pr-2 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition-opacity"
  >
    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-white shrink-0" aria-hidden="true"><path d="M12 3a1 1 0 0 1 1 1v9.59l3.3-3.3a1 1 0 1 1 1.4 1.42l-5 5a1 1 0 0 1-1.4 0l-5-5a1 1 0 1 1 1.4-1.42l3.3 3.3V4a1 1 0 0 1 1-1Zm-7 14a1 1 0 0 1 1 1v1h12v-1a1 1 0 1 1 2 0v1a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1a1 1 0 0 1 1-1Z"/></svg>
    Uygulamayı Yükle
  </button>
  <button type="button" id="js-pwa-install-close" aria-label="Kapat" class="w-7 h-7 mr-1.5 flex items-center justify-center rounded-full text-white/80 hover:text-white hover:bg-white/15 text-base leading-none shrink-0">×</button>
</div>

<script>
(function () {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
  }

  var wrapEl = document.getElementById('js-pwa-install-wrap');
  var installButton = document.getElementById('js-pwa-install-button');
  var closeButton = document.getElementById('js-pwa-install-close');
  var deferredPrompt = null;
  var autoHideTimer = null;

  // Zaten yuklu bir PWA icinden aciliyorsa (standalone) ya da kullanici
  // daha once "gizle" demisse bir daha hic gosterme.
  var alreadyInstalled = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
  var dismissed = (function () { try { return localStorage.getItem('pwa_install_dismissed') === '1'; } catch (e) { return false; } })();

  function hideWrap() {
    if (wrapEl) wrapEl.hidden = true;
    if (autoHideTimer) { clearTimeout(autoHideTimer); autoHideTimer = null; }
  }

  function dismissPermanently() {
    hideWrap();
    try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {}
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    if (alreadyInstalled || dismissed) return;
    deferredPrompt = event;
    if (wrapEl) wrapEl.hidden = false;
    // Kullanici hicbir sey yapmazsa 13 saniye sonra kendiliginden kaybolur
    // (kalici gizleme isareti koymadan - bir sonraki sayfa yuklemesinde
    // tekrar gorunebilir), takilip kalan bir uyari olmasin diye.
    autoHideTimer = setTimeout(hideWrap, 13000);
  });

  if (installButton) {
    installButton.addEventListener('click', function () {
      // Prompt gecerli degilse (suresi dolmus/zaten yuklu) bile buton
      // ekranda pasif kalmasin - her durumda gizlenir.
      dismissPermanently();
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt = null;
    });
  }

  if (closeButton) {
    closeButton.addEventListener('click', function (e) {
      e.stopPropagation();
      dismissPermanently();
    });
  }

  window.addEventListener('appinstalled', dismissPermanently);
})();
</script>
