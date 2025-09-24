/**
 * TEM DE TUDO - SERVICE WORKER MOBILE
 * PWA Completo com Cache Inteligente e Notificações Push
 */

const CACHE_NAME = 'tem-de-tudo-mobile-v1.0.0';
const STATIC_CACHE = 'tem-de-tudo-static-v1';
const DYNAMIC_CACHE = 'tem-de-tudo-dynamic-v1';

// Arquivos essenciais para cache offline
const STATIC_FILES = [
    '/',
    '/index.html',
    '/login.html',
    '/register.html',
    '/estabelecimentos.html',
    '/contato.html',
    '/profile-client.html',
    '/profile-company.html',
    '/register-company.html',
    '/css/mobile-theme.css',
    '/js/app-mobile.js',
    '/img/logo.png',
    '/manifest.json',
    '/favicon.ico'
];

// URLs da API para cache dinâmico
const API_URLS = [
    '/api/establishments',
    '/api/user/profile',
    '/api/rewards',
    '/api/points',
    '/api/comments',
    '/api/ratings'
];

// ================================
// INSTALAÇÃO DO SERVICE WORKER
// ================================
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker Mobile instalando...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('📦 Cacheando arquivos estáticos');
                return cache.addAll(STATIC_FILES.map(url => new Request(url, { cache: 'reload' })));
            })
            .then(() => {
                console.log('✅ Service Worker instalado com sucesso');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('❌ Erro na instalação do Service Worker:', error);
            })
    );
});

// ================================
// ATIVAÇÃO DO SERVICE WORKER
// ================================
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker ativando...');
    
    event.waitUntil(
        Promise.all([
            // Limpar caches antigos
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE && cacheName !== CACHE_NAME) {
                            console.log('🗑️ Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            
            // Tomar controle de todas as abas
            self.clients.claim()
        ])
        .then(() => {
            console.log('✅ Service Worker ativado e pronto');
        })
    );
});

// ================================
// INTERCEPTAÇÃO DE REQUESTS
// ================================
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignorar requests não-HTTP ou de outros domínios
    if (!request.url.startsWith('http') || url.origin !== self.location.origin) {
        return;
    }
    
    // Estratégias baseadas no tipo de request
    if (request.method !== 'GET') {
        // Requests POST/PUT/DELETE - tentar network primeiro
        event.respondWith(networkOnlyStrategy(request));
        return;
    }
    
    // Cache First para recursos estáticos
    if (isStaticResource(url.pathname)) {
        event.respondWith(cacheFirstStrategy(request));
        return;
    }
    
    // Network First para APIs
    if (isApiRequest(request.url)) {
        event.respondWith(networkFirstStrategy(request));
        return;
    }
    
    // Stale While Revalidate para páginas HTML
    if (request.destination === 'document' || url.pathname.endsWith('.html') || url.pathname === '/') {
        event.respondWith(staleWhileRevalidateStrategy(request));
        return;
    }
    
    // Estratégia padrão - Cache First
    event.respondWith(cacheFirstStrategy(request));
});

// ================================
// FUNÇÕES DE VERIFICAÇÃO
// ================================
function isStaticResource(pathname) {
    return STATIC_FILES.includes(pathname) || 
           pathname.startsWith('/css/') ||
           pathname.startsWith('/js/') ||
           pathname.startsWith('/img/') ||
           pathname.startsWith('/assets/') ||
           pathname.includes('/favicon') ||
           pathname.endsWith('.png') ||
           pathname.endsWith('.jpg') ||
           pathname.endsWith('.jpeg') ||
           pathname.endsWith('.gif') ||
           pathname.endsWith('.svg') ||
           pathname.endsWith('.ico');
}

function isApiRequest(url) {
    return url.includes('/api/') || API_URLS.some(apiUrl => url.includes(apiUrl));
}

// ================================
// ESTRATÉGIAS DE CACHE
// ================================

