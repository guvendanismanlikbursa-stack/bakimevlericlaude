<div
  id="js-cookie-consent"
  class="fixed inset-x-0 bottom-0 z-50 hidden bg-gray-950 text-white shadow-[0_-4px_20px_rgba(0,0,0,.25)]"
  role="dialog"
  aria-label="Çerez onayı"
>
  <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
    <p class="text-sm text-white/80 flex-1">
      Sitemizi kullanarak, deneyiminizi iyileştirmek amacıyla çerez kullanımını kabul etmiş olursunuz.
      Detaylar için <a href="{{ brand_route('pages.show', ['slug' => 'cerez-politikasi']) }}" class="underline hover:text-white">Çerez Politikası</a>'nı inceleyebilirsiniz.
    </p>
    <div class="flex items-center gap-2 shrink-0">
      <button type="button" id="js-cookie-decline" class="rounded-lg border border-white/20 px-4 py-2 text-xs font-bold text-white/80 hover:bg-white/10">Reddet</button>
      <button type="button" id="js-cookie-accept" class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Kabul Et</button>
    </div>
  </div>
</div>
<style>
  {{-- Banner acikken alttaki yuzen butonlarla (WhatsApp/sohbet/PWA) cakismasin diye yukari itilir. --}}
  body.js-cookie-banner-open #js-whatsapp-button,
  body.js-cookie-banner-open #js-chat-toggle { bottom: 5.5rem !important; }
  body.js-cookie-banner-open #js-pwa-install-button { bottom: 9rem !important; }
</style>
<script>
(function () {
  var KEY = 'cookie_consent';
  var banner = document.getElementById('js-cookie-consent');
  if (!banner) return;

  try {
    if (!localStorage.getItem(KEY)) {
      banner.classList.remove('hidden');
      document.body.classList.add('js-cookie-banner-open');
    }
  } catch (e) {
    return;
  }

  function setConsent(value) {
    try { localStorage.setItem(KEY, value); } catch (e) {}
    banner.classList.add('hidden');
    document.body.classList.remove('js-cookie-banner-open');
  }

  var acceptBtn = document.getElementById('js-cookie-accept');
  var declineBtn = document.getElementById('js-cookie-decline');
  if (acceptBtn) acceptBtn.addEventListener('click', function () { setConsent('accepted'); });
  if (declineBtn) declineBtn.addEventListener('click', function () { setConsent('declined'); });
})();
</script>
