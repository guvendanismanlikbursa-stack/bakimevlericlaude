{{-- 12 Agustos 2026: kullanicinin talebi - "mesajlasma canli hissetmiyor,
     sayfayi yenilemem gerekiyor". Gercek WebSocket paylasimli hostingde
     kurulamadigi icin (bkz. support-chat-widget.blade.php ayni desen),
     3-4 saniyede bir yeni mesaj yoklanir (polling) + gonderme AJAX ile
     sayfa yenilenmeden yapilir. --}}
<script>
(function () {
  var messagesEl = document.getElementById('js-thread-messages');
  var form = document.getElementById('js-thread-form');
  var input = document.getElementById('js-thread-input');
  if (!messagesEl || !form || !input) return;

  var ownSender = messagesEl.dataset.ownSender;
  var lastId = parseInt(messagesEl.dataset.lastId || '0', 10);
  var pollUrl = @json($pollUrl);
  var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  function renderMessage(m) {
    if (!m || typeof m.id !== 'number') return;
    if (m.id <= lastId) return;
    lastId = Math.max(lastId, m.id);
    messagesEl.dataset.lastId = lastId;

    var emptyNotice = messagesEl.querySelector('.js-thread-empty');
    if (emptyNotice) emptyNotice.remove();

    var wrap = document.createElement('div');
    wrap.className = m.sender_type === ownSender ? 'text-right' : 'text-left';
    var bubble = document.createElement('div');
    bubble.className = 'inline-block px-3 py-2 rounded-lg text-sm ' + (m.sender_type === ownSender ? 'bg-primary text-white' : 'bg-gray-100');
    bubble.textContent = m.body;
    var time = document.createElement('div');
    time.className = 'text-xs text-gray-400 mt-1';
    time.textContent = m.created_at;
    wrap.appendChild(bubble);
    wrap.appendChild(time);
    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function poll() {
    fetch(pollUrl + '?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (data) { (data.messages || []).forEach(renderMessage); })
      .catch(function () {});
  }

  // 14 Agustos 2026: sekme arka plandayken gereksiz sunucu yuku/pil tuketimini
  // onlemek icin polling'i durdurup, sekme tekrar gorunur oldugunda devam ettiriyoruz.
  var pollTimer = setInterval(poll, 3500);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      poll();
      if (!pollTimer) pollTimer = setInterval(poll, 3500);
    } else if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var body = input.value.trim();
    if (!body) return;

    input.value = '';
    input.disabled = true;

    fetch(form.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ body: body }),
    })
      // 14 Agustos 2026: fetch() sadece gercek ag hatasinda reddediyor, HTTP
      // 419 (oturum/CSRF suresi dolmus) gibi hata kodlarinda "basarili" gibi
      // devam ediyordu - data.message bir hata string'i oluyor, renderMessage
      // bunu bozuk bir mesaj sanip goruyor VE lastId'yi NaN'a bulastirip
      // sonraki tum yoklamalari mukerrer mesaj gostermeye basliyordu. Artik
      // basarisiz HTTP durumunda catch bloguna dusup yazilan metni geri koyuyor.
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (data) {
        if (data.message) renderMessage(data.message);
      })
      .catch(function () {
        alert('Mesaj gönderilemedi. Oturumunuz zaman aşımına uğramış olabilir, sayfayı yenileyip tekrar deneyin.');
        input.value = body;
      })
      .finally(function () {
        input.disabled = false;
        input.focus();
      });
  });
})();
</script>
