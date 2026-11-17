@php
  // 14 Ekim 2026: kullanıcının talebi - "admin tarafındaki mantık
  // çatışmalarının hepsini düzelt - admin panelinde hiç sesli uyarı yoktu".
  // Bu partial daha önce SADECE aile/kurum oturumlarında çalışıyordu (admin
  // panelinde hiç include edilmemişti) - claims/registrations/chat gibi
  // ZATEN bildirim gönderen türler bile admin ekranında sessiz kalıyordu.
  // Admin'de rozet, markaya özel renk yerine sabit koyu bir ton kullanır
  // (admin panelinin kendi renk şeması, bkz. admin/layout.blade.php).
  $panelType = session('facility_user_id') ? 'facility' : (session('family_user_id') ? 'family' : (session('admin_id') ? 'admin' : null));
@endphp
@if($panelType)
<div id="js-notify-reminder-wrap" hidden class="fixed bottom-24 right-5 z-40 max-w-xs rounded-xl shadow-lg p-4 text-sm" style="background: {{ $panelType === 'admin' ? '#111827' : $brand['primary_color'] }}; color: #fff;">
  <button type="button" id="js-notify-reminder-close" aria-label="Kapat" class="absolute top-1.5 right-2 text-white/70 hover:text-white text-base leading-none">×</button>
  <p class="font-semibold pr-4">🔔 Bildirimlerinizi açmayı unutmayınız</p>
  <p class="text-white/80 text-xs mt-1">Gelen mesaj ve talepleri kaçırmamak için tarayıcı bildirim iznini açık tutun.</p>
</div>

@if($panelType === 'admin')
{{-- 17 Kasım 2026: kullanıcının talebi - canlı sohbette yeni mesaj gelince admin panelinin hangi sayfasındaysa
     (Genel Bakış dahil) ekranda görsel uyarı çıksın; yalnızca ses ve kenar menüdeki küçük sayı yetmiyordu.
     Renkler satır içi verildi (derlenmiş CSS'te olmayan sınıflar canlıda çalışmıyor). --}}
<a id="js-sohbet-uyari" href="#" hidden style="display:none; position:fixed; top:16px; right:16px; z-index:60; max-width:340px; background:#16a34a; color:#fff; border-radius:14px; padding:12px 34px 12px 14px; box-shadow:0 10px 25px rgba(0,0,0,.25); font-size:14px; line-height:1.35; text-decoration:none;">
  <span style="font-weight:700; display:block;">💬 Yeni canlı sohbet mesajı</span>
  <span id="js-sohbet-uyari-metin" style="display:block; font-size:12.5px; opacity:.92; margin-top:3px;"></span>
  <span style="display:block; font-size:12px; font-weight:700; margin-top:6px; text-decoration:underline;">Sohbeti aç →</span>
  <button type="button" id="js-sohbet-uyari-kapat" aria-label="Kapat" style="position:absolute; top:6px; right:10px; background:none; border:0; color:#fff; font-size:18px; line-height:1; cursor:pointer;">×</button>
</a>
@endif

<script>
(function () {
  var panelType = @json($panelType);
  var countUrl = @json(match($panelType){'facility' => brand_route('facility.notifications.unread-count'), 'admin' => route('admin.notifications.unread-count'), default => brand_route('family.notifications.unread-count')});

  // --- Sesli uyari: panel acikken okunmamis bildirim sayisi artarsa kisa
  // bir "ping" calar (Web Audio API ile uretiliyor, ayrica bir ses dosyasi
  // gerektirmiyor). Sadece aile/kurum panelinde calisir, genel ziyaretci
  // sayfalarinda hic yuklenmez (bkz. $panelType kontrolu yukarida).
  var lastCount = null;

  function playPing() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = new Ctx();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.35);
    } catch (e) {}
  }

  // 17 Kasım 2026: admin panelinde canlı sohbet için görsel uyarı + kenar menü sayacının sayfa yenilemeden güncellenmesi.
  var lastSohbetId = null;
  var sohbetUyari = document.getElementById('js-sohbet-uyari');
  var sohbetUyariKapat = document.getElementById('js-sohbet-uyari-kapat');
  if (sohbetUyariKapat) sohbetUyariKapat.addEventListener('click', function (e) {
    e.preventDefault(); e.stopPropagation();
    sohbetUyari.hidden = true; sohbetUyari.style.display = 'none';
  });

  function sohbetGuncelle(data) {
    if (panelType !== 'admin' || !('sohbet_okunmamis' in data)) return false;
    var rozet = document.getElementById('js-admin-sohbet-rozet');
    if (rozet) { rozet.textContent = data.sohbet_okunmamis; rozet.style.display = data.sohbet_okunmamis > 0 ? '' : 'none'; }
    var son = data.sohbet_son;
    var yeni = false;
    // Zaten o sohbetin sayfasındaysa mesaj ekranda belirir, ayrıca uyarı gösterilmez.
    if (son && lastSohbetId !== null && son.id > lastSohbetId && location.href.split('?')[0] !== son.url && sohbetUyari) {
      document.getElementById('js-sohbet-uyari-metin').textContent = son.ad + (son.site ? ' (' + son.site + ')' : '') + ': ' + son.onizleme;
      sohbetUyari.href = son.url;
      sohbetUyari.hidden = false; sohbetUyari.style.display = 'block';
      yeni = true;
    }
    if (son) lastSohbetId = son.id; else if (lastSohbetId === null) lastSohbetId = 0;
    return yeni;
  }

  function pollUnread() {
    fetch(countUrl, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (data) {
        var yeniSohbet = sohbetGuncelle(data);
        if ((lastCount !== null && data.count > lastCount) || yeniSohbet) playPing();
        lastCount = data.count;
      })
      .catch(function () {});
  }

  pollUnread();
  // 14 Agustos 2026: sekme arka plandayken gereksiz sunucu yuku/pil tuketimini
  // onlemek icin polling'i durdurup, sekme tekrar gorunur oldugunda devam ettiriyoruz.
  var pollTimer = setInterval(pollUnread, 20000);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      pollUnread();
      if (!pollTimer) pollTimer = setInterval(pollUnread, 20000);
    } else if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  });

  // --- "Bildirimlerinizi acin" hatirlatici: bu oturumda (sekme acikken)
  // ilk panel girisinde 3-5 saniye sonra kendiliginden kaybolan bir not -
  // bugun duzelttigimiz PWA install banner'iyla ayni inline-style deseni
  // (CSS kaskad hatasina dusmeden gizlemek icin, bkz. o dosyadaki not).
  var reminderKey = 'panel_notify_reminder_shown_' + panelType;
  var alreadyShown = false;
  try { alreadyShown = sessionStorage.getItem(reminderKey) === '1'; } catch (e) {}

  var wrap = document.getElementById('js-notify-reminder-wrap');
  var closeBtn = document.getElementById('js-notify-reminder-close');

  function hideReminder() {
    if (wrap) { wrap.hidden = true; wrap.style.display = 'none'; }
  }

  if (!alreadyShown && wrap && window.Notification && Notification.permission !== 'granted') {
    wrap.hidden = false;
    wrap.style.display = 'block';
    try { sessionStorage.setItem(reminderKey, '1'); } catch (e) {}
    setTimeout(hideReminder, 4000);
  }

  if (closeBtn) closeBtn.addEventListener('click', hideReminder);
})();
</script>
@endif
