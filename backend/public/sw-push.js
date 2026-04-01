/* global self, clients */
// Service Worker para Push Notifications (Stitch)

self.addEventListener('push', function (event) {
  if (!event.data) return;
  const payload = event.data.json();
  const title = payload.title || 'Notificação';
  const options = {
    body: payload.body || '',
    data: payload.data || {},
    icon: '/img/icon-192.png',
    badge: '/img/icon-96.png',
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const target = event.notification.data?.url || '/';
  event.waitUntil(clients.openWindow(target));
});
