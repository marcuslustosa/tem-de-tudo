/* global self, clients */

self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Tem de Tudo';
  const options = {
    body: data.body || 'Você recebeu uma nova notificação.',
    icon: data.icon || '/img/icon-192.png',
    badge: data.badge || '/img/icon-96.png',
    data: {
      url: data.url || data.data.url || '/index.html',
      empresa_id: data.empresa_id || data.data.empresa_id || null,
      tipo: data.tipo || data.data.tipo || 'push',
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data.url || '/index.html';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.focus();
          if ('navigate' in client) {
            return client.navigate(targetUrl);
          }
          return undefined;
        }
      }

      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }

      return undefined;
    })
  );
});
