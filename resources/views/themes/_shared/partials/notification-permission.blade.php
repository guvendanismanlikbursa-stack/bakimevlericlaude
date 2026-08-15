@php
  $notifPermissionId = 'js-notif-permission-' . uniqid();
@endphp
<div id="{{ $notifPermissionId }}" data-vapid-key="{{ config('services.vapid.public_key') }}" data-subscribe-url="{{ brand_route('push.subscribe') }}">
  <button type="button" class="js-notif-enable-btn {{ $buttonClass ?? 'btn-primary text-sm px-4 py-2 rounded-lg font-semibold' }}" hidden>Bildirimleri Aç</button>
  <p class="js-notif-granted-text text-sm text-green-700 font-semibold" hidden>Bildirimler açık ✓</p>
  <p class="js-notif-denied-text text-sm text-gray-500" hidden>Bildirimler engellenmiş. Açmak için tarayıcı site ayarlarından izin vermeniz gerekiyor.</p>
  <p class="js-notif-unsupported-text text-sm text-gray-400" hidden>Bu tarayıcı bildirimleri desteklemiyor.</p>
  <p class="js-notif-error-text text-sm text-red-600 font-semibold" hidden></p>
</div>

<script>
(function () {
  var root = document.getElementById(@json($notifPermissionId));
  if (!root) return;

  var enableBtn = root.querySelector('.js-notif-enable-btn');
  var grantedText = root.querySelector('.js-notif-granted-text');
  var deniedText = root.querySelector('.js-notif-denied-text');
  var unsupportedText = root.querySelector('.js-notif-unsupported-text');
  var errorText = root.querySelector('.js-notif-error-text');
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
    if (errorText) errorText.hidden = true;
  }

  // izin (Notification.permission) durumundan BAGIMSIZ olarak calisir -
  // izin "granted" olsa bile GERCEK sunucu abonelik kaydi basarisiz
  // olabilir, kullanici ikisini birden gormeli (ör. "izin acik" YAZIYOR
  // ama "kayit basarisiz" UYARISI da gorunuyor).
  //
  // 30 Temmuz 2026: subscribe()'daki TUM adimlar (service worker kaydi,
  // pushManager.subscribe, sunucuya fetch) TEK bir .catch()'e dusuyordu ve
  // hepsi ayni "sunucuya kaydedilemedi" mesajini gosteriyordu - bu, gercek
  // hatanin NEREDE oldugunu (ör. cihaz/tarayici push'u hic desteklemiyor mu,
  // yoksa gercekten sunucu mu reddetti) mobilde DevTools'suz ayirt etmeyi
  // imkansiz kiliyordu. Artik hangi adimin basarisiz oldugu mesaja yaziliyor.
  function showSubscribeError(message) {
    if (errorText) {
      errorText.textContent = message || 'Bildirim aboneliği başarısız oldu, lütfen sayfayı yenileyip tekrar deneyin.';
      errorText.hidden = false;
    }
  }

  // 30 Temmuz 2026: admin gercek cihazinda push bildirimi hic almadigini
  // bildirdi. Kok neden: bu fonksiyon fetch(subscribeUrl) yanitini HIC
  // KONTROL ETMIYORDU - sunucu 401/419/500 donse bile (ornegin oturum
  // suresi dolmus, CSRF token eskimis) fetch() bunu THROW ETMEZ (sadece
  // gercek network hatalarinda reddeder), .then() zinciri "basarili" gibi
  // devam ediyordu. Kullanici arayuzde "Bildirimler acik" yazisini goruyordu
  // (bu SADECE Notification.permission durumuna bakiyor, GERCEK sunucu
  // kaydina degil) ama push_subscriptions tablosuna HICBIR SEY yazilmamis
  // olabiliyordu - sessiz, gorunmeyen bir basarisizlik. Artik response.ok
  // ve {ok:true} govdesi kontrol ediliyor, degilse hata firlatiliyor ki
  // asagidaki .catch() bloklari kullaniciya GORUNUR bir hata gosterebilsin.
  function subscribe() {
    return navigator.serviceWorker.register('/sw.js').catch(function (e) {
      throw new Error('Servis çalışanı (service worker) kaydı başarısız: ' + e.message);
    }).then(function () {
      // register() sonrasi SW hemen "activated" olmayabilir; pushManager
      // cagrilari icin "ready" (activated) durumunu beklemek gerekiyor.
      return navigator.serviceWorker.ready;
    }).then(function (registration) {
      var currentKey = urlBase64ToUint8Array(vapidKey);
      return registration.pushManager.getSubscription().then(function (existing) {
        // 15 Agustos 2026: kullanicinin bildirdigi "APK hic bildirim
        // vermiyor" sikayetinin kok nedeni - sunucudaki VAPID anahtari bir
        // noktada degismis, ama tarayicida ESKI anahtarla olusturulmus bir
        // abonelik hala mevcut oldugu icin getSubscription() onu donuyor ve
        // TEKRAR TEKRAR sunucuya (artik gecersiz olan) o eski anahtarla
        // gonderiliyordu - sonsuza kadar sessizce basarisiz oluyordu (FCM
        // "VAPID credentials do not correspond" ile reddediyordu, bkz.
        // WebPushService). Var olan aboneligin GERCEKTEN su anki anahtarla
        // eslesip eslesmedigi kontrol edilir; eslesmiyorsa once eskisinden
        // abonelik iptal edilip GUNCEL anahtarla yeniden abone olunur.
        if (existing) {
          var existingKey = new Uint8Array(existing.options.applicationServerKey);
          var matches = existingKey.length === currentKey.length && existingKey.every(function (b, i) { return b === currentKey[i]; });
          if (matches) return existing;
          return existing.unsubscribe().then(function () {
            return registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: currentKey,
            });
          }).catch(function (e) {
            throw new Error('Eski abonelik iptal edilip yenilenemedi: ' + e.message);
          });
        }
        return registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: currentKey,
        }).catch(function (e) {
          throw new Error('Cihaz/tarayıcı push aboneliği oluşturamadı: ' + e.message);
        });
      });
    }).then(function (subscription) {
      var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      return fetch(subscribeUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(subscription.toJSON()),
      }).catch(function (e) {
        throw new Error('Sunucuya bağlanılamadı (ağ hatası): ' + e.message);
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Sunucu abonelik kaydını reddetti: HTTP ' + response.status);
        }
        return response.json();
      }).then(function (body) {
        if (!body || body.ok !== true) {
          throw new Error('Sunucu abonelik kaydını onaylamadı.');
        }
        return body;
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
      subscribe().catch(function (e) { console.error('Bildirim aboneligi basarisiz:', e); showSubscribeError(e.message); });
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
          subscribe().catch(function (e) { console.error('Bildirim aboneligi basarisiz:', e); showSubscribeError(e.message); });
        } else {
          showState(permission === 'denied' ? 'denied' : 'default');
        }
      });
    });
  }

  render();
})();
</script>
