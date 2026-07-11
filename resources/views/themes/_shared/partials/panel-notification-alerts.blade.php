@php
  $panelType = session('facility_user_id') ? 'facility' : (session('family_user_id') ? 'family' : null);
@endphp
@if($panelType)
<div id="js-notify-reminder-wrap" hidden class="fixed bottom-24 right-5 z-40 max-w-xs rounded-xl shadow-lg p-4 text-sm" style="background: {{ $brand['primary_color'] }}; color: #fff;">
  <button type="button" id="js-notify-reminder-close" aria-label="Kapat" class="absolute top-1.5 right-2 text-white/70 hover:text-white text-base leading-none">×</button>
  <p class="font-semibold pr-4">🔔 Bildirimlerinizi açmayı unutmayınız</p>
  <p class="text-white/80 text-xs mt-1">Gelen mesaj ve teklifleri kaçırmamak için tarayıcı bildirim iznini açık tutun.</p>
</div>

<script>
(function () {
  var panelType = @json($panelType);
  var countUrl = @json($panelType === 'facility' ? brand_route('facility.notifications.unread-count') : brand_route('family.notifications.unread-count'));

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

  function pollUnread() {
    fetch(countUrl, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (lastCount !== null && data.count > lastCount) playPing();
        lastCount = data.count;
      })
      .catch(function () {});
  }

  pollUnread();
  setInterval(pollUnread, 20000);

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
