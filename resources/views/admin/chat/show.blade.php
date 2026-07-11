@extends('admin.layout')
@section('title', 'Sohbet #'.$thread->id)

@section('content')
<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-bold">Sohbet #{{ $thread->id }} · {{ $thread->brand }}</h1>
    <div class="text-sm text-gray-500 mt-1">
      {{ ['sohbet' => 'Sohbet', 'dertlesme' => 'Dertleşme', 'fikir' => 'Fikir', 'temsilci' => 'Temsilci'][$thread->intent] ?? $thread->intent }}
      · Tercih: {{ ['erkek' => 'Bay', 'kadin' => 'Bayan', 'farketmez' => 'Farketmez'][$thread->operator_gender_preference] ?? '—' }}
      · Şehir: {{ $thread->city_name ?? '—' }}
      · İlgilenen: {{ $thread->assignedAdmin->name ?? '—' }}
    </div>
  </div>
  <form method="POST" action="{{ route('admin.chat.close', $thread) }}" onsubmit="return confirm('Bu sohbeti kapatmak istediğinize emin misiniz?');">
    @csrf
    <button class="text-sm text-red-600 border border-red-200 rounded-lg px-3 py-2 hover:bg-red-50">Sohbeti Kapat</button>
  </form>
</div>

@php $intentLabels = ['sohbet' => '💬 Sohbet', 'dertlesme' => '🤍 Dertleşme', 'fikir' => '💡 Fikir', 'temsilci' => '🎧 Temsilci']; @endphp
@if($siblingThreads->isNotEmpty())
  <div class="mb-4">
    <div class="text-xs font-bold text-gray-500 mb-2">Bu ziyaretçinin diğer sohbetleri (aynı kişi, ayrı bölümler)</div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('admin.chat.show', $thread) }}" class="px-3 py-1.5 rounded-full text-xs font-bold bg-blue-600 text-white">
        {{ $intentLabels[$thread->intent] ?? $thread->intent }} (şu an)
      </a>
      @foreach($siblingThreads as $sibling)
        <a href="{{ route('admin.chat.show', $sibling) }}" class="px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200">
          {{ $intentLabels[$sibling->intent] ?? $sibling->intent }}
          @if($sibling->unread_by_admin)<span class="ml-1 bg-orange-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">yeni</span>@endif
        </a>
      @endforeach
    </div>
  </div>
@endif

<div class="bg-white rounded-xl shadow-sm flex flex-col h-[70vh]">
  <div id="js-admin-chat-messages" class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50 rounded-t-xl">
    @foreach($thread->messages as $m)
      <div class="js-admin-bubble admin-bubble-{{ $m->sender_type }}" data-id="{{ $m->id }}">
        @if($m->body){{ $m->body }}@endif
        @if($m->attachment_path)
          @php $url = \Illuminate\Support\Facades\Storage::disk('public')->url($m->attachment_path); @endphp
          @if($m->attachment_type === 'image')<br><img src="{{ $url }}" class="max-w-xs rounded-lg mt-1">
          @elseif($m->attachment_type === 'video')<br><video src="{{ $url }}" controls class="max-w-xs rounded-lg mt-1"></video>
          @else<br><a href="{{ $url }}" target="_blank" class="underline">Ek dosyayı aç</a>@endif
        @endif
        <div class="text-[10px] opacity-60 mt-1">{{ $m->sender_type === 'admin' ? ($m->senderAdmin->name ?? 'Admin') : 'Misafir' }} · {{ $m->created_at->format('H:i') }}</div>
        @if($m->sender_type === 'guest' && ($detected = detect_chat_section($m->body)))
          <div class="text-[10px] mt-1 inline-block bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">🏷 {{ $detected['label'] }} ilgisi olabilir</div>
        @endif
      </div>
    @endforeach
  </div>

  <form id="js-admin-reply-form" method="POST" action="{{ route('admin.chat.reply', $thread) }}" enctype="multipart/form-data" class="border-t p-3">
    @csrf
    <div id="js-admin-attach-preview" class="hidden px-1 pb-2 text-xs text-gray-500"></div>
    <div id="js-admin-mic-status" class="hidden px-1 pb-2 text-xs"></div>
    <div class="flex items-end gap-2">
      <input type="file" name="attachment" id="js-admin-file" class="hidden" accept="image/jpeg,image/png,image/webp,video/mp4,.pdf,.doc,.docx">
      <button type="button" id="js-admin-attach-btn" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100" title="Dosya ekle">📎</button>
      <button type="button" id="js-admin-mic-btn" class="hidden shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100" title="Sesli yaz">🎤</button>
      <textarea name="body" id="js-admin-input" rows="2" placeholder="Yanıtınızı yazın…" class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-gray-400"></textarea>
      <button type="submit" class="shrink-0 rounded-xl px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700">Gönder</button>
    </div>
  </form>
</div>

