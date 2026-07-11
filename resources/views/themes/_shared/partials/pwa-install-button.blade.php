<button
  type="button"
  id="js-pwa-install-button"
  hidden
  aria-label="Uygulamayı Yükle"
  class="fixed bottom-24 right-5 z-40 flex items-center gap-2 px-4 py-2.5 rounded-full shadow-lg text-sm font-semibold text-white hover:scale-105 transition-transform"
  style="background: {{ $brand['primary_color'] }};"
>
  <svg viewBox="0 0 24 24" class="w-5 h-5 fill-white" aria-hidden="true"><path d="M12 3a1 1 0 0 1 1 1v9.59l3.3-3.3a1 1 0 1 1 1.4 1.42l-5 5a1 1 0 0 1-1.4 0l-5-5a1 1 0 1 1 1.4-1.42l3.3 3.3V4a1 1 0 0 1 1-1Zm-7 14a1 1 0 0 1 1 1v1h12v-1a1 1 0 1 1 2 0v1a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1a1 1 0 0 1 1-1Z"/></svg>
  Uygulamayı Yükle
</button>

<script>
(function () {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
  }

  var installButton = document.getElementById('js-pwa-install-button');
  var deferredPrompt = null;

  // Zaten yuklu bir PWA icinden aciliyorsa (standalone) ya da kullanici
  // daha once "gizle" demisse bir daha hic gosterme.
  var alreadyInstalled = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
  var dismissed = (function () { try { return localStorage.getItem('pwa_install_dismissed') === '1'; } catch (e) { return false; } })();

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    if (alreadyInstalled || dismissed) return;
    deferredPrompt = event;
    if (installButton) installButton.hidden = false;
  });

  if (installButton) {
    installButton.addEventListener('click', function () {
      // Prompt gecerli degilse (suresi dolmus/zaten yuklu) bile buton
      // ekranda pasif kalmasin - her durumda gizlenir.
      installButton.hidden = true;
      try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {}
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt = null;
    });
  }

  window.addEventListener('appinstalled', function () {
    if (installButton) installButton.hidden = true;
    try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {}
  });
})();
</script>
