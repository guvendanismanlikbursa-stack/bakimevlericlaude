<div
  id="js-cookie-consent"
  class="fixed inset-x-0 bottom-0 z-50 hidden bg-gray-950 text-white shadow-[0_-4px_20px_rgba(0,0,0,.25)]"
  role="dialog"
  aria-label="Çerez onayı"
>
  <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
    {{-- 18 Kasım 2026: kullanıcının kararı - "Reddet" ve kapatma yok; iki seçenekten biri seçilmeden band kapanmaz.
         "Yalnızca zorunlu çerezler": Analytics çerezsiz çalışır (bkz. layouts/brand.blade.php izin modu). --}}
    <p class="text-sm text-white/80 flex-1">
      Sitemizin çalışması için zorunlu çerezler kullanılır. Sitemizi iyileştirmek için analiz çerezlerini de kullanmak istiyoruz;
      bunlar yalnızca <b class="text-white">Tümünü kabul et</b> derseniz cihazınıza kaydedilir.
      Detaylar: <a href="{{ brand_route('pages.show', ['slug' => 'cerez-politikasi']) }}" class="underline hover:text-white">Çerez Politikası</a>.
    </p>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
      {{-- Ölçüm kaybını azaltmak için (kullanıcının talebi): "Tümünü kabul et" renkli ve büyük, "zorunlu" sade. Satır içi stil: Tailwind canlıda derlenmiyor. --}}
      <button type="button" id="js-cookie-decline" style="background:transparent;border:1px solid rgba(255,255,255,.22);color:rgba(255,255,255,.7);border-radius:8px;padding:8px 12px;font-size:12px;font-weight:600;cursor:pointer;">Yalnızca zorunlu çerezler</button>
      <button type="button" id="js-cookie-accept" class="bg-primary" style="color:#fff;border:0;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.35);">Tümünü kabul et</button>
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
    if (value === 'tumu') {
      try { window.dispatchEvent(new Event('cerez-izni-verildi')); } catch (e) {}
    }
  }

  var acceptBtn = document.getElementById('js-cookie-accept');
  var declineBtn = document.getElementById('js-cookie-decline');
  if (acceptBtn) acceptBtn.addEventListener('click', function () { setConsent('tumu'); });
  if (declineBtn) declineBtn.addEventListener('click', function () { setConsent('zorunlu'); });
})();
</script>