<style>
  .js-admin-bubble { max-width: 70%; padding: .5rem .75rem; border-radius: .9rem; font-size: .85rem; line-height: 1.4; word-wrap: break-word; }
  .admin-bubble-guest { margin-right: auto; background: #fff; border: 1px solid #e5e7eb; border-bottom-left-radius: .25rem; }
  .admin-bubble-admin { margin-left: auto; background: #2563eb; color: #fff; border-bottom-right-radius: .25rem; }
</style>

<script>
(function () {
  var messagesEl = document.getElementById('js-admin-chat-messages');
  var pollUrl = @json(route('admin.chat.poll', $thread));
  var lastId = @json($thread->messages->max('id') ?? 0);

  messagesEl.scrollTop = messagesEl.scrollHeight;

  function appendMessage(m) {
    if (m.id <= lastId) return;
    lastId = Math.max(lastId, m.id);
    var div = document.createElement('div');
    div.className = 'js-admin-bubble admin-bubble-' + m.sender_type;
    var html = m.body ? m.body.replace(/</g, '&lt;') : '';
    if (m.attachment_url && m.attachment_type === 'image') html += '<br><img src="' + m.attachment_url + '" class="max-w-xs rounded-lg mt-1">';
    else if (m.attachment_url && m.attachment_type === 'video') html += '<br><video src="' + m.attachment_url + '" controls class="max-w-xs rounded-lg mt-1"></video>';
    else if (m.attachment_url) html += '<br><a href="' + m.attachment_url + '" target="_blank" class="underline">Ek dosyayı aç</a>';
    div.innerHTML = html;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  setInterval(function () {
    fetch(pollUrl + '?after_id=' + lastId).then(function (r) { return r.json(); }).then(function (data) {
      (data.messages || []).forEach(appendMessage);
    }).catch(function () {});
  }, 4000);

  var attachBtn = document.getElementById('js-admin-attach-btn');
  var fileInput = document.getElementById('js-admin-file');
  var preview = document.getElementById('js-admin-attach-preview');
  attachBtn.addEventListener('click', function () { fileInput.click(); });
  fileInput.addEventListener('change', function () {
    var file = fileInput.files[0];
    if (!file) return;
    preview.textContent = '📎 ' + file.name;
    preview.classList.remove('hidden');
  });

  var micBtn = document.getElementById('js-admin-mic-btn');
  var inputEl = document.getElementById('js-admin-input');
  var micStatus = document.getElementById('js-admin-mic-status');
  function showMicStatus(text, isError) {
    if (!text) { micStatus.classList.add('hidden'); return; }
    micStatus.textContent = text;
    micStatus.className = isError ? 'px-1 pb-2 text-xs text-red-600' : 'px-1 pb-2 text-xs text-blue-600';
  }

  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (! window.isSecureContext) {
    // Web Speech API sadece HTTPS (veya localhost) uzerinde calisir - http
    // uzerinden acilan bir admin paneli mikrofonu hic baslatamaz.
    // Buton yine de gizli kalir (asagidaki SpeechRecognition kontrolu).
  }
  if (SpeechRecognition && window.isSecureContext) {
    micBtn.classList.remove('hidden');
    var recognition = new SpeechRecognition();
    recognition.lang = 'tr-TR';
    recognition.interimResults = false;
    var listening = false;

    micBtn.addEventListener('click', function () {
      if (listening) {
        recognition.stop();
        return;
      }
      try {
        recognition.start();
        listening = true;
        micBtn.classList.add('text-red-600');
        showMicStatus('Dinleniyor…', false);
      } catch (err) {
        // Onceki oturum tam kapanmadan tekrar start() cagrilirsa
        // InvalidStateError firlatir - kullaniciya sessizce hicbir sey
        // olmuyormus gibi gorunmemesi icin hata gosteriliyor.
        showMicStatus('Mikrofon başlatılamadı, tekrar deneyin.', true);
      }
    });
    recognition.addEventListener('result', function (e) {
      inputEl.value = (inputEl.value ? inputEl.value + ' ' : '') + e.results[0][0].transcript;
    });
    recognition.addEventListener('end', function () {
      listening = false;
      micBtn.classList.remove('text-red-600');
      showMicStatus(null);
    });
    recognition.addEventListener('error', function (e) {
      listening = false;
      micBtn.classList.remove('text-red-600');
      var messages = {
        'not-allowed': 'Mikrofon izni reddedildi. Tarayıcı ayarlarından izin verin.',
        'no-speech': 'Ses algılanamadı, tekrar deneyin.',
        'audio-capture': 'Mikrofon bulunamadı.',
        'network': 'Ses tanıma servisine ulaşılamadı (internet bağlantısını kontrol edin).',
      };
      showMicStatus(messages[e.error] || ('Mikrofon hatası: ' + e.error), true);
    });
  }

  // Formu normal POST-redirect-back ile gonderiyoruz (admin panelinin genel
  // deseniyle tutarli) - JS sadece dosya onizleme + sesli yazma icin.
})();
</script>
@endsection
