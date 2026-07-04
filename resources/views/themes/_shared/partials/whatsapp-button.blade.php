@php
  $whatsappDefaults = config('platform.default_whatsapp');
  $whatsappNumber = \App\Models\Setting::get('whatsapp_number', $whatsappDefaults['number']);
  $whatsappMessage = str_replace('{marka}', $brand['name'], \App\Models\Setting::get('whatsapp_message', $whatsappDefaults['message']));
@endphp
<a
  href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode($whatsappMessage) }}"
  target="_blank"
  rel="noopener"
  id="js-whatsapp-button"
  aria-label="WhatsApp'tan canlı destek"
  class="fixed bottom-5 right-5 z-40 flex items-center justify-center w-14 h-14 rounded-full shadow-lg animate-[wa-bounce-in_.5s_ease-out] hover:scale-105 transition-transform"
  style="background:#25D366;"
>
  <svg viewBox="0 0 32 32" class="w-8 h-8 fill-white" aria-hidden="true">
    <path d="M16.004 3C9.376 3 4 8.373 4 15c0 2.288.638 4.428 1.744 6.252L4 29l7.94-1.71A11.94 11.94 0 0 0 16.004 27C22.63 27 28 21.627 28 15S22.63 3 16.004 3Zm6.965 17.09c-.29.82-1.44 1.5-2.36 1.7-.64.13-1.47.24-4.28-.92-3.59-1.49-5.9-5.13-6.08-5.37-.18-.24-1.45-1.93-1.45-3.68 0-1.75.92-2.61 1.24-2.97.32-.36.7-.45.93-.45.23 0 .47 0 .67.01.21.01.5-.08.78.6.29.7.98 2.42 1.06 2.6.08.18.14.39.03.63-.11.24-.17.39-.34.6-.17.21-.36.47-.51.63-.17.18-.35.37-.15.72.2.36.9 1.48 1.93 2.4 1.33 1.18 2.44 1.55 2.8 1.72.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41.24-.36.48-.3.79-.18.32.12 2.01.95 2.36 1.12.35.18.58.27.66.42.09.15.09.87-.2 1.68Z"/>
  </svg>
</a>
<style>
  @keyframes wa-bounce-in {
    0% { transform: translateY(24px) scale(.6); opacity: 0; }
    60% { transform: translateY(-6px) scale(1.05); opacity: 1; }
    100% { transform: translateY(0) scale(1); }
  }
</style>
<script>
(function () {
  var button = document.getElementById('js-whatsapp-button');
  if (!button) return;
  var trackUrl = @json(brand_route('whatsapp.track'));
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

  function sendTrack(lat, lng) {
    fetch(trackUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ page_url: window.location.href, lat: lat, lng: lng }),
      keepalive: true
    }).catch(function () {});
  }

  button.addEventListener('click', function () {
    // WhatsApp sohbetini geciktirmeden aciyoruz (href zaten yeni sekmede acilir);
    // konum izni istemesi/beklemesi kullaniciyi bekletmesin diye kisa timeout'lu.
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        sendTrack(position.coords.latitude, position.coords.longitude);
      }, function () {
        sendTrack(null, null);
      }, { timeout: 3000, maximumAge: 60000 });
    } else {
      sendTrack(null, null);
    }
  });
})();
</script>
