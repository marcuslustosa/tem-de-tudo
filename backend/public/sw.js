// Service Worker - Tem de Tudo App
// Versão otimizada para PWA com cache estratégico

const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `tem-de-tudo-${CACHE_VERSION}`;

// Assets essenciais para cache (offline first)
const CORE_ASSETS = [
    '/',
    '/app-inicio.html',
    '/app-perfil.html',
    '/app-meu-qrcode.html',
    '/app-scanner.html',
    '/css/mobile-native.css',
    '/manifest.json'
];

// Instalação - cacheia assets essenciais
self.addEventListener('install', (event) => {
    console.log('[SW] Instalando Service Worker...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Cache aberto, adicionando assets...');
                return cache.addAll(CORE_ASSETS);
            })
            .then(() => {
                console.log('[SW] Assets cacheados com sucesso');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Erro ao cachear assets:', error);
            })
    );
});

// Ativação - limpa caches antigos
self.addEventListener('activate', (event) => {
    console.log('[SW] Ativando Service Worker...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[SW] Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Service Worker ativado');
                return self.clients.claim();
            })
    );
});

// Fetch - intercepta requisições
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignora outros domínios (exceto CDNs)
    if (url.origin !== location.origin && 
        !url.origin.includes('cdnjs.cloudflare.com') &&
        !url.origin.includes('fonts.googleapis.com') &&
        !url.origin.includes('fonts.gstatic.com')) {
        return;
    }
    
    // API: Network First
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
        return;
    }
    
    // HTML: Network First
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirst(request));
        return;
    }
    
    // Assets: Cache First
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'font' ||
        request.destination === 'image'
    ) {
        event.respondWith(cacheFirst(request));
        return;
    }
    
    event.respondWith(staleWhileRevalidate(request));
});

// Cache First
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Network First
async function networkFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        if (request.headers.get('Accept')?.includes('text/html')) {
            return cache.match('/app-inicio.html');
        }
        return new Response('Offline', { status: 503 });
    }
}

// Stale While Revalidate
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    
    const fetchPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);
    
    return cached || fetchPromise;
}

// Push Notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Tem de Tudo';
    const options = {
        body: data.body || 'Nova notificação',
        icon: '/img/icon-192.png',
        badge: '/img/badge-72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/app-notificacoes.html'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const urlToOpen = event.notification.data?.url || '/app-inicio.html';
    
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then((clientList) => {
                for (let client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

console.log('[SW] Service Worker carregado - Versão:', CACHE_VERSION);