// Network Only - Para requests que modificam dados
async function networkOnlyStrategy(request) {
    try {
        const networkResponse = await fetch(request.clone());
        
        // Se for uma resposta de sucesso, invalidar cache relacionado
        if (networkResponse.ok && request.url.includes('/api/')) {
            await invalidateRelatedCache(request.url);
        }
        
        return networkResponse;
    } catch (error) {
        console.error('❌ Erro na requisição de rede:', error);
        
        // Retornar erro estruturado
        return new Response(
            JSON.stringify({
                error: 'Sem conexão',
                message: 'Não foi possível completar a operação',
                offline: true,
                timestamp: Date.now()
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Cache First - Para recursos estáticos que raramente mudam
async function cacheFirstStrategy(request) {
    try {
        // Verificar cache primeiro
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Se não encontrou no cache, buscar na rede
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            await cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('📱 Modo offline - servindo do cache:', request.url);
        
        // Tentar cache novamente como fallback
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fallback específico para imagens
        if (request.destination === 'image' || request.url.includes('.png') || request.url.includes('.jpg')) {
            return createImageFallback();
        }
        
        throw error;
    }
}

// Network First - Para dados dinâmicos que precisam estar atualizados
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            await cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('📱 Modo offline - servindo API do cache:', request.url);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Adicionar header indicando que é cache
            const headers = new Headers(cachedResponse.headers);
            headers.set('X-Served-From-Cache', 'true');
            headers.set('X-Cache-Timestamp', Date.now().toString());
            
            return new Response(cachedResponse.body, {
                status: cachedResponse.status,
                statusText: cachedResponse.statusText,
                headers: headers
            });
        }
        
        // Fallback para APIs
        return createApiFallback(request);
    }
}

// Stale While Revalidate - Para páginas HTML (busca do cache mas atualiza em background)
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    
    // Buscar do cache primeiro
    const cachedResponsePromise = caches.match(request);
    
    // Atualizar cache em background
    const networkResponsePromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch((error) => {
            console.log('📱 Falha na rede para SWR:', request.url);
            return null;
        });
    
    const cachedResponse = await cachedResponsePromise;
    
    if (cachedResponse) {
        // Retornar cache imediatamente, rede atualiza em background
        networkResponsePromise.catch(() => {}); // Evitar unhandled promise rejection
        return cachedResponse;
    }
    
    // Se não há cache, esperar pela rede
    return networkResponsePromise || createPageFallback(request);
}

// ================================
// FUNÇÕES DE FALLBACK
// ================================
function createImageFallback() {
    const svg = `
        <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
            <rect width="200" height="200" fill="#f3f4f6" rx="8"/>
            <circle cx="100" cy="80" r="25" fill="#d1d5db"/>
            <path d="M60 140 L70 120 L90 130 L110 110 L130 125 L140 140 Z" fill="#d1d5db"/>
            <text x="100" y="170" text-anchor="middle" fill="#6b7280" font-family="Arial" font-size="12">
                Imagem offline
            </text>
        </svg>
    `;
    
    return new Response(svg, {
        headers: { 
            'Content-Type': 'image/svg+xml',
            'Cache-Control': 'max-age=86400'
        }
    });
}

function createApiFallback(request) {
    const fallbackData = {
        error: 'Offline',
        message: 'Dados não disponíveis sem conexão',
        offline: true,
        timestamp: Date.now(),
        requestUrl: request.url
    };
    
    // Fallbacks específicos para diferentes APIs
    if (request.url.includes('/establishments')) {
        fallbackData.data = [];
        fallbackData.message = 'Lista de estabelecimentos não disponível offline';
    } else if (request.url.includes('/profile')) {
        fallbackData.message = 'Perfil não disponível offline';
    } else if (request.url.includes('/points')) {
        fallbackData.data = { points: 0, history: [] };
        fallbackData.message = 'Pontos serão sincronizados quando voltar online';
    }
    
    return new Response(JSON.stringify(fallbackData), {
        status: 503,
        headers: { 
            'Content-Type': 'application/json',
            'X-Fallback': 'true'
        }
    });
}

function createPageFallback(request) {
    const offlinePage = `
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Offline - Tem de Tudo</title>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                    margin: 0; padding: 2rem; text-align: center; background: #f8fafc;
                }
                .offline-container { max-width: 400px; margin: 0 auto; }
                .offline-icon { font-size: 4rem; margin-bottom: 1rem; }
                .offline-title { color: #1e293b; font-size: 1.5rem; margin-bottom: 0.5rem; }
                .offline-message { color: #64748b; margin-bottom: 2rem; }
                .retry-btn { 
                    background: #3b82f6; color: white; border: none; 
                    padding: 0.75rem 2rem; border-radius: 0.5rem; cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="offline-container">
                <div class="offline-icon">📱</div>
                <h1 class="offline-title">Você está offline</h1>
                <p class="offline-message">
                    Esta página não está disponível sem conexão com a internet.
                    Verifique sua conexão e tente novamente.
                </p>
                <button class="retry-btn" onclick="window.location.reload()">
                    Tentar Novamente
                </button>
            </div>
        </body>
        </html>
    `;
    
    return new Response(offlinePage, {
        headers: { 'Content-Type': 'text/html' }
    });
}

// ================================
// INVALIDAÇÃO DE CACHE
// ================================
async function invalidateRelatedCache(url) {
    const cache = await caches.open(DYNAMIC_CACHE);
    
    if (url.includes('/establishments')) {
        await cache.delete('/api/establishments');
    } else if (url.includes('/profile')) {
        await cache.delete('/api/user/profile');
    } else if (url.includes('/points')) {
        await cache.delete('/api/points');
    }
}

// ================================
// NOTIFICAÇÕES PUSH
// ================================
self.addEventListener('push', (event) => {
    console.log('🔔 Notificação push recebida');
    
    let data = {
        title: 'Tem de Tudo',
        body: 'Você tem uma nova notificação!',
        icon: '/img/logo.png',
        badge: '/img/logo.png'
    };
    
    if (event.data) {
        try {
            const pushData = event.data.json();
            data = { ...data, ...pushData };
        } catch (e) {
            data.body = event.data.text() || data.body;
        }
    }
    
    const options = {
        title: data.title,
        body: data.body,
        icon: data.icon || '/img/logo.png',
        badge: data.badge || '/img/logo.png',
        image: data.image,
        tag: data.tag || 'tem-de-tudo-notification',
        renotify: data.renotify || false,
        requireInteraction: data.requireInteraction || false,
        silent: data.silent || false,
        vibrate: data.vibrate || [200, 100, 200],
        actions: [
            {
                action: 'open',
                title: 'Abrir',
                icon: '/img/logo.png'
            },
            {
                action: 'close',
                title: 'Dispensar'
            }
        ],
        data: {
            url: data.url || '/',
            timestamp: Date.now(),
            ...data.data
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// ================================
// CLIQUE EM NOTIFICAÇÕES
// ================================
self.addEventListener('notificationclick', (event) => {
    console.log('👆 Clique em notificação:', event.action);
    
    event.notification.close();
    
    if (event.action === 'close') {
        return;
    }
    
    const urlToOpen = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Procurar janela já aberta com a mesma origem
                for (const client of clientList) {
                    if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                        if (urlToOpen !== '/') {
                            client.navigate(urlToOpen);
                        }
                        return client.focus();
                    }
                }
                
                // Abrir nova janela
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// ================================
// SINCRONIZAÇÃO EM BACKGROUND
// ================================
self.addEventListener('sync', (event) => {
    console.log('🔄 Sincronização em background:', event.tag);
    
    switch (event.tag) {
        case 'background-sync':
            event.waitUntil(performBackgroundSync());
            break;
        case 'points-sync':
            event.waitUntil(syncOfflinePoints());
            break;
        case 'comments-sync':
            event.waitUntil(syncOfflineComments());
            break;
        default:
            console.log('🔄 Tag de sincronização desconhecida:', event.tag);
    }
});

async function performBackgroundSync() {
    try {
        console.log('🔄 Iniciando sincronização completa...');
        
        // Atualizar cache essencial
        await updateEssentialCache();
        
        // Sincronizar dados pendentes
        await syncPendingOperations();
        
        console.log('✅ Sincronização completa finalizada');
    } catch (error) {
        console.error('❌ Erro na sincronização:', error);
    }
}

async function updateEssentialCache() {
    const essentialUrls = [
        '/',
        '/estabelecimentos.html',
        '/api/establishments',
        '/api/user/profile'
    ];
    
    const cache = await caches.open(DYNAMIC_CACHE);
    
    for (const url of essentialUrls) {
        try {
            const response = await fetch(url);
            if (response.ok) {
                await cache.put(url, response.clone());
                console.log('✅ Cache atualizado:', url);
            }
        } catch (error) {
            console.log('⚠️ Falha ao atualizar cache:', url);
        }
    }
}

async function syncPendingOperations() {
    // Implementar sincronização de operações pendentes
    // (comentários, avaliações, pontos, etc.)
    console.log('📤 Sincronizando operações pendentes...');
}

async function syncOfflinePoints() {
    // Implementar sincronização específica de pontos
    console.log('🎯 Sincronizando pontos offline...');
}

async function syncOfflineComments() {
    // Implementar sincronização específica de comentários
    console.log('💬 Sincronizando comentários offline...');
}

// ================================
// COMUNICAÇÃO COM O APP
// ================================
self.addEventListener('message', (event) => {
    const { type, data } = event.data || {};
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.ports[0]?.postMessage({ success: true });
            });
            break;
            
        case 'UPDATE_CACHE':
            updateSpecificCache(data.urls).then(() => {
                event.ports[0]?.postMessage({ success: true });
            });
            break;
            
        case 'GET_CACHE_INFO':
            getCacheInfo().then(info => {
                event.ports[0]?.postMessage(info);
            });
            break;
            
        case 'FORCE_SYNC':
            registration.sync.register('background-sync').then(() => {
                event.ports[0]?.postMessage({ success: true });
            });
            break;
    }
});

async function clearAllCaches() {
    const cacheNames = await caches.keys();
    return Promise.all(
        cacheNames.map(cacheName => {
            console.log('🗑️ Removendo cache:', cacheName);
            return caches.delete(cacheName);
        })
    );
}

async function updateSpecificCache(urls) {
    const cache = await caches.open(DYNAMIC_CACHE);
    return Promise.all(
        urls.map(async (url) => {
            try {
                const response = await fetch(url);
                if (response.ok) {
                    await cache.put(url, response);
                }
            } catch (error) {
                console.error('❌ Erro ao atualizar cache:', url);
            }
        })
    );
}

async function getCacheInfo() {
    const cacheNames = await caches.keys();
    const info = {
        caches: {},
        totalSize: 0,
        version: CACHE_NAME
    };
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        info.caches[cacheName] = {
            size: keys.length,
            urls: keys.map(req => req.url).slice(0, 10) // Limitar para não sobrecarregar
        };
        info.totalSize += keys.length;
    }
    
    return info;
}

// ================================
// INICIALIZAÇÃO
// ================================
console.log('🎯 Tem de Tudo Service Worker Mobile carregado');
console.log('📋 Cache Name:', CACHE_NAME);
console.log('📂 Arquivos estáticos:', STATIC_FILES.length);
console.log('🔗 APIs monitoradas:', API_URLS.length);

// Reportar versão quando solicitado
self.addEventListener('message', (event) => {
    if (event.data === 'GET_VERSION') {
        event.ports[0]?.postMessage({ 
            version: CACHE_NAME,
            timestamp: Date.now(),
            static_files: STATIC_FILES.length,
            api_urls: API_URLS.length
        });
    }
});