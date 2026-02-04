// Service Worker para PWA - Tem de Tudo
const CACHE_NAME = 'tem-de-tudo-v1.0.0';
const urlsToCache = [
  '/',
  '/index.html',
  '/entrar.html',
  '/cadastro.html',
  '/app-dashboard.html',
  '/app-empresas.html',
  '/app-promocoes.html',
  '/app-qrcode.html',
  '/app-perfil.html',
  '/css/theme-escuro.css',
  '/js/notification-system-simple.js',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Cache aberto');
        return cache.addAll(urlsToCache);
      })
      .catch(err => console.log('[SW] Erro ao cachear:', err))
  );
  self.skipWaiting();
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Ativando Service Worker...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Estratégia: Network First com fallback para Cache
self.addEventListener('fetch', (event) => {
  // Filtrar requisições não cacheáveis
  const url = new URL(event.request.url);
  const method = event.request.method;
  
  // NÃO cachear:
  // - Extensões do Chrome
  // - Métodos POST/PUT/DELETE/HEAD
  // - URLs externas (não são do mesmo domínio)
  const shouldCache = 
    !url.protocol.includes('chrome-extension') &&
    method === 'GET' &&
    (url.origin === location.origin || url.hostname === 'localhost');
  
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Se conseguiu da rede E pode cachear, cacheia e retorna
        if (shouldCache && response.ok) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone).catch(() => {});
          });
        }
        return response;
      })
      .catch(() => {
        // Se falhou, tenta do cache (somente para requisições cacheáveis)
        if (!shouldCache) {
          return new Response('Network error', { status: 503 });
        }
        return caches.match(event.request).then((response) => {
          if (response) {
            return response;
          }
          // Página offline personalizada
          if (event.request.mode === 'navigate') {
            return caches.match('/offline.html');
          }
        });
      })
  );
});

// Sincronização em background
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync:', event.tag);
  if (event.tag === 'sync-checkins') {
    event.waitUntil(syncCheckIns());
  }
});

// Notificações Push
self.addEventListener('push', (event) => {
  console.log('[SW] Push recebido:', event.data?.text());
  
  const options = {
    body: event.data?.text() || 'Nova notificação do Tem de Tudo',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-96x96.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'open',
        title: 'Abrir',
        icon: '/icons/icon-96x96.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: '/icons/icon-96x96.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Tem de Tudo', options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Clique na notificação:', event.action);
  event.notification.close();

  if (event.action === 'open') {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Função auxiliar para sincronizar check-ins offline
async function syncCheckIns() {
  // Implementar lógica de sincronização de check-ins feitos offline
  console.log('[SW] Sincronizando check-ins...');
}
