// ============================================
// SISTEMA SIMPLES DE NOTIFICAÇÕES - 100% GRÁTIS
// ============================================
// 1. Push Notifications (Web API - nativo, grátis)
// 2. Email (EmailJS - 300 emails/mês grátis)
// 3. In-App (localStorage - 100% grátis)
// ============================================

const API_URL = window.location.hostname === 'localhost' ? 
    'http://localhost:8000/api' : 
    'https://tem-de-tudo-9g7r.onrender.com/api';

// ============================================
// 1. PUSH NOTIFICATIONS (Web API)
// ============================================

// Verifica se Push Notifications está habilitado
function checkPushPermission() {
    if (!('Notification' in window)) {
        console.log('Push Notifications não suportadas neste navegador');
        return false;
    }
    
    const status = document.getElementById('pushStatus');
    if (Notification.permission === 'granted') {
        if (status) {
            status.className = 'status-badge active';
            status.innerHTML = '<i class="fas fa-circle"></i> Ativo';
        }
        return true;
    } else if (Notification.permission === 'denied') {
        if (status) {
            status.className = 'status-badge inactive';
            status.innerHTML = '<i class="fas fa-circle"></i> Bloqueado';
        }
        return false;
    } else {
        if (status) {
            status.className = 'status-badge inactive';
            status.innerHTML = '<i class="fas fa-circle"></i> Inativo';
        }
        return false;
    }
}

// Habilita Push Notifications
async function enablePushNotifications() {
    if (!('Notification' in window)) {
        alert('Seu navegador não suporta notificações push 😔');
        return;
    }
    
    try {
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            alert('✅ Notificações Push ativadas com sucesso!');
            checkPushPermission();
            
            // Registrar Service Worker para Push Notifications
            if ('serviceWorker' in navigator) {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registrado:', registration);
            }
        } else {
            alert('❌ Você negou permissão para notificações');
        }
    } catch (error) {
        console.error('Erro ao habilitar notificações:', error);
        alert('Erro ao habilitar notificações');
    }
}

// Testa Push Notification
function testPushNotification() {
    if (Notification.permission !== 'granted') {
        alert('⚠️ Você precisa ativar as notificações primeiro!');
        enablePushNotifications();
        return;
    }
    
    const userName = localStorage.getItem('userName') || 'Cliente';
    
    const options = {
        body: `Olá ${userName}! Esta é uma notificação de teste do sistema Tem de Tudo 🎉`,
        icon: '/images/logo.png',
        badge: '/images/badge.png',
        vibrate: [200, 100, 200],
        tag: 'test-notification',
        requireInteraction: false,
        actions: [
            {
                action: 'open',
                title: 'Abrir App',
                icon: '/images/icon-open.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: '/images/icon-close.png'
            }
        ],
        data: {
            url: '/dashboard-cliente.html',
            dateOfArrival: Date.now()
        }
    };
    
    // Mostra notificação
    const notification = new Notification('🔔 Nova Notificação - Tem de Tudo', options);
    
    notification.onclick = function(event) {
        event.preventDefault();
        window.focus();
        window.location.href = '/dashboard-cliente.html';
        notification.close();
    };
    
    // Adiciona ao histórico in-app
    addInAppNotification({
        title: 'Notificação Push Teste',
        message: `Olá ${userName}! Esta é uma notificação de teste do sistema Tem de Tudo 🎉`,
        icon: 'fa-rocket',
        type: 'info'
    });
    
    alert('✅ Notificação enviada! Veja a notificação do navegador');
}

// ============================================
// 2. EMAIL NOTIFICATIONS (EmailJS)
// ============================================

// Configuração EmailJS (GRÁTIS - 300 emails/mês)
// Cadastre-se em: https://www.emailjs.com/
// Pegue suas credenciais e substitua abaixo:

const EMAILJS_CONFIG = {
    serviceId: 'service_temdettudo',  // Substituir pela sua Service ID
    templateId: 'template_notificacao', // Substituir pela sua Template ID
    publicKey: 'YOUR_PUBLIC_KEY'        // Substituir pela sua Public Key
};

// Inicializa EmailJS
function initEmailJS() {
    if (typeof emailjs !== 'undefined') {
        emailjs.init(EMAILJS_CONFIG.publicKey);
        console.log('✅ EmailJS inicializado');
    } else {
        console.error('❌ EmailJS não carregado');
    }
}

