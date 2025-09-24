/**
 * Firebase Push Notifications - TemDeTudo
 * Sistema completo de notificações para clientes, empresas e admins
 */

// Configuração Firebase
const firebaseConfig = {
    apiKey: "AIzaSyBsomeFirebaseAPIKeyHere",
    authDomain: "temdetudo-app.firebaseapp.com",
    projectId: "temdetudo-app",
    storageBucket: "temdetudo-app.appspot.com",
    messagingSenderId: "123456789012",
    appId: "1:123456789012:web:abcdef123456"
};

let messaging = null;
let currentFCMToken = null;

// Inicializar Firebase
async function initializeFirebase() {
    try {
        // Importar Firebase (assumindo que está incluído via CDN)
        if (typeof firebase === 'undefined') {
            console.warn('Firebase não carregado. Incluir scripts Firebase no HTML.');
            return;
        }

        // Inicializar Firebase
        if (!firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }

        // Inicializar Messaging
        messaging = firebase.messaging();
        
        // Registrar Service Worker
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.register('/sw-notifications.js');
            console.log('Service Worker registrado:', registration);
        }

        // Configurar tratamento de mensagens em primeiro plano
        setupForegroundMessaging();
        
        // Solicitar permissão e obter token
        await requestNotificationPermission();

        console.log('✅ Firebase inicializado com sucesso');
        return true;

    } catch (error) {
        console.error('❌ Erro ao inicializar Firebase:', error);
        return false;
    }
}

// Solicitar permissão para notificações
async function requestNotificationPermission() {
    try {
        // Verificar se o navegador suporta notificações
        if (!('Notification' in window)) {
            console.warn('Este navegador não suporta notificações');
            return false;
        }

        // Solicitar permissão
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('✅ Permissão de notificação concedida');
            
            // Obter token FCM
            const token = await messaging.getToken({
                vapidKey: 'BH7s-4c8_ZMxMm4QqW2sVzEbD5pR3kX9tY6nL1oP2aQ7mK8jF4vC9eN3bG5hI0uT8s' // VAPID key do Firebase
            });

            if (token) {
                currentFCMToken = token;
                console.log('🔑 Token FCM obtido:', token.substring(0, 20) + '...');
                
                // Enviar token para o servidor
                await updateFCMTokenOnServer(token);
                
                // Salvar localmente
                localStorage.setItem('fcm_token', token);
                
                return true;
            } else {
                console.warn('⚠️ Não foi possível obter o token FCM');
            }
        } else {
            console.warn('❌ Permissão de notificação negada');
        }

        return false;

    } catch (error) {
        console.error('❌ Erro ao solicitar permissão:', error);
        return false;
    }
}

// Configurar mensagens em primeiro plano
function setupForegroundMessaging() {
    if (!messaging) return;

    messaging.onMessage((payload) => {
        console.log('📬 Mensagem recebida em primeiro plano:', payload);

        const { title, body, icon } = payload.notification || {};
        const data = payload.data || {};

        // Mostrar notificação customizada
        showCustomNotification(title, body, icon, data);

        // Marcar como recebida na API
        if (data.notification_id) {
            markNotificationAsReceived(data.notification_id);
        }
    });

    // Lidar com cliques em notificações
    messaging.onTokenRefresh(async () => {
        console.log('🔄 Token FCM renovado');
        const newToken = await messaging.getToken();
        
        if (newToken && newToken !== currentFCMToken) {
            currentFCMToken = newToken;
            localStorage.setItem('fcm_token', newToken);
            await updateFCMTokenOnServer(newToken);
        }
    });
}

// Mostrar notificação customizada
function showCustomNotification(title, body, icon, data) {
    // Se a página não tem foco, mostrar notificação do navegador
    if (document.hidden) {
        if (Notification.permission === 'granted') {
            const notification = new Notification(title, {
                body,
                icon: icon || '/favicon.ico',
                badge: '/img/badge.png',
                tag: data.type || 'default',
                requireInteraction: data.priority === 'high',
                data: data
            });

            notification.onclick = function() {
                handleNotificationClick(data);
                notification.close();
            };
        }
        return;
    }

    // Mostrar notificação in-app
    showInAppNotification(title, body, icon, data);
}

