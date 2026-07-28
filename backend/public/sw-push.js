/* global self, clients */

self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const inner = data.data || {};
  const title = data.title || 'Tem de Tudo';
  const image = data.image || inner.image || null;
  const options = {
    body: data.body || 'Você recebeu uma nova notificação.',
    icon: data.icon || '/img/icon-192.png',
    badge: data.badge || '/img/icon-96.png',
    data: {
      url: data.url || inner.url || '/index.html',
      empresa_id: data.empresa_id || inner.empresa_id || null,
      tipo: data.tipo || inner.tipo || 'push',
    },
  };

  // Push com imagem: exibe a imagem enviada pela empresa quando houver.
  if (image) {
    options.image = image;
  }

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

/* Handler de fetch: o Chrome so oferece a instalacao em 1 toque
   (beforeinstallprompt) quando o service worker responde a fetch. Sem isto o
   usuario de Android caia no passo a passo manual sem necessidade.
   Estrategia: rede primeiro, sem cache proprio — nao muda o comportamento do
   app, so cumpre o criterio e da uma resposta offline decente na navegacao. */
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith('/api/')) return;

  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/index.html').then((r) => r || Response.error()))
    );
  }
});
