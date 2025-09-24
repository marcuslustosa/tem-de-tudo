/**
 * TEM DE TUDO - SISTEMA MOBILE-FIRST
 * JavaScript Principal com Funcionalidades Completas
 */

// ================================
// CONFIGURAÇÕES GLOBAIS
// ================================
const CONFIG = {
    API_BASE_URL: window.location.origin,
    STORAGE_KEYS: {
        AUTH_TOKEN: 'tem_de_tudo_token',
        USER_DATA: 'tem_de_tudo_user',
        FAVORITES: 'tem_de_tudo_favorites',
        CART: 'tem_de_tudo_cart',
        POINTS: 'tem_de_tudo_points'
    },
    TOAST_DURATION: 5000,
    NOTIFICATION_PERMISSION: 'tem_de_tudo_notifications'
};

// ================================
// UTILITÁRIOS
// ================================
const Utils = {
    // Debounce para otimizar performance
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Formatação de telefone brasileiro
    formatPhone(value) {
        const cleanValue = value.replace(/\D/g, '');
        if (cleanValue.length <= 10) {
            return cleanValue.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else {
            return cleanValue.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
    },

    // Formatação de CPF
    formatCPF(value) {
        const cleanValue = value.replace(/\D/g, '');
        return cleanValue.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    },

    // Validação de email
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validação de senha forte
    validatePassword(password) {
        return password.length >= 8 && 
               /[A-Z]/.test(password) && 
               /[a-z]/.test(password) && 
               /\d/.test(password);
    },

    // Storage com fallback
    setStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    },

    getStorage(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Storage error:', e);
            return defaultValue;
        }
    },

    removeStorage(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    }
};

// ================================
// SISTEMA DE TOASTS
// ================================
class ToastManager {
    constructor() {
        this.container = this.createContainer();
        this.toasts = new Map();
    }

    createContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'info', title = null, duration = CONFIG.TOAST_DURATION) {
        const id = Date.now().toString();
        const toast = this.createToast(id, message, type, title);
        
        this.container.appendChild(toast);
        this.toasts.set(id, toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }

        return id;
    }

    createToast(id, message, type, title) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('data-toast-id', id);

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="toastManager.remove('${id}')">✕</button>
        `;

        return toast;
    }

    remove(id) {
        const toast = this.toasts.get(id);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                this.toasts.delete(id);
            }, 300);
        }
    }

    success(message, title = 'Sucesso') {
        return this.show(message, 'success', title);
    }

    error(message, title = 'Erro') {
        return this.show(message, 'error', title);
    }

    warning(message, title = 'Atenção') {
        return this.show(message, 'warning', title);
    }

    info(message, title = 'Informação') {
        return this.show(message, 'info', title);
    }
}

// ================================
// SISTEMA DE AUTENTICAÇÃO
// ================================
class AuthManager {
    constructor() {
        this.user = Utils.getStorage(CONFIG.STORAGE_KEYS.USER_DATA);
        this.token = Utils.getStorage(CONFIG.STORAGE_KEYS.AUTH_TOKEN);
    }

    async login(email, password, remember = false) {
        try {
            // Simular chamada API
            const response = await this.mockApiCall('/api/login', {
                email,
                password,
                remember
            });

            if (response.success) {
                this.user = response.user;
                this.token = response.token;
                
                Utils.setStorage(CONFIG.STORAGE_KEYS.USER_DATA, this.user);
                Utils.setStorage(CONFIG.STORAGE_KEYS.AUTH_TOKEN, this.token);
                
                toastManager.success('Login realizado com sucesso!');
                return { success: true, user: this.user };
            } else {
                throw new Error(response.message || 'Credenciais inválidas');
            }
        } catch (error) {
            toastManager.error(error.message);
            return { success: false, error: error.message };
        }
    }

    async register(userData) {
        try {
            const response = await this.mockApiCall('/api/register', userData);

            if (response.success) {
                this.user = response.user;
                this.token = response.token;
                
                Utils.setStorage(CONFIG.STORAGE_KEYS.USER_DATA, this.user);
                Utils.setStorage(CONFIG.STORAGE_KEYS.AUTH_TOKEN, this.token);
                
                // Bonus de boas-vindas
                await this.addPoints(100, 'Bônus de cadastro');
                
                toastManager.success('Conta criada com sucesso! Você ganhou 100 pontos de bônus!');
                return { success: true, user: this.user };
            } else {
                throw new Error(response.message || 'Erro ao criar conta');
            }
        } catch (error) {
            toastManager.error(error.message);
            return { success: false, error: error.message };
        }
    }

    logout() {
        this.user = null;
        this.token = null;
        
        Utils.removeStorage(CONFIG.STORAGE_KEYS.USER_DATA);
        Utils.removeStorage(CONFIG.STORAGE_KEYS.AUTH_TOKEN);
        
        toastManager.success('Logout realizado com sucesso!');
        window.location.href = '/';
    }

    isAuthenticated() {
        return !!(this.token && this.user);
    }

    getUser() {
        return this.user;
    }

    async mockApiCall(endpoint, data) {
        // Simular delay de rede
        await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 1000));

        // Mock de usuários para teste
        const mockUsers = [
            {
                id: 1,
                name: 'João Silva',
                email: 'joao@email.com',
                password: '123456',
                phone: '(11) 99999-9999',
                points: 1250,
                level: 'silver',
                avatar: 'https://via.placeholder.com/100'
            },
            {
                id: 2,
                name: 'Maria Santos',
                email: 'maria@email.com',
                password: '123456',
                phone: '(11) 88888-8888',
                points: 2500,
                level: 'gold',
                avatar: 'https://via.placeholder.com/100'
            }
        ];

        if (endpoint === '/api/login') {
            const user = mockUsers.find(u => 
                u.email === data.email && u.password === data.password
            );
            
            if (user) {
                const { password, ...userWithoutPassword } = user;
                return {
                    success: true,
                    user: userWithoutPassword,
                    token: 'mock-jwt-token-' + Date.now()
                };
            } else {
                return {
                    success: false,
                    message: 'Email ou senha incorretos'
                };
            }
        }

        if (endpoint === '/api/register') {
            // Verificar se email já existe
            const existingUser = mockUsers.find(u => u.email === data.email);
            if (existingUser) {
                return {
                    success: false,
                    message: 'Este email já está cadastrado'
                };
            }

            const newUser = {
                id: mockUsers.length + 1,
                name: data.name,
                email: data.email,
                phone: data.phone,
                points: 0,
                level: 'bronze',
                avatar: 'https://via.placeholder.com/100'
            };

            return {
                success: true,
                user: newUser,
                token: 'mock-jwt-token-' + Date.now()
            };
        }

        return { success: false, message: 'Endpoint não encontrado' };
    }

    async addPoints(amount, description) {
        if (!this.isAuthenticated()) return false;

        const currentPoints = this.user.points || 0;
        const newPoints = currentPoints + amount;
        
        this.user.points = newPoints;
        this.user.level = this.calculateLevel(newPoints);
        
        Utils.setStorage(CONFIG.STORAGE_KEYS.USER_DATA, this.user);
        
        // Salvar histórico
        const pointsHistory = Utils.getStorage('points_history', []);
        pointsHistory.unshift({
            id: Date.now(),
            amount,
            description,
            timestamp: new Date().toISOString(),
            balance: newPoints
        });
        Utils.setStorage('points_history', pointsHistory);

        return true;
    }

    calculateLevel(points) {
        if (points >= 10000) return 'diamond';
        if (points >= 5000) return 'gold';
        if (points >= 1000) return 'silver';
        return 'bronze';
    }
}

// ================================
// SISTEMA DE NOTIFICAÇÕES PUSH
// ================================
class NotificationManager {
    constructor() {
        this.permission = Notification.permission;
        this.swRegistration = null;
    }

    async requestPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                toastManager.success('Notificações ativadas com sucesso!');
                await this.registerServiceWorker();
            } else {
                toastManager.warning('Notificações não foram permitidas');
            }
            
            return permission;
        } else {
            toastManager.error('Notificações não são suportadas neste navegador');
            return 'denied';
        }
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                this.swRegistration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('Service Worker registrado:', this.swRegistration);
            } catch (error) {
                console.error('Erro ao registrar Service Worker:', error);
            }
        }
    }

    async showNotification(title, options = {}) {
        if (this.permission !== 'granted') {
            return false;
        }

        const defaultOptions = {
            icon: '/img/logo.png',
            badge: '/img/logo.png',
            tag: 'tem-de-tudo',
            renotify: true,
            requireInteraction: false,
            ...options
        };

        if (this.swRegistration) {
            await this.swRegistration.showNotification(title, defaultOptions);
        } else {
            new Notification(title, defaultOptions);
        }

        return true;
    }

    async notifyPointsEarned(points, description) {
        await this.showNotification('🎉 Pontos Ganhos!', {
            body: `Você ganhou ${points} pontos: ${description}`,
            icon: '/img/logo.png'
        });
    }

    async notifyLevelUp(level) {
        const levelNames = {
            bronze: '🥉 Bronze',
            silver: '🥈 Prata', 
            gold: '🥇 Ouro',
            diamond: '💎 Diamante'
        };

        await this.showNotification('🎊 Parabéns!', {
            body: `Você subiu para o nível ${levelNames[level]}!`,
            icon: '/img/logo.png'
        });
    }
}

// ================================
// GERADOR DE QR CODE
// ================================
class QRCodeManager {
    generateQR(text, size = 200) {
        // URL da API QR Code gratuita
        const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
        return qrApiUrl;
    }

    showQRModal(data, title = 'QR Code') {
        const qrUrl = this.generateQR(data);
        
        const modalHTML = `
            <div class="modal-overlay active" id="qrModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        <button class="modal-close" onclick="closeModal('qrModal')">✕</button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${qrUrl}" alt="QR Code" style="max-width: 100%; height: auto;">
                        <p class="mt-4 text-sm text-gray-600">
                            Escaneie este QR Code para acessar rapidamente
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