// Mostrar notificação dentro da aplicação
function showInAppNotification(title, body, icon, data) {
    // Criar elemento de notificação
    const notificationEl = document.createElement('div');
    notificationEl.className = 'in-app-notification';
    notificationEl.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <img src="${icon || '/favicon.ico'}" alt="Icon" />
            </div>
            <div class="notification-text">
                <div class="notification-title">${title}</div>
                <div class="notification-body">${body}</div>
            </div>
            <div class="notification-actions">
                <button class="notification-close" onclick="this.closest('.in-app-notification').remove()">×</button>
            </div>
        </div>
    `;

    // Adicionar estilos se não existirem
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .in-app-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                border-left: 4px solid #667eea;
                max-width: 350px;
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                cursor: pointer;
            }
            .notification-content {
                display: flex;
                align-items: center;
                padding: 15px;
            }
            .notification-icon img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                margin-right: 12px;
            }
            .notification-text {
                flex: 1;
            }
            .notification-title {
                font-weight: bold;
                color: #333;
                font-size: 14px;
                margin-bottom: 4px;
            }
            .notification-body {
                color: #666;
                font-size: 13px;
                line-height: 1.4;
            }
            .notification-close {
                background: none;
                border: none;
                font-size: 20px;
                color: #999;
                cursor: pointer;
                padding: 0;
                margin-left: 10px;
            }
            .notification-close:hover {
                color: #666;
            }
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    // Adicionar ao DOM
    document.body.appendChild(notificationEl);

    // Click handler
    notificationEl.addEventListener('click', () => {
        handleNotificationClick(data);
        notificationEl.remove();
    });

    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (notificationEl.parentNode) {
            notificationEl.remove();
        }
    }, 5000);
}

// Lidar com clique em notificação
function handleNotificationClick(data) {
    const { action, type } = data;

    switch (action) {
        case 'open_profile':
            window.location.href = '/profile-client.html';
            break;
        case 'open_admin':
            window.location.href = '/admin.html';
            break;
        case 'open_company':
            window.location.href = '/profile-company.html';
            break;
        case 'open_security':
            window.location.href = '/login.html';
            break;
        case 'open_app':
        default:
            // Focar na aba atual ou ir para homepage
            window.focus();
            if (window.location.pathname === '/') return;
            window.location.href = '/';
            break;
    }

    // Marcar como lida
    if (data.notification_id) {
        markNotificationAsRead(data.notification_id);
    }
}

// Atualizar token FCM no servidor
async function updateFCMTokenOnServer(token) {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        
        if (!authToken) {
            console.log('ℹ️ Usuário não logado, token será enviado no próximo login');
            return;
        }

        const response = await fetch(`${API_BASE}/notifications/fcm-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ fcm_token: token })
        });

        if (response.ok) {
            console.log('✅ Token FCM enviado para o servidor');
        } else {
            console.warn('⚠️ Erro ao enviar token FCM:', await response.text());
        }

    } catch (error) {
        console.error('❌ Erro ao atualizar token FCM:', error);
    }
}

// Marcar notificação como recebida
async function markNotificationAsReceived(notificationId) {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        if (!authToken) return;

        await fetch(`${API_BASE}/notifications/${notificationId}/received`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

    } catch (error) {
        console.error('Erro ao marcar notificação como recebida:', error);
    }
}

// Marcar notificação como lida
async function markNotificationAsRead(notificationId) {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        if (!authToken) return;

        const response = await fetch(`${API_BASE}/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (response.ok) {
            console.log('✅ Notificação marcada como lida');
            updateNotificationCount();
        }

    } catch (error) {
        console.error('Erro ao marcar notificação como lida:', error);
    }
}

// Obter notificações do usuário
async function getUserNotifications(page = 1) {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        if (!authToken) return [];

        const response = await fetch(`${API_BASE}/notifications?page=${page}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            return data.data;
        }

        return [];

    } catch (error) {
        console.error('Erro ao obter notificações:', error);
        return [];
    }
}

// Atualizar contador de notificações
async function updateNotificationCount() {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        if (!authToken) return;

        const response = await fetch(`${API_BASE}/notifications`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const unreadCount = data.unread_count || 0;

            // Atualizar badge de notificações
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

    } catch (error) {
        console.error('Erro ao atualizar contador de notificações:', error);
    }
}

// Configurações de notificação
async function updateNotificationSettings(settings) {
    try {
        const authToken = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');
        if (!authToken) return false;

        const response = await fetch(`${API_BASE}/notifications/settings`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(settings)
        });

        return response.ok;

    } catch (error) {
        console.error('Erro ao atualizar configurações:', error);
        return false;
    }
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', () => {
    // Aguardar um pouco para garantir que outros scripts carregaram
    setTimeout(initializeFirebase, 1000);
    
    // Atualizar contador de notificações a cada 30 segundos
    setInterval(updateNotificationCount, 30000);
});

// Exportar funções para uso global
window.NotificationManager = {
    init: initializeFirebase,
    requestPermission: requestNotificationPermission,
    updateToken: updateFCMTokenOnServer,
    markAsRead: markNotificationAsRead,
    getNotifications: getUserNotifications,
    updateCount: updateNotificationCount,
    updateSettings: updateNotificationSettings
};