/**
 * Service Worker para Push Notifications - TemDeTudo
 * Lida com notificações em background
 */

// Importar Firebase Messaging (Background)
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Configuração Firebase
const firebaseConfig = {
    apiKey: "AIzaSyBsomeFirebaseAPIKeyHere",
    authDomain: "temdetudo-app.firebaseapp.com",
    projectId: "temdetudo-app",
    storageBucket: "temdetudo-app.appspot.com",
    messagingSenderId: "123456789012",
    appId: "1:123456789012:web:abcdef123456"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Lidar com mensagens em background
messaging.onBackgroundMessage((payload) => {
    console.log('📱 Notificação recebida em background:', payload);

    const { title, body, icon } = payload.notification || {};
    const data = payload.data || {};

    // Configurações da notificação
    const notificationOptions = {
        body: body || 'Nova notificação do TemDeTudo',
        icon: icon || '/favicon.ico',
        badge: '/img/badge.png',
        tag: data.type || 'default',
        requireInteraction: data.priority === 'high',
        actions: getNotificationActions(data.type),
        data: {
            ...data,
            click_action: data.action || 'open_app',
            url: getNotificationURL(data)
        },
        vibrate: data.priority === 'high' ? [200, 100, 200] : [100, 50, 100],
        silent: data.priority === 'low'
    };

    // Mostrar notificação
    return self.registration.showNotification(
        title || 'TemDeTudo',
        notificationOptions
    );
});

// Definir ações baseadas no tipo
function getNotificationActions(type) {
    const actions = {
        'welcome': [
            { action: 'open', title: '🚀 Começar', icon: '/img/start.png' },
            { action: 'dismiss', title: 'Depois', icon: '/img/close.png' }
        ],
        'points_gained': [
            { action: 'view_profile', title: '👤 Ver Perfil', icon: '/img/profile.png' },
            { action: 'view_rewards', title: '🎁 Ver Prêmios', icon: '/img/rewards.png' }
        ],
        'points_redeemed': [
            { action: 'view_profile', title: '👤 Ver Perfil', icon: '/img/profile.png' },
            { action: 'dismiss', title: 'OK', icon: '/img/ok.png' }
        ],
        'level_up': [
            { action: 'view_profile', title: '🏆 Ver Nível', icon: '/img/level.png' },
            { action: 'share', title: '📤 Compartilhar', icon: '/img/share.png' }
        ],
        'security_alert': [
            { action: 'open_security', title: '🔒 Verificar', icon: '/img/security.png' },
            { action: 'dismiss', title: 'Ignorar', icon: '/img/close.png' }
        ],
        'admin_report': [
            { action: 'open_admin', title: '📊 Ver Dashboard', icon: '/img/dashboard.png' },
            { action: 'dismiss', title: 'Depois', icon: '/img/close.png' }
        ]
    };

    return actions[type] || [
        { action: 'open', title: 'Abrir', icon: '/img/open.png' }
    ];
}

// Obter URL baseada nos dados
function getNotificationURL(data) {
    const { action, type, user_type } = data;

    const urls = {
        'open_profile': user_type === 'company' ? '/profile-company.html' : '/profile-client.html',
        'open_admin': '/admin.html',
        'open_security': '/login.html',
        'view_rewards': '/estabelecimentos.html',
        'view_profile': '/profile-client.html',
        'open_app': '/',
        'default': '/'
    };

    return urls[action] || urls['default'];
}

// Lidar com clique na notificação
self.addEventListener('notificationclick', (event) => {
    console.log('🖱️ Clique na notificação:', event);

    event.notification.close();

    const { action } = event;
    const data = event.notification.data || {};

    // Lidar com ações específicas
    let targetUrl = '/';

    switch (action) {
        case 'open':
        case 'open_app':
            targetUrl = data.url || '/';
            break;
        case 'view_profile':
            targetUrl = '/profile-client.html';
            break;
        case 'view_rewards':
            targetUrl = '/estabelecimentos.html';
            break;
        case 'open_admin':
            targetUrl = '/admin.html';
            break;
        case 'open_security':
            targetUrl = '/login.html';
            break;
        case 'share':
            handleShareAction(data);
            return;
        case 'dismiss':
            // Apenas fechar
            return;
        default:
            targetUrl = data.url || '/';
            break;
    }

    // Abrir ou focar na página
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Procurar por uma aba já aberta com a URL
                for (const client of clientList) {
                    const clientUrl = new URL(client.url);
                    const targetUrlObj = new URL(targetUrl, self.location.origin);
                    
                    if (clientUrl.pathname === targetUrlObj.pathname && client.focus) {
                        // Marcar notificação como lida
                        markNotificationAsRead(data.notification_id);
                        return client.focus();
                    }
                }
                
                // Se não encontrou, abrir nova aba
                if (clients.openWindow) {
                    markNotificationAsRead(data.notification_id);
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

// Lidar com fechamento da notificação
self.addEventListener('notificationclose', (event) => {
    console.log('❌ Notificação fechada:', event);
    
    // Analytics: registrar fechamento
    const data = event.notification.data || {};
    if (data.notification_id) {
        // Registrar que foi fechada sem interação
        fetch('/api/notifications/' + data.notification_id + '/closed', {
            method: 'POST'
        }).catch(() => {
            // Ignorar erros silenciosamente
        });
    }
});

// Compartilhar conquista
function handleShareAction(data) {
    if (navigator.share) {
        navigator.share({
            title: '🎉 Subi de nível no TemDeTudo!',
            text: `Acabei de alcançar o nível ${data.new_level} no TemDeTudo! 🏆`,
            url: 'https://temdetudo.com'
        }).catch(() => {
            // Fallback para compartilhamento manual
            copyToClipboard(`🎉 Subi para o nível ${data.new_level} no TemDeTudo! Venha você também: https://temdetudo.com`);
        });
    } else {
        // Fallback: copiar para clipboard
        copyToClipboard(`🎉 Subi para o nível ${data.new_level} no TemDeTudo! Venha você também: https://temdetudo.com`);
    }
}

// Copiar para clipboard
function copyToClipboard(text) {
    // Usar API de clipboard se disponível
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
}

// Marcar notificação como lida
function markNotificationAsRead(notificationId) {
    if (!notificationId) return;

    // Tentar obter token de auth do IndexedDB ou localStorage
    // Como é service worker, usar fetch direto
    fetch('/api/notifications/' + notificationId + '/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }).catch((error) => {
        console.log('Erro ao marcar como lida:', error);
    });
}

// Cache de notificações para funcionamento offline
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker instalado');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('✅ Service Worker ativado');
    event.waitUntil(self.clients.claim());
});

// Sincronização em background (para quando voltar online)
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync-notifications') {
        event.waitUntil(syncPendingNotifications());
    }
});

// Sincronizar notificações pendentes
async function syncPendingNotifications() {
    try {
        // Verificar notificações pendentes quando voltar online
        const response = await fetch('/api/notifications/sync', {
            method: 'POST'
        });
        
        if (response.ok) {
            console.log('📡 Notificações sincronizadas');
        }
    } catch (error) {
        console.log('Erro na sincronização:', error);
    }
}