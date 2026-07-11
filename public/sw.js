// Bakim Platform service worker: PWA kurulabilirligi (minimal fetch pass-through)
// + Web Push bildirim gosterimi/tiklanmasi icin.

self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

// Kurulabilirlik kriterleri icin kayitli bir fetch handler olmasi yeterli;
// hicbir ozel cache/offline stratejisi uygulanmiyor, istekler oldugu gibi
// gecirilir (pass-through).
self.addEventListener('fetch', function () {});

self.addEventListener('push', function (event) {
  var data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'Bildirim', body: event.data ? event.data.text() : '' };
  }

  var title = data.title || 'Bakim Platform';
  var options = {
    body: data.body || '',
    icon: data.icon || '/images/logo-bakimevleri-192.png',
    badge: data.icon || '/images/logo-bakimevleri-192.png',
    data: { url: data.url || '/' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