// Envia Email de Teste
async function testEmailNotification() {
    const emailInput = document.getElementById('userEmail');
    const userEmail = emailInput ? emailInput.value : '';
    
    if (!userEmail || !userEmail.includes('@')) {
        alert('⚠️ Digite um email válido primeiro!');
        return;
    }
    
    // Se não tiver EmailJS configurado, mostra instruções
    if (!EMAILJS_CONFIG.publicKey || EMAILJS_CONFIG.publicKey === 'YOUR_PUBLIC_KEY') {
        alert(`📧 CONFIGURAÇÃO NECESSÁRIA:

1. Acesse: https://www.emailjs.com/
2. Crie conta GRÁTIS (300 emails/mês)
3. Crie um serviço de email
4. Crie um template
5. Copie as credenciais para o arquivo notification-system-simple.js

Por enquanto, vou simular o envio do email...`);
        
        // Simula envio
        setTimeout(() => {
            alert(`✅ Email simulado enviado para: ${userEmail}

CONTEÚDO:
━━━━━━━━━━━━━━━━━━━━
🔔 Nova Notificação - Tem de Tudo

Olá!

Esta é uma notificação de teste do sistema Tem de Tudo.

Você tem 3 promoções disponíveis!
🎁 Desconto especial de 20%

Acesse o app para ver mais.

━━━━━━━━━━━━━━━━━━━━`);
            
            // Adiciona ao histórico
            addInAppNotification({
                title: 'Email de Teste Enviado',
                message: `Email simulado enviado para ${userEmail}`,
                icon: 'fa-envelope',
                type: 'success'
            });
        }, 1000);
        
        return;
    }
    
    try {
        const userName = localStorage.getItem('userName') || 'Cliente';
        
        const templateParams = {
            to_email: userEmail,
            to_name: userName,
            subject: '🔔 Nova Notificação - Tem de Tudo',
            message: 'Esta é uma notificação de teste do sistema Tem de Tudo!',
            app_url: window.location.origin,
            bonus_count: '3',
            promo_title: 'Desconto especial de 20%'
        };
        
        const response = await emailjs.send(
            EMAILJS_CONFIG.serviceId,
            EMAILJS_CONFIG.templateId,
            templateParams
        );
        
        console.log('Email enviado:', response);
        alert(`✅ Email enviado com sucesso para: ${userEmail}`);
        
        // Adiciona ao histórico
        addInAppNotification({
            title: 'Email Enviado',
            message: `Email enviado para ${userEmail}`,
            icon: 'fa-envelope',
            type: 'success'
        });
        
    } catch (error) {
        console.error('Erro ao enviar email:', error);
        alert('❌ Erro ao enviar email. Verifique as configurações do EmailJS.');
    }
}

// ============================================
// 3. IN-APP NOTIFICATIONS (localStorage)
// ============================================

// Adiciona notificação in-app
function addInAppNotification(data) {
    const notification = {
        id: Date.now(),
        title: data.title,
        message: data.message,
        icon: data.icon || 'fa-bell',
        type: data.type || 'info', // info, success, warning, error
        timestamp: new Date().toISOString(),
        read: false,
        url: data.url || null
    };
    
    // Pega notificações existentes
    const notifications = getInAppNotifications();
    
    // Adiciona nova notificação no início
    notifications.unshift(notification);
    
    // Limita a 50 notificações
    if (notifications.length > 50) {
        notifications.splice(50);
    }
    
    // Salva no localStorage
    localStorage.setItem('app_notifications', JSON.stringify(notifications));
    
    // Atualiza UI
    renderNotifications();
    updateNotificationBadge();
    
    return notification;
}

// Pega todas notificações
function getInAppNotifications() {
    const stored = localStorage.getItem('app_notifications');
    return stored ? JSON.parse(stored) : [];
}

// Marca notificação como lida
function markAsRead(notificationId) {
    const notifications = getInAppNotifications();
    const notification = notifications.find(n => n.id === notificationId);
    
    if (notification) {
        notification.read = true;
        localStorage.setItem('app_notifications', JSON.stringify(notifications));
        renderNotifications();
        updateNotificationBadge();
    }
}

// Deleta notificação
function deleteNotification(notificationId) {
    let notifications = getInAppNotifications();
    notifications = notifications.filter(n => n.id !== notificationId);
    localStorage.setItem('app_notifications', JSON.stringify(notifications));
    renderNotifications();
    updateNotificationBadge();
}

// Limpa todas notificações
function clearAllNotifications() {
    if (confirm('Tem certeza que deseja limpar todas as notificações?')) {
        localStorage.setItem('app_notifications', JSON.stringify([]));
        renderNotifications();
        updateNotificationBadge();
        alert('✅ Todas notificações foram removidas');
    }
}

