/**
 * ================================================
 * INSTALADOR PWA - TEM DE TUDO
 * ================================================
 * Adiciona funcionalidade de instalar como app
 */

let deferredPrompt;

// Registra o Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('✅ Service Worker registrado:', registration.scope);
            
            // Verifica atualizações a cada 60 segundos
            setInterval(() => {
                registration.update();
            }, 60000);
            
        } catch (error) {
            console.error('❌ Erro ao registrar Service Worker:', error);
        }
    });
}

// Captura o evento de instalação
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('🎯 Prompt de instalação capturado');
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostra botão de instalação
    mostrarBotaoInstalar();
});

// Detecta quando o app foi instalado
window.addEventListener('appinstalled', () => {
    console.log('✅ App instalado com sucesso!');
    deferredPrompt = null;
    
    // Esconde botão de instalação
    esconderBotaoInstalar();
    
    // Notifica usuário
    if (window.NotificationSystem) {
        NotificationSystem.add({
            title: '🎉 App Instalado!',
            message: 'Tem de Tudo foi instalado com sucesso no seu dispositivo',
            icon: 'fa-check-circle',
            type: 'success'
        });
    }
});

/**
 * Função para instalar o PWA
 */
async function instalarPWA() {
    if (!deferredPrompt) {
        console.log('⚠️ Prompt de instalação não disponível');
        return;
    }
    
    // Mostra o prompt
    deferredPrompt.prompt();
    
    // Aguarda a escolha do usuário
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`👤 Usuário ${outcome === 'accepted' ? 'aceitou' : 'recusou'} instalar`);
    
    deferredPrompt = null;
}

/**
 * Cria e mostra botão de instalação flutuante
 */
function mostrarBotaoInstalar() {
    // Verifica se já existe
    if (document.getElementById('install-pwa-btn')) return;
    
    // Cria botão
    const btn = document.createElement('button');
    btn.id = 'install-pwa-btn';
    btn.innerHTML = `
        <i class="fas fa-download"></i>
        <span>Instalar App</span>
    `;
    btn.onclick = instalarPWA;
    
    // Estilo
    btn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 30px;
        padding: 15px 25px;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        z-index: 9999;
        transition: all 0.3s;
        animation: slideInRight 0.5s ease-out;
    `;
    
    // Adiciona hover
    btn.onmouseenter = () => {
        btn.style.transform = 'scale(1.1)';
        btn.style.boxShadow = '0 15px 40px rgba(102, 126, 234, 0.6)';
    };
    btn.onmouseleave = () => {
        btn.style.transform = 'scale(1)';
        btn.style.boxShadow = '0 10px 30px rgba(102, 126, 234, 0.4)';
    };
    
    // Adiciona ao body
    document.body.appendChild(btn);
    
    // Animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Esconde botão de instalação
 */
function esconderBotaoInstalar() {
    const btn = document.getElementById('install-pwa-btn');
    if (btn) {
        btn.style.animation = 'slideOutRight 0.5s ease-out';
        setTimeout(() => btn.remove(), 500);
    }
}

/**
 * Verifica se app já está instalado
 */
function verificarSeInstalado() {
    // Standalone = instalado
    if (window.matchMedia('(display-mode: standalone)').matches || 
        window.navigator.standalone === true) {
        console.log('✅ App rodando em modo instalado');
        return true;
    }
    return false;
}

/**
 * Solicita permissão para notificações
 */
async function solicitarPermissaoNotificacoes() {
    if ('Notification' in window && Notification.permission === 'default') {
        const permission = await Notification.requestPermission();
        console.log('🔔 Permissão de notificações:', permission);
        return permission === 'granted';
    }
    return Notification.permission === 'granted';
}

// Log se está instalado
console.log('📱 PWA Instalado:', verificarSeInstalado());

// Exporta funções globalmente
window.PWA = {
    instalar: instalarPWA,
    estaInstalado: verificarSeInstalado,
    solicitarNotificacoes: solicitarPermissaoNotificacoes
};
