@php
  $notifPermissionId = 'js-notif-permission-' . uniqid();
@endphp
<div id="{{ $notifPermissionId }}" data-vapid-key="{{ config('services.vapid.public_key') }}" data-subscribe-url="{{ brand_route('push.subscribe') }}">
  <button type="button" class="js-notif-enable-btn {{ $buttonClass ?? 'btn-primary text-sm px-4 py-2 rounded-lg font-semibold' }}" hidden>Bildirimleri Aç</button>
  <p class="js-notif-granted-text text-sm text-green-700 font-semibold" hidden>Bildirimler açık ✓</p>
  <p class="js-notif-denied-text text-sm text-gray-500" hidden>Bildirimler engellenmiş. Açmak için tarayıcı site ayarlarından izin vermeniz gerekiyor.</p>
  <p class="js-notif-unsupported-text text-sm text-gray-400" hidden>Bu tarayıcı bildirimleri desteklemiyor.</p>
</div>

<script>
(function () {
  var root = document.getElementById(@json($notifPermissionId));
  if (!root) return;

  var enableBtn = root.querySelector('.js-notif-enable-btn');
  var grantedText = root.querySelector('.js-notif-granted-text');
  var deniedText = root.querySelector('.js-notif-denied-text');
  var unsupportedText = root.querySelector('.js-notif-unsupported-text');
  var vapidKey = root.dataset.vapidKey;
  var subscribeUrl = root.dataset.subscribeUrl;

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  }

  function showState(state) {
    enableBtn.hidden = state !== 'default';
    grantedText.hidden = state !== 'granted';
    deniedText.hidden = state !== 'denied';
    unsupportedText.hidden = state !== 'unsupported';
  }

  function subscribe() {
    return navigator.serviceWorker.register('/sw.js').then(function () {
      // register() sonrasi SW hemen "activated" olmayabilir; pushManager
      // cagrilari icin "ready" (activated) durumunu beklemek gerekiyor.
      return navigator.serviceWorker.ready;
    }).then(function (registration) {
      return registration.pushManager.getSubscription().then(function (existing) {
        if (existing) return existing;
        return registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });
      });
    }).then(function (subscription) {
      var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      return fetch(subscribeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(subscription.toJSON()),
      });
    });
  }

  // Sayfa ilk yuklendiginde: izin zaten "granted" ise (ornegin baska bir
  // sayfada daha once verildiyse) ama bu cihazda henuz gercek bir push
  // abonelik kaydi yoksa, kullaniciyi tekrar butona bastirmadan sessizce
  // abone olunur - izin zaten var, tekrar sormaya gerek yok.
  function render() {
    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window) || !vapidKey) {
      showState('unsupported');
      return;
    }

    if (Notification.permission === 'granted') {
      showState('granted');
      subscribe().catch(function (e) { console.error('Bildirim aboneligi basarisiz:', e); });
    } else if (Notification.permission === 'denied') {
      showState('denied');
    } else {
      showState('default');
    }
  }

  if (enableBtn) {
    enableBtn.addEventListener('click', function () {
      Notification.requestPermission().then(function (permission) {
        if (permission === 'granted') {
          showState('granted');
          subscribe().catch(function (e) { console.error('Bildirim aboneligi basarisiz:', e); });
        } else {
          showState(permission === 'denied' ? 'denied' : 'default');
        }
      });
    });
  }

  render();
})();
</script>