// Renderiza lista de notificações
function renderNotifications() {
    const container = document.getElementById('notificationList');
    if (!container) return;
    
    const notifications = getInAppNotifications();
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>Nenhuma notificação ainda</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notifications.map(notif => {
        const date = new Date(notif.timestamp);
        const timeAgo = getTimeAgo(date);
        
        return `
            <div class="notification-item ${!notif.read ? 'unread' : ''}" 
                 onclick="markAsRead(${notif.id})"
                 style="cursor: pointer;">
                <div class="notification-icon-small">
                    <i class="fas ${notif.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">
                        <i class="fas fa-clock"></i> ${timeAgo}
                    </div>
                </div>
                <button onclick="event.stopPropagation(); deleteNotification(${notif.id})" 
                        style="background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 8px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }).join('');
}

// Atualiza badge de notificações não lidas
function updateNotificationBadge() {
    const notifications = getInAppNotifications();
    const unreadCount = notifications.filter(n => !n.read).length;
    
    // Atualiza badges no menu
    const badges = document.querySelectorAll('.notification-badge, .badge');
    badges.forEach(badge => {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    });
    
    // Atualiza título da página
    if (unreadCount > 0) {
        document.title = `(${unreadCount}) Notificações - Tem de Tudo`;
    }
}

// Calcula tempo decorrido
function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + ' anos atrás';
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + ' meses atrás';
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + ' dias atrás';
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + ' horas atrás';
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + ' minutos atrás';
    
    return 'agora mesmo';
}

// Testa notificação in-app
function testInAppNotification() {
    const types = ['info', 'success', 'warning', 'error'];
    const icons = ['fa-gift', 'fa-star', 'fa-heart', 'fa-trophy', 'fa-fire'];
    const titles = [
        '🎁 Novo Bônus Disponível!',
        '⭐ Você ganhou pontos!',
        '❤️ Promoção Exclusiva',
        '🏆 Conquista Desbloqueada',
        '🔥 Oferta Relâmpago'
    ];
    const messages = [
        'Você ganhou 50 pontos de bônus!',
        'Sua empresa favorita tem novidades',
        'Desconto de 30% em produtos selecionados',
        'Parabéns! Você completou 10 check-ins',
        'Apenas hoje: Compre 1 e leve 2'
    ];
    
    const randomType = types[Math.floor(Math.random() * types.length)];
    const randomIcon = icons[Math.floor(Math.random() * icons.length)];
    const randomTitle = titles[Math.floor(Math.random() * titles.length)];
    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
    
    addInAppNotification({
        title: randomTitle,
        message: randomMessage,
        icon: randomIcon,
        type: randomType,
        url: '/dashboard-cliente.html'
    });
    
    alert('✅ Notificação adicionada ao histórico!');
}

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

// Carrega email do usuário do localStorage
function loadUserEmail() {
    const emailInput = document.getElementById('userEmail');
    if (emailInput) {
        const savedEmail = localStorage.getItem('userEmail');
        if (savedEmail) {
            emailInput.value = savedEmail;
        }
        
        // Salva email quando mudar
        emailInput.addEventListener('change', () => {
            localStorage.setItem('userEmail', emailInput.value);
        });
    }
}

// Cria notificações de exemplo para demonstração
function createDemoNotifications() {
    const notifications = getInAppNotifications();
    
    // Só cria se não tiver notificações
    if (notifications.length === 0) {
        addInAppNotification({
            title: '🎉 Bem-vindo ao Sistema!',
            message: 'Esta é sua central de notificações. Aqui você verá todas as novidades!',
            icon: 'fa-rocket',
            type: 'info'
        });
        
        addInAppNotification({
            title: '🎁 Bônus de Boas-Vindas',
            message: 'Você ganhou 100 pontos de bônus por se cadastrar!',
            icon: 'fa-gift',
            type: 'success'
        });
    }
}

// ============================================
// INICIALIZAÇÃO
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🔔 Sistema de Notificações Inicializado');
    
    // Verifica permissão de push
    checkPushPermission();
    
    // Inicializa EmailJS
    initEmailJS();
    
    // Carrega email do usuário
    loadUserEmail();
    
    // Renderiza notificações
    renderNotifications();
    
    // Atualiza badge
    updateNotificationBadge();
    
    // Cria notificações de demo (apenas na primeira vez)
    createDemoNotifications();
});

// ============================================
// API PARA OUTRAS PÁGINAS USAREM
// ============================================

window.NotificationSystem = {
    // Push Notifications
    enablePush: enablePushNotifications,
    testPush: testPushNotification,
    checkPushPermission: checkPushPermission,
    
    // Email
    sendEmail: testEmailNotification,
    
    // In-App
    add: addInAppNotification,
    getAll: getInAppNotifications,
    markRead: markAsRead,
    delete: deleteNotification,
    clear: clearAllNotifications,
    updateBadge: updateNotificationBadge,
    
    // Utilitários
    render: renderNotifications
};

console.log('✅ NotificationSystem disponível globalmente');
