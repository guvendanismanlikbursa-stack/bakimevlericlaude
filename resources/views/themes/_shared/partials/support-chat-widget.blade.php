@php
  $waDefaults = config('platform.default_whatsapp');
  $waNumber = \App\Models\Setting::get('whatsapp_number', $waDefaults['number']);
  $waMessage = str_replace('{marka}', $brand['name'], \App\Models\Setting::get('whatsapp_message', $waDefaults['message']));
@endphp
{{-- Canli destek ikonu: tek ikon, WhatsApp'a erisim asagidaki menude --}}
<button
  type="button"
  id="js-chat-toggle"
  aria-label="Canlı destek"
  class="fixed bottom-5 right-5 z-40 flex items-center justify-center w-14 h-14 rounded-full shadow-lg animate-[wa-bounce-in_.5s_ease-out] hover:scale-105 transition-transform"
  style="background: {{ $brand['primary_color'] }};"
>
  <svg viewBox="0 0 24 24" class="w-7 h-7 fill-white" aria-hidden="true">
    <path d="M12 2C6.48 2 2 5.94 2 10.8c0 2.62 1.32 4.96 3.4 6.56-.09.99-.4 2.4-1.24 3.72-.15.24.06.54.34.5 1.9-.28 3.34-1.05 4.28-1.68.99.26 2.06.4 3.22.4 5.52 0 10-3.94 10-8.8S17.52 2 12 2Z"/>
  </svg>
</button>

<div id="js-chat-menu" class="fixed bottom-24 right-5 z-50 hidden w-72 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
  <div class="px-4 py-3 text-white text-sm font-black" style="background: {{ $brand['primary_color'] }};">Size nasıl yardımcı olabiliriz?</div>
  <div class="p-2">
    <button type="button" class="js-intent-btn w-full text-left px-3 py-2.5 rounded-lg hover:bg-gray-50 text-sm font-semibold text-gray-800" data-intent="sohbet">💬 Sohbet etmek istiyorum</button>
    <button type="button" class="js-intent-btn w-full text-left px-3 py-2.5 rounded-lg hover:bg-gray-50 text-sm font-semibold text-gray-800" data-intent="dertlesme">🤍 Dertleşmek istiyorum</button>
    <button type="button" class="js-intent-btn w-full text-left px-3 py-2.5 rounded-lg hover:bg-gray-50 text-sm font-semibold text-gray-800" data-intent="fikir">💡 Fikir almak istiyorum</button>
    <button type="button" class="js-intent-btn w-full text-left px-3 py-2.5 rounded-lg hover:bg-gray-50 text-sm font-semibold text-gray-800" data-intent="temsilci">🎧 Müşteri temsilcisi lütfen</button>
  </div>
  <a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode($waMessage) }}" target="_blank" rel="noopener" class="flex items-center gap-2 px-4 py-3 border-t border-gray-100 text-sm font-semibold text-gray-600 hover:bg-gray-50">
    <svg viewBox="0 0 32 32" class="w-5 h-5 fill-[#25D366]" aria-hidden="true"><path d="M16.004 3C9.376 3 4 8.373 4 15c0 2.288.638 4.428 1.744 6.252L4 29l7.94-1.71A11.94 11.94 0 0 0 16.004 27C22.63 27 28 21.627 28 15S22.63 3 16.004 3Z"/></svg>
    WhatsApp'tan yaz
  </a>
</div>

<div id="js-chat-gender" class="fixed bottom-24 right-5 z-50 hidden w-72 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
  <div class="px-4 py-3 text-white text-sm font-black" style="background: {{ $brand['primary_color'] }};">Kiminle görüşmek istersiniz?</div>
  <div class="p-3 grid grid-cols-2 gap-2">
    <button type="button" class="js-gender-btn rounded-xl border border-gray-200 py-4 text-sm font-bold text-gray-800 hover:border-gray-400" data-gender="erkek">👨 Bay</button>
    <button type="button" class="js-gender-btn rounded-xl border border-gray-200 py-4 text-sm font-bold text-gray-800 hover:border-gray-400" data-gender="kadin">👩 Bayan</button>
  </div>
  <button type="button" class="js-gender-btn w-full text-center px-4 py-2.5 text-xs font-semibold text-gray-500 hover:bg-gray-50" data-gender="farketmez">Farketmez, önemli değil</button>