// ================================
// SISTEMA DE AVALIAÇÕES
// ================================
class RatingManager {
    constructor() {
        this.ratings = Utils.getStorage('ratings', {});
    }

    createRatingWidget(establishmentId, currentRating = 0) {
        const widget = document.createElement('div');
        widget.className = 'rating';
        widget.setAttribute('data-establishment-id', establishmentId);
        
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('span');
            star.className = `rating-star ${i <= currentRating ? 'active' : ''}`;
            star.textContent = '⭐';
            star.setAttribute('data-rating', i);
            star.addEventListener('click', () => this.setRating(establishmentId, i));
            widget.appendChild(star);
        }
        
        return widget;
    }

    async setRating(establishmentId, rating) {
        if (!authManager.isAuthenticated()) {
            toastManager.warning('Faça login para avaliar');
            return;
        }

        this.ratings[establishmentId] = {
            rating,
            userId: authManager.getUser().id,
            timestamp: new Date().toISOString()
        };

        Utils.setStorage('ratings', this.ratings);
        this.updateRatingDisplay(establishmentId, rating);
        
        toastManager.success('Avaliação enviada com sucesso!');
        
        // Ganhar pontos por avaliar
        await authManager.addPoints(5, 'Avaliação de estabelecimento');
    }

    updateRatingDisplay(establishmentId, rating) {
        const widget = document.querySelector(`[data-establishment-id="${establishmentId}"]`);
        if (widget) {
            const stars = widget.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                star.classList.toggle('active', index < rating);
            });
        }
    }

    getRating(establishmentId) {
        return this.ratings[establishmentId]?.rating || 0;
    }
}

// ================================
// SISTEMA DE COMENTÁRIOS
// ================================
class CommentManager {
    constructor() {
        this.comments = Utils.getStorage('comments', {});
    }

    async addComment(establishmentId, text) {
        if (!authManager.isAuthenticated()) {
            toastManager.warning('Faça login para comentar');
            return false;
        }

        const user = authManager.getUser();
        const commentId = Date.now().toString();
        
        if (!this.comments[establishmentId]) {
            this.comments[establishmentId] = [];
        }

        const comment = {
            id: commentId,
            text,
            userId: user.id,
            userName: user.name,
            userAvatar: user.avatar,
            timestamp: new Date().toISOString(),
            likes: 0
        };

        this.comments[establishmentId].unshift(comment);
        Utils.setStorage('comments', this.comments);
        
        // Ganhar pontos por comentar
        await authManager.addPoints(3, 'Comentário em estabelecimento');
        
        toastManager.success('Comentário adicionado!');
        return comment;
    }

    getComments(establishmentId) {
        return this.comments[establishmentId] || [];
    }

