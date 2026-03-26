// SW simplificado para evitar cache quebrado. Ele apaga todos os caches e se auto-remove.
const CACHE_VERSION = 'v0-disable';

self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k))))
      .then(() => self.registration.unregister())
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Passa direto para a rede sem cache para garantir CSS/JS sempre frescos
  event.respondWith(fetch(event.request).catch(() => fetch(event.request)));
});