</div>

<div id="js-chat-panel" class="fixed bottom-5 right-5 z-50 hidden w-[min(92vw,360px)] h-[min(78vh,560px)] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden">
  <div class="flex items-center gap-3 px-4 py-3 text-white shrink-0" style="background: {{ $brand['primary_color'] }};">
    <div id="js-chat-eye" class="relative w-9 h-9 rounded-full bg-white shrink-0 overflow-hidden">
      <div id="js-chat-pupil" class="absolute w-3.5 h-3.5 rounded-full" style="top:50%;left:50%;margin:-7px 0 0 -7px;"></div>
    </div>
    <div class="flex-1 min-w-0">
      <div class="text-sm font-black">Sizi dinliyoruz</div>
      <div id="js-chat-status" class="text-xs text-white/80">Bağlanıyor…</div>
    </div>
    <button type="button" id="js-chat-topic-btn" aria-label="Konu değiştir" title="Konu değiştir" class="text-white/80 hover:text-white text-lg leading-none px-1">☰</button>
    <button type="button" id="js-chat-close" aria-label="Kapat" class="text-white/80 hover:text-white text-xl leading-none px-1">×</button>
  </div>

  <div id="js-chat-offline-banner" class="hidden px-4 py-2 text-xs font-semibold text-amber-800 bg-amber-50 border-b border-amber-100"></div>

  <div id="js-chat-messages" class="flex-1 overflow-y-auto px-3 py-3 space-y-2 bg-gray-50"></div>

  <div class="border-t border-gray-100 p-2 shrink-0">
    <div id="js-chat-attach-preview" class="hidden px-2 pb-2 text-xs text-gray-500"></div>
    <div class="flex items-end gap-1.5">
      <input type="file" id="js-chat-file" class="hidden" accept="image/jpeg,image/png,image/webp,video/mp4,.pdf,.doc,.docx">
      <button type="button" id="js-chat-attach-btn" aria-label="Dosya ekle" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
        <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M17.5 6.5v9a4 4 0 1 1-8 0v-8a2.5 2.5 0 1 1 5 0v7a1 1 0 1 1-2 0v-7a.5.5 0 0 0-1 0v8a2.5 2.5 0 1 0 5 0v-9a4 4 0 0 0-8 0v8a5.5 5.5 0 1 0 11 0v-9h-2Z"/></svg>
      </button>
      <button type="button" id="js-chat-mic-btn" aria-label="Sesli yaz" class="hidden shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
        <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M12 15a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3Zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.93V21h2v-2.07A7 7 0 0 0 19 12h-2Z"/></svg>
      </button>
      <textarea id="js-chat-input" rows="1" placeholder="Mesajınızı yazın…" class="flex-1 resize-none max-h-24 rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-gray-400"></textarea>
      <button type="button" id="js-chat-send" aria-label="Gönder" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-white" style="background: {{ $brand['primary_color'] }};">
        <svg viewBox="0 0 24 24" class="w-4 h-4 fill-current"><path d="M3 20l18-8L3 4v6l12 2-12 2v6Z"/></svg>
      </button>
    </div>
  </div>
</div>