    createCommentWidget(establishmentId) {
        const widget = document.createElement('div');
        widget.className = 'comments-section';
        widget.innerHTML = `
            <div class="comment-form">
                <textarea class="comment-input" placeholder="Deixe seu comentário..." rows="3"></textarea>
                <button class="btn btn-primary btn-sm comment-submit" onclick="commentManager.submitComment('${establishmentId}')">
                    Comentar
                </button>
            </div>
            <div class="comments-list" id="comments-${establishmentId}">
                ${this.renderComments(establishmentId)}
            </div>
        `;
        
        return widget;
    }

    async submitComment(establishmentId) {
        const input = document.querySelector('.comment-input');
        const text = input.value.trim();
        
        if (!text) {
            toastManager.warning('Digite um comentário');
            return;
        }

        const comment = await this.addComment(establishmentId, text);
        if (comment) {
            input.value = '';
            this.refreshComments(establishmentId);
        }
    }

    renderComments(establishmentId) {
        const comments = this.getComments(establishmentId);
        
        if (comments.length === 0) {
            return '<p class="text-center text-gray-500">Nenhum comentário ainda. Seja o primeiro!</p>';
        }

        return comments.map(comment => `
            <div class="comment-item">
                <div class="comment-header">
                    <img src="${comment.userAvatar}" alt="${comment.userName}" class="avatar avatar-sm">
                    <div class="comment-meta">
                        <strong>${comment.userName}</strong>
                        <span class="comment-date">${this.formatDate(comment.timestamp)}</span>
                    </div>
                </div>
                <div class="comment-text">${comment.text}</div>
            </div>
        `).join('');
    }

    refreshComments(establishmentId) {
        const container = document.getElementById(`comments-${establishmentId}`);
        if (container) {
            container.innerHTML = this.renderComments(establishmentId);
        }
    }

    formatDate(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Agora mesmo';
        if (minutes < 60) return `${minutes}m atrás`;
        if (hours < 24) return `${hours}h atrás`;
        if (days < 7) return `${days}d atrás`;
        return date.toLocaleDateString('pt-BR');
    }
}

// ================================
// INICIALIZAÇÃO
// ================================
let toastManager, authManager, notificationManager, qrManager, ratingManager, commentManager;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar managers
    toastManager = new ToastManager();
    authManager = new AuthManager();
    notificationManager = new NotificationManager();
    qrManager = new QRCodeManager();
    ratingManager = new RatingManager();
    commentManager = new CommentManager();

    // Registrar Service Worker para PWA
    notificationManager.registerServiceWorker();

    // Configurar formulários
    setupForms();
    
    // Configurar navegação mobile
    setupMobileNavigation();
    
    // Configurar máscaras de input
    setupInputMasks();
    
    // Verificar autenticação
    checkAuthentication();
    
    // Configurar pull-to-refresh (mobile)
    setupPullToRefresh();

    console.log('🎯 Tem de Tudo - Sistema inicializado com sucesso!');
});

// ================================
// CONFIGURAÇÃO DE FORMULÁRIOS
// ================================
function setupForms() {
    // Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register Form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Contact Form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }

    // Search Forms
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', Utils.debounce(handleSearch, 300));
    });
}

async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const email = form.email.value;
    const password = form.password.value;
    const remember = form.remember?.checked || false;

    // Validações
    if (!Utils.validateEmail(email)) {
        toastManager.error('Por favor, digite um email válido');
        return;
    }

    if (!password) {
        toastManager.error('Por favor, digite sua senha');
        return;
    }

    // Loading state
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    try {
        const result = await authManager.login(email, password, remember);
        
        if (result.success) {
            // Redirect baseado no tipo de usuário
            const redirectTo = new URLSearchParams(window.location.search).get('redirect') || '/profile-client.html';
            window.location.href = redirectTo;
        }
    } finally {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Validações
    if (!data.name || data.name.length < 2) {
        toastManager.error('Nome deve ter pelo menos 2 caracteres');
        return;
    }

    if (!Utils.validateEmail(data.email)) {
        toastManager.error('Por favor, digite um email válido');
        return;
    }

    if (!Utils.validatePassword(data.password)) {
        toastManager.error('Senha deve ter pelo menos 8 caracteres, com letras maiúsculas, minúsculas e números');
        return;
    }

    if (data.password !== data.confirmPassword) {
        toastManager.error('Senhas não conferem');
        return;
    }

    if (!data.terms) {
        toastManager.error('Você deve aceitar os termos de uso');
        return;
    }

    // Loading state
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    try {
        const result = await authManager.register(data);
        
        if (result.success) {
            // Mostrar bônus de boas-vindas
            showWelcomeBonus();
            
            setTimeout(() => {
                window.location.href = '/profile-client.html';
            }, 2000);
        }
    } finally {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
    }
}

function showWelcomeBonus() {
    const bonusHTML = `
        <div class="modal-overlay active" id="welcomeModal">
            <div class="modal">
                <div class="modal-body text-center">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
                    <h2 style="color: var(--gold-color); margin-bottom: 1rem;">Parabéns!</h2>
                    <p>Você ganhou <strong>100 pontos</strong> de bônus por se cadastrar!</p>
                    <button class="btn btn-primary mt-4" onclick="closeModal('welcomeModal')">
                        Começar a usar!
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', bonusHTML);
}

async function handleContactForm(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    // Simular envio
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    toastManager.success('Mensagem enviada com sucesso! Entraremos em contato em breve.');
    form.reset();
    
    submitBtn.classList.remove('btn-loading');
    submitBtn.disabled = false;
}

function handleSearch(e) {
    const query = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.establishment-card, .reward-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const isVisible = text.includes(query);
        card.style.display = isVisible ? 'block' : 'none';
    });
}

// ================================
// NAVEGAÇÃO MOBILE
// ================================
function setupMobileNavigation() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileNav = document.getElementById('mobileNav');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }

    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', toggleMobileMenu);
    }

    // Fechar menu ao clicar no overlay
    if (mobileNav) {
        mobileNav.addEventListener('click', function(e) {
            if (e.target === mobileNav) {
                toggleMobileMenu();
            }
        });
    }
}

function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.classList.toggle('active');
        document.body.classList.toggle('mobile-menu-open');
    }
}

// ================================
// MÁSCARAS DE INPUT
// ================================
function setupInputMasks() {
    // Telefone
    const phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = Utils.formatPhone(e.target.value);
        });
    });

    // CPF
    const cpfInputs = document.querySelectorAll('input[name="cpf"]');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = Utils.formatCPF(e.target.value);
        });
    });

    // Password toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    });
}

// ================================
// VERIFICAÇÃO DE AUTENTICAÇÃO
// ================================
function checkAuthentication() {
    const protectedPages = ['/profile-client.html', '/profile-company.html'];
    const currentPage = window.location.pathname;
    
    if (protectedPages.includes(currentPage) && !authManager.isAuthenticated()) {
        toastManager.warning('Faça login para acessar esta página');
        window.location.href = `/login.html?redirect=${encodeURIComponent(currentPage)}`;
        return;
    }

    // Atualizar interface com dados do usuário
    if (authManager.isAuthenticated()) {
        updateUserInterface();
    }
}

function updateUserInterface() {
    const user = authManager.getUser();
    
    // Atualizar nome do usuário
    const userNameElements = document.querySelectorAll('#userName, .user-name');
    userNameElements.forEach(el => {
        if (el) el.textContent = user.name;
    });

    // Atualizar email
    const userEmailElements = document.querySelectorAll('#userEmail, .user-email');
    userEmailElements.forEach(el => {
        if (el) el.textContent = user.email;
    });

    // Atualizar pontos
    const pointsElements = document.querySelectorAll('#pointsBalance, .points-balance');
    pointsElements.forEach(el => {
        if (el) el.textContent = user.points || 0;
    });

    // Atualizar nível
    const levelElements = document.querySelectorAll('#userLevel, .user-level');
    const levelNames = {
        bronze: '🥉 Bronze',
        silver: '🥈 Prata', 
        gold: '🥇 Ouro',
        diamond: '💎 Diamante'
    };
    levelElements.forEach(el => {
        if (el) el.textContent = levelNames[user.level] || '🥉 Bronze';
    });
}

// ================================
// PULL TO REFRESH (MOBILE)
// ================================
function setupPullToRefresh() {
    if (!('ontouchstart' in window)) return; // Apenas em dispositivos touch

    let startY = 0;
    let currentY = 0;
    let pulling = false;
    
    const pullThreshold = 100;
    const maxPull = 150;
    
    const container = document.querySelector('.app-main') || document.body;
    
    container.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            pulling = true;
        }
    });
    
    container.addEventListener('touchmove', function(e) {
        if (!pulling) return;
        
        currentY = e.touches[0].clientY;
        const pullDistance = Math.min(currentY - startY, maxPull);
        
        if (pullDistance > 0) {
            e.preventDefault();
            container.style.transform = `translateY(${pullDistance * 0.5}px)`;
            container.style.transition = 'none';
        }
    });
    
    container.addEventListener('touchend', function() {
        if (!pulling) return;
        
        const pullDistance = currentY - startY;
        
        container.style.transition = 'transform 0.3s ease';
        container.style.transform = 'translateY(0)';
        
        if (pullDistance > pullThreshold) {
            refreshPage();
        }
        
        pulling = false;
        startY = 0;
        currentY = 0;
    });
}

function refreshPage() {
    toastManager.info('Atualizando conteúdo...');
    
    // Simular atualização
    setTimeout(() => {
        if (authManager.isAuthenticated()) {
            updateUserInterface();
        }
        toastManager.success('Conteúdo atualizado!');
    }, 1000);
}

// ================================
// FUNÇÕES GLOBAIS UTILITÁRIAS
// ================================
function showToast(message, type = 'info') {
    toastManager.show(message, type);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

function logout() {
    if (confirm('Deseja realmente sair?')) {
        authManager.logout();
    }
}

function generateQR(data, title) {
    qrManager.showQRModal(data, title);
}

async function requestNotificationPermission() {
    await notificationManager.requestPermission();
}

// ================================
// ESTABELECIMENTOS E FILTROS
// ================================
function filterEstablishments(category) {
    const cards = document.querySelectorAll('.establishment-card');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    // Atualizar botões
    filterButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
    
    // Filtrar cards
    cards.forEach(card => {
        const cardCategory = card.dataset.category;
        const isVisible = category === 'all' || cardCategory === category;
        
        card.style.display = isVisible ? 'block' : 'none';
        
        if (isVisible) {
            card.style.animation = 'fadeIn 0.3s ease-out';
        }
    });
}

// ================================
// FAVORITOS
// ================================
function toggleFavorite(establishmentId) {
    if (!authManager.isAuthenticated()) {
        toastManager.warning('Faça login para favoritar');
        return;
    }
    
    const favorites = Utils.getStorage(CONFIG.STORAGE_KEYS.FAVORITES, []);
    const index = favorites.indexOf(establishmentId);
    
    if (index > -1) {
        favorites.splice(index, 1);
        toastManager.info('Removido dos favoritos');
    } else {
        favorites.push(establishmentId);
        toastManager.success('Adicionado aos favoritos');
    }
    
    Utils.setStorage(CONFIG.STORAGE_KEYS.FAVORITES, favorites);
    updateFavoriteButtons();
}

function updateFavoriteButtons() {
    const favorites = Utils.getStorage(CONFIG.STORAGE_KEYS.FAVORITES, []);
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(btn => {
        const establishmentId = btn.dataset.establishmentId;
        const isFavorite = favorites.includes(establishmentId);
        
        btn.textContent = isFavorite ? '❤️' : '🤍';
        btn.classList.toggle('active', isFavorite);
    });
}

// ================================
// SISTEMA DE PONTOS
// ================================
async function simulatePurchase(establishmentId, amount) {
    if (!authManager.isAuthenticated()) {
        toastManager.warning('Faça login para acumular pontos');
        return;
    }
    
    const points = Math.floor(amount);
    const success = await authManager.addPoints(points, `Compra no estabelecimento`);
    
    if (success) {
        toastManager.success(`Você ganhou ${points} pontos!`);
        notificationManager.notifyPointsEarned(points, 'Compra realizada');
        updateUserInterface();
        
        // Verificar se subiu de nível
        const user = authManager.getUser();
        const previousLevel = Utils.getStorage('previous_level', 'bronze');
        
        if (user.level !== previousLevel) {
            Utils.setStorage('previous_level', user.level);
            notificationManager.notifyLevelUp(user.level);
        }
    }
}

// ================================
// EXPORTAR PARA ESCOPO GLOBAL
// ================================
window.toggleMobileMenu = toggleMobileMenu;
window.showToast = showToast;
window.closeModal = closeModal;
window.logout = logout;
window.generateQR = generateQR;
window.requestNotificationPermission = requestNotificationPermission;
window.filterEstablishments = filterEstablishments;
window.toggleFavorite = toggleFavorite;
window.simulatePurchase = simulatePurchase;

// Managers globais
window.toastManager = null;
window.authManager = null;
window.notificationManager = null;
window.qrManager = null;
window.ratingManager = null;
window.commentManager = null;