<style>
  #js-chat-pupil { background: {{ $brand['primary_color'] }}; transition: transform .05s linear; }
  #js-chat-eye.js-eye-idle #js-chat-pupil { animation: eye-blink 4s ease-in-out infinite; }
  @keyframes eye-blink { 0%, 92%, 100% { transform: scaleY(1); } 96% { transform: scaleY(0.15); } }
  .js-chat-bubble { max-width: 82%; padding: .5rem .75rem; border-radius: .9rem; font-size: .83rem; line-height: 1.35; word-wrap: break-word; }
  .js-chat-bubble.guest { margin-left: auto; background: {{ $brand['primary_color'] }}; color: #fff; border-bottom-right-radius: .25rem; }
  .js-chat-bubble.admin { margin-right: auto; background: #fff; color: #1f2937; border: 1px solid #e5e7eb; border-bottom-left-radius: .25rem; }
  .js-chat-bubble img, .js-chat-bubble video { max-width: 100%; border-radius: .5rem; margin-top: .25rem; }
</style>

<script>
(function () {
  var brandSlug = @json($brand['slug']);
  var storageKey = 'support_chat_token_' + brandSlug;

  var toggleBtn = document.getElementById('js-chat-toggle');
  var menuEl = document.getElementById('js-chat-menu');
  var genderEl = document.getElementById('js-chat-gender');
  var panelEl = document.getElementById('js-chat-panel');
  if (!toggleBtn || !menuEl || !genderEl || !panelEl) return;

  var statusEl = document.getElementById('js-chat-status');
  var offlineBanner = document.getElementById('js-chat-offline-banner');
  var messagesEl = document.getElementById('js-chat-messages');
  var inputEl = document.getElementById('js-chat-input');
  var sendBtn = document.getElementById('js-chat-send');
  var closeBtn = document.getElementById('js-chat-close');
  var fileInput = document.getElementById('js-chat-file');
  var attachBtn = document.getElementById('js-chat-attach-btn');
  var attachPreview = document.getElementById('js-chat-attach-preview');
  var micBtn = document.getElementById('js-chat-mic-btn');
  var eyeEl = document.getElementById('js-chat-eye');
  var pupilEl = document.getElementById('js-chat-pupil');
  var topicBtn = document.getElementById('js-chat-topic-btn');

  var startUrl = @json(brand_route('support-chat.start'));
  var sendUrlTemplate = @json(brand_route('support-chat.send', ['thread' => 'THREAD_ID']));
  var pollUrlTemplate = @json(brand_route('support-chat.poll', ['thread' => 'THREAD_ID']));
  var facilitiesUrlTemplate = @json(brand_route('facilities.index', ['bolum' => 'SECTION_SLUG']));
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

  var state = { threadId: null, guestToken: null, lastMessageId: 0, pollTimer: null, selectedIntent: null };
  var pendingFile = null;

  function hideAllPanels() { menuEl.classList.add('hidden'); genderEl.classList.add('hidden'); panelEl.classList.add('hidden'); }

  toggleBtn.addEventListener('click', function () {
    if (!panelEl.classList.contains('hidden')) { hideAllPanels(); return; }
    if (!menuEl.classList.contains('hidden')) { hideAllPanels(); return; }

    var savedToken = localStorage.getItem(storageKey);
    if (savedToken) {
      openPanel(savedToken, null, null);
    } else {
      hideAllPanels();
      menuEl.classList.remove('hidden');
    }
  });

  closeBtn.addEventListener('click', function () {
    hideAllPanels();
    if (state.pollTimer) clearInterval(state.pollTimer);
  });

  // Sohbet devam ederken kullanici konu/niyet degistirmek isteyebilir -
  // menuyu tekrar acar, secim yapinca AYNI thread'in niyeti guncellenir
  // (bkz. captureLocationAndStart -> state.guestToken), mesaj gecmisi kaybolmaz.
  topicBtn.addEventListener('click', function () {
    panelEl.classList.add('hidden');
    menuEl.classList.remove('hidden');
  });

  document.querySelectorAll('.js-intent-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.selectedIntent = btn.getAttribute('data-intent');
      hideAllPanels();
      genderEl.classList.remove('hidden');
    });
  });

  document.querySelectorAll('.js-gender-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var gender = btn.getAttribute('data-gender');
      hideAllPanels();
      captureLocationAndStart(state.selectedIntent, gender);
    });
  });

  // Konum: WhatsApp butonundaki desenle birebir ayni - kisa timeout, izin
  // verilsin/verilmesin istek her zaman gonderilir, kullanici asla beklemez.
  // state.guestToken doluysa (kullanici zaten bir sohbete baslamis, sadece
  // konu degistiriyor) o mevcut thread'e baglanip niyeti gunceller - yeni
  // bir thread acmaz, mesaj gecmisi kaybolmaz.
  function captureLocationAndStart(intent, gender) {
    var token = state.guestToken || null;
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (pos) {
        startThread(token, intent, gender, pos.coords.latitude, pos.coords.longitude);
      }, function () {
        startThread(token, intent, gender, null, null);
      }, { timeout: 4000, maximumAge: 60000 });
    } else {
      startThread(token, intent, gender, null, null);
    }
  }

  function startThread(existingToken, intent, gender, lat, lng) {
    var body = { guest_token: existingToken || undefined, intent: intent || undefined, operator_gender_preference: gender || undefined, lat: lat, lng: lng };
    fetch(startUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); }).then(function (data) {
      localStorage.setItem(storageKey, data.guest_token);
      state.threadId = data.thread_id;
      state.guestToken = data.guest_token;
      applyOnlineStatus(data.is_online, data.offline_message);
      renderMessages(data.messages, true);
      panelEl.classList.remove('hidden');
      startPolling();
      setupEyeTracking(gender);
    }).catch(function () {
      statusEl.textContent = 'Bağlantı kurulamadı, lütfen tekrar deneyin.';
      panelEl.classList.remove('hidden');
    });
  }

  function openPanel(savedToken) {
    startThread(savedToken, null, null, null, null);
  }

  function applyOnlineStatus(isOnline, offlineMessage) {
    if (isOnline) {
      statusEl.textContent = 'Çevrimiçi';
      offlineBanner.classList.add('hidden');
    } else {
      statusEl.textContent = 'Çevrimdışı';
      offlineBanner.textContent = offlineMessage || 'Şu an çevrimdışıyız. Mesajınızı bırakın, size en kısa sürede döneriz.';
      offlineBanner.classList.remove('hidden');
    }
  }

  function renderMessages(list, replace) {
    if (replace) { messagesEl.innerHTML = ''; state.lastMessageId = 0; }
    (list || []).forEach(function (m) {
      if (m.id <= state.lastMessageId) return;
      state.lastMessageId = Math.max(state.lastMessageId, m.id);
      var bubble = document.createElement('div');
      bubble.className = 'js-chat-bubble ' + (m.sender_type === 'guest' ? 'guest' : 'admin');
      var html = '';
      if (m.body) html += m.body.replace(/</g, '&lt;');
      if (m.attachment_url && m.attachment_type === 'image') html += '<br><img src="' + m.attachment_url + '" alt="ek görsel">';
      else if (m.attachment_url && m.attachment_type === 'video') html += '<br><video src="' + m.attachment_url + '" controls></video>';
      else if (m.attachment_url) html += '<br><a href="' + m.attachment_url + '" target="_blank" rel="noopener" class="underline">Ek dosyayı aç</a>';
      bubble.innerHTML = html || '(boş mesaj)';
      messagesEl.appendChild(bubble);
    });
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function startPolling() {
    if (state.pollTimer) clearInterval(state.pollTimer);
    state.pollTimer = setInterval(function () {
      if (!state.threadId) return;
      var url = pollUrlTemplate.replace('THREAD_ID', state.threadId) + '?after_id=' + state.lastMessageId + '&guest_token=' + encodeURIComponent(state.guestToken);
      fetch(url).then(function (r) { return r.json(); }).then(function (data) {
        renderMessages(data.messages, false);
        applyOnlineStatus(data.is_online, offlineBanner.textContent);
      }).catch(function () {});
    }, 4500);
  }

  function sendMessage() {
    var text = inputEl.value.trim();
    if (!text && !pendingFile) return;
    if (!state.threadId) return;

    var formData = new FormData();
    formData.append('guest_token', state.guestToken);
    if (text) formData.append('body', text);
    if (pendingFile) formData.append('attachment', pendingFile);

    inputEl.value = '';
    var fileToSend = pendingFile;
    pendingFile = null;
    attachPreview.classList.add('hidden');
    fileInput.value = '';

    fetch(sendUrlTemplate.replace('THREAD_ID', state.threadId), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: formData,
    }).then(function (r) { return r.json(); }).then(function (data) {
      renderMessages([data.message], false);
      if (data.suggested_section) renderSuggestionCard(data.suggested_section);
    }).catch(function () {
      alert('Mesaj gönderilemedi, lütfen tekrar deneyin.');
    });
  }

  var suggestedSections = {};
  function renderSuggestionCard(section) {
    if (suggestedSections[section.slug]) return; // ayni bolum icin tekrar tekrar gosterme
    suggestedSections[section.slug] = true;

    var card = document.createElement('div');
    card.className = 'js-chat-bubble admin';
    card.style.background = '#f8fafc';
    card.innerHTML = 'Bu konuda kurumlara bakmak ister misiniz? <a href="' +
      facilitiesUrlTemplate.replace('SECTION_SLUG', section.slug) +
      '" target="_blank" rel="noopener" class="font-bold underline">' + section.label + ' listesine git →</a>';
    messagesEl.appendChild(card);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  sendBtn.addEventListener('click', sendMessage);
  inputEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  attachBtn.addEventListener('click', function () { fileInput.click(); });
  fileInput.addEventListener('change', function () {
    var file = fileInput.files[0];
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) {
      alert('Dosya çok büyük (en fazla 20 MB).');
      fileInput.value = '';
      return;
    }
    pendingFile = file;
    attachPreview.textContent = '📎 ' + file.name;
    attachPreview.classList.remove('hidden');
  });

  // Sesli yaziya dokme - tarayici yerlisi, sunucuya hicbir sey gitmez.
  // Sadece guvenli baglamda (https/localhost) calisir.
  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (SpeechRecognition && window.isSecureContext) {
    micBtn.classList.remove('hidden');
    var recognition = new SpeechRecognition();
    recognition.lang = 'tr-TR';
    recognition.interimResults = false;
    var listening = false;
    micBtn.addEventListener('click', function () {
      if (listening) { recognition.stop(); return; }
      try {
        recognition.start();
        listening = true;
        micBtn.classList.add('text-red-500');
      } catch (err) {
        // Onceki oturum tam kapanmadan tekrar start() cagrilirsa olusan
        // InvalidStateError'i sessizce yutmak yerine kullaniciya bildiriyoruz.
        alert('Mikrofon başlatılamadı, lütfen tekrar deneyin.');
      }
    });
    recognition.addEventListener('result', function (e) {
      var transcript = e.results[0][0].transcript;
      inputEl.value = (inputEl.value ? inputEl.value + ' ' : '') + transcript;
    });
    recognition.addEventListener('end', function () { listening = false; micBtn.classList.remove('text-red-500'); });
    recognition.addEventListener('error', function (e) {
      listening = false;
      micBtn.classList.remove('text-red-500');
      if (e.error === 'not-allowed') alert('Mikrofon izni reddedildi. Tarayıcı ayarlarından izin verin.');
    });
  }

  // Goz ikonu: masaustunde imleci takip eder, dokunmatikte durgun/kirpan hale duser.
  var eyeTrackingBound = false;
  function setupEyeTracking(gender) {
    pupilEl.style.background = gender === 'kadin' ? '#ec4899' : (gender === 'erkek' ? '#3b82f6' : '{{ $brand["primary_color"] }}');

    var canHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;
    if (!canHover) {
      eyeEl.classList.add('js-eye-idle');
      return;
    }
    if (eyeTrackingBound) return;
    eyeTrackingBound = true;

    document.addEventListener('mousemove', function (e) {
      var rect = eyeEl.getBoundingClientRect();
      var cx = rect.left + rect.width / 2;
      var cy = rect.top + rect.height / 2;
      var angle = Math.atan2(e.clientY - cy, e.clientX - cx);
      var radius = 6;
      var x = Math.cos(angle) * radius;
      var y = Math.sin(angle) * radius;
      pupilEl.style.transform = 'translate(' + x + 'px,' + y + 'px)';
    });
  }
})();
</script>
