/**
 * SISTEMA DE AUTENTICAÇÃO - TEMDETUDO
 * Gerenciamento completo de login, logout e sessões
 */

// ================================
// CONFIGURAÇÕES DE AUTENTICAÇÃO
// ================================
const AuthConfig = {
    API_BASE: window.location.origin + '/api',
    STORAGE_KEYS: {
        TOKEN: 'tem_de_tudo_token',
        USER: 'tem_de_tudo_user',
        ADMIN_TOKEN: 'admin_token',
        ADMIN_USER: 'admin_user',
        REMEMBER_ME: 'remember_me_expires'
    },
    TOKEN_REFRESH_INTERVAL: 15 * 60 * 1000, // 15 minutos
    SESSION_CHECK_INTERVAL: 5 * 60 * 1000,  // 5 minutos
    ROUTES: {
        LOGIN: '/auth/login',
        ADMIN_LOGIN: '/admin/login',
        REFRESH: '/auth/refresh',
        LOGOUT: '/auth/logout',
        VERIFY: '/auth/verify'
    }
};

// ================================
// CLASSE DE AUTENTICAÇÃO
// ================================
class AuthManager {
    constructor() {
        this.token = null;
        this.user = null;
        this.refreshTimer = null;
        this.sessionTimer = null;
        this.isRefreshing = false;
        
        this.init();
    }
    
    // Inicializar sistema de autenticação
    init() {
        this.loadStoredAuth();
        this.setupTokenRefresh();
        this.setupSessionCheck();
        this.setupEventListeners();
        
        // Verificar se a sessão ainda é válida
        this.verifySession();
    }
    
    // Carregar autenticação armazenada
    loadStoredAuth() {
        const token = localStorage.getItem(AuthConfig.STORAGE_KEYS.TOKEN) || 
                     sessionStorage.getItem(AuthConfig.STORAGE_KEYS.TOKEN);
        const userData = localStorage.getItem(AuthConfig.STORAGE_KEYS.USER) || 
                        sessionStorage.getItem(AuthConfig.STORAGE_KEYS.USER);
        
        if (token && userData) {
            try {
                this.token = token;
                this.user = JSON.parse(userData);
                this.setupAuthHeaders();
                console.log('✅ Sessão restaurada:', this.user.name);
            } catch (error) {
                console.error('❌ Erro ao restaurar sessão:', error);
                this.clearAuth();
            }
        }
    }
    
    // Configurar headers de autenticação
    setupAuthHeaders() {
        // Interceptar todas as requisições para adicionar token
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            if (this.token && !url.includes('/auth/login')) {
                options.headers = {
                    ...options.headers,
                    'Authorization': `Bearer ${this.token}`,
                    'X-Requested-With': 'XMLHttpRequest'
                };
            }
            return originalFetch(url, options);
        };
    }
    
    // Login de usuário comum
    async login(credentials, remember = false) {
        try {
            const response = await this.makeAuthRequest(AuthConfig.ROUTES.LOGIN, credentials);
            
            if (response.success) {
                await this.handleSuccessfulAuth(response, remember);
                return { success: true, user: this.user };
            } else {
                return { success: false, message: response.message || 'Credenciais inválidas' };
            }
        } catch (error) {
            console.error('❌ Erro no login:', error);
            return { success: false, message: 'Erro de conexão. Tente novamente.' };
        }
    }
    
    // Login de admin
    async adminLogin(credentials, remember = false) {
        try {
            const response = await this.makeAuthRequest(AuthConfig.ROUTES.ADMIN_LOGIN, credentials);
            
            if (response.success) {
                // Armazenar dados de admin separadamente
                const storage = remember ? localStorage : sessionStorage;
                storage.setItem(AuthConfig.STORAGE_KEYS.ADMIN_TOKEN, response.token);
                storage.setItem(AuthConfig.STORAGE_KEYS.ADMIN_USER, JSON.stringify(response.admin));
                
                return { success: true, admin: response.admin };
            } else {
                return { success: false, message: response.message || 'Credenciais administrativas inválidas' };
            }
        } catch (error) {
            console.error('❌ Erro no login admin:', error);
            return { success: false, message: 'Erro de conexão. Tente novamente.' };
        }
    }
    
    // Processar autenticação bem-sucedida
    async handleSuccessfulAuth(response, remember = false) {
        this.token = response.token;
        this.user = response.user;
        
        // Determinar onde armazenar (localStorage vs sessionStorage)
        const storage = remember ? localStorage : sessionStorage;
        
        storage.setItem(AuthConfig.STORAGE_KEYS.TOKEN, this.token);
        storage.setItem(AuthConfig.STORAGE_KEYS.USER, JSON.stringify(this.user));
        
        if (remember) {
            const expiresAt = Date.now() + (30 * 24 * 60 * 60 * 1000); // 30 dias
            localStorage.setItem(AuthConfig.STORAGE_KEYS.REMEMBER_ME, expiresAt.toString());
        }
        
        this.setupAuthHeaders();
        this.setupTokenRefresh();
        this.dispatchAuthEvent('login', this.user);
        
        console.log('✅ Login realizado:', this.user.name);
    }
    
    // Fazer requisição de autenticação
    async makeAuthRequest(endpoint, data) {
        const response = await fetch(`${AuthConfig.API_BASE}${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        return await response.json();
    }
    
    // Logout
    async logout(showMessage = true) {
        try {
            // Tentar invalidar token no servidor
            if (this.token) {
                await fetch(`${AuthConfig.API_BASE}${AuthConfig.ROUTES.LOGOUT}`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json'
                    }
                });
            }
        } catch (error) {
            console.warn('⚠️ Erro ao invalidar token no servidor:', error);
        } finally {
            this.clearAuth();
            this.dispatchAuthEvent('logout');
            
            if (showMessage) {
                this.showMessage('Logout realizado com sucesso!', 'success');
            }
            
            // Redirecionar para página de login após um delay
            setTimeout(() => {
                window.location.href = '/login.html';
            }, 1500);
        }
    }
    
    // Limpar dados de autenticação
    clearAuth() {
        this.token = null;
        this.user = null;
        
        // Limpar todos os storages
        [localStorage, sessionStorage].forEach(storage => {
            Object.values(AuthConfig.STORAGE_KEYS).forEach(key => {
                storage.removeItem(key);
            });
        });
        
        // Limpar timers
        if (this.refreshTimer) clearInterval(this.refreshTimer);
        if (this.sessionTimer) clearInterval(this.sessionTimer);
        
        console.log('🔄 Autenticação limpa');
    }
    
    // Verificar se usuário está logado
    isAuthenticated() {
        return !!(this.token && this.user);
    }
    
    // Verificar se é admin
    isAdmin() {
        return this.user && (this.user.role === 'admin' || this.user.role === 'super_admin');
    }
    
    // Obter dados do usuário
    getUser() {
        return this.user;
    }
    
    // Obter token
    getToken() {
        return this.token;
    }
    
    // Verificar sessão no servidor
    async verifySession() {
        if (!this.token) return false;
        
        try {
            const response = await fetch(`${AuthConfig.API_BASE}${AuthConfig.ROUTES.VERIFY}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            
            if (!response.ok) {
                this.clearAuth();
                return false;
            }
            
            const result = await response.json();
            if (result.valid) {
                // Atualizar dados do usuário se necessário
                if (result.user) {
                    this.user = result.user;
                    const storage = localStorage.getItem(AuthConfig.STORAGE_KEYS.TOKEN) ? localStorage : sessionStorage;
                    storage.setItem(AuthConfig.STORAGE_KEYS.USER, JSON.stringify(this.user));
                }
                return true;
            } else {
                this.clearAuth();
                return false;
            }
            
        } catch (error) {
            console.error('❌ Erro na verificação de sessão:', error);
            // Não limpar auth em caso de erro de rede
            return false;
        }
    }
    
    // Renovar token
    async refreshToken() {
        if (this.isRefreshing || !this.token) return;
        
        this.isRefreshing = true;
        
        try {
            const response = await fetch(`${AuthConfig.API_BASE}${AuthConfig.ROUTES.REFRESH}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.token) {
                    this.token = result.token;
                    
                    // Atualizar storage
                    const storage = localStorage.getItem(AuthConfig.STORAGE_KEYS.TOKEN) ? localStorage : sessionStorage;
                    storage.setItem(AuthConfig.STORAGE_KEYS.TOKEN, this.token);
                    
                    console.log('🔄 Token renovado com sucesso');
                }
            } else {
                console.warn('⚠️ Falha na renovação do token');
                this.clearAuth();
            }
            
        } catch (error) {
            console.error('❌ Erro na renovação do token:', error);
        } finally {
            this.isRefreshing = false;
        }
    }
    
    // Configurar renovação automática de token
    setupTokenRefresh() {
        if (this.refreshTimer) clearInterval(this.refreshTimer);
        
        if (this.token) {
            this.refreshTimer = setInterval(() => {
                this.refreshToken();
            }, AuthConfig.TOKEN_REFRESH_INTERVAL);
        }
    }
    
    // Configurar verificação de sessão
    setupSessionCheck() {
        if (this.sessionTimer) clearInterval(this.sessionTimer);
        
        if (this.token) {
            this.sessionTimer = setInterval(() => {
                this.verifySession();
            }, AuthConfig.SESSION_CHECK_INTERVAL);
        }
    }
    
    // Configurar event listeners
    setupEventListeners() {
        // Verificar when tab becomes active
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.token) {
                this.verifySession();
            }
        });
        
        // Verificar remember me expiration
        setInterval(() => {
            const rememberExpires = localStorage.getItem(AuthConfig.STORAGE_KEYS.REMEMBER_ME);
            if (rememberExpires && Date.now() > parseInt(rememberExpires)) {
                this.clearAuth();
                this.showMessage('Sessão expirada. Faça login novamente.', 'warning');
            }
        }, 60000); // Verificar a cada minuto
    }
    
    // Disparar eventos de autenticação
    dispatchAuthEvent(type, data = null) {
        const event = new CustomEvent('authStateChanged', {
            detail: { type, data, user: this.user, isAuthenticated: this.isAuthenticated() }
        });
        document.dispatchEvent(event);
    }
    
    // Mostrar mensagens
    showMessage(message, type = 'info') {
        // Tentar usar o sistema de toast existente
        if (window.showToast) {
            window.showToast(message, type);
        } else if (window.Toast && window.Toast.show) {
            window.Toast.show(message, type);
        } else {
            // Fallback para alert
            console.log(`${type.toUpperCase()}: ${message}`);
            if (type === 'error' || type === 'warning') {
                alert(message);
            }
        }
    }
    
    // Redirecionar baseado no perfil do usuário
    redirectToDashboard() {
        if (!this.isAuthenticated()) {
            window.location.href = '/login.html';
            return;
        }

        const user = this.getUser();

        switch (user.perfil) {
            case 'admin':
                window.location.href = '/admin.html';
                break;
            case 'empresa':
                window.location.href = '/dashboard-estabelecimento.html';
                break;
            case 'cliente':
                window.location.href = '/dashboard-cliente.html';
                break;
            default:
                window.location.href = '/dashboard-cliente.html';
                break;
        }
    }
}

// ================================
// MIDDLEWARE DE AUTENTICAÇÃO
// ================================
const AuthMiddleware = {
    // Proteger páginas que requerem autenticação
    requireAuth() {
        if (!window.Auth.isAuthenticated()) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    },
    
    // Proteger páginas de admin
    requireAdmin() {
        if (!window.Auth.isAuthenticated()) {
            window.location.href = '/admin-login.html';
            return false;
        }
        
        if (!window.Auth.isAdmin()) {
            alert('Acesso negado. Você não tem permissões administrativas.');
            window.location.href = '/login.html';
            return false;
        }
        
        return true;
    },
    
    // Redirecionar usuários já logados
    redirectIfAuthenticated() {
        if (window.Auth.isAuthenticated()) {
            window.Auth.redirectToDashboard();
            return true;
        }
        return false;
    }
};

// ================================
// INICIALIZAÇÃO GLOBAL
// ================================
// Criar instância global do Auth Manager
window.Auth = new AuthManager();
window.AuthMiddleware = AuthMiddleware;

// Event listeners para formulários de login - apenas se não houver onsubmit inline
document.addEventListener('DOMContentLoaded', function() {
    // Formulário de login comum - apenas adicionar se não tiver onsubmit inline
    const loginForm = document.getElementById('loginForm');
    if (loginForm && !loginForm.hasAttribute('onsubmit')) {
        loginForm.addEventListener('submit', handleUserLogin);
    }

    // Formulário de login admin - apenas adicionar se não tiver onsubmit inline
    const adminLoginForm = document.getElementById('adminLoginForm');
    if (adminLoginForm && !adminLoginForm.hasAttribute('onsubmit')) {
        adminLoginForm.addEventListener('submit', handleAdminLogin);
    }

    // Botões de logout
    document.querySelectorAll('[data-action="logout"]').forEach(button => {
        button.addEventListener('click', () => window.Auth.logout());
    });
});

// ================================
// HANDLERS DE LOGIN
// ================================
async function handleUserLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const credentials = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    const remember = formData.get('remember') === 'on';
    
    const loginBtn = event.target.querySelector('button[type="submit"]');
    const originalText = loginBtn.textContent;
    
    // Loading state
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
    
    try {
        const result = await window.Auth.login(credentials, remember);
        
        if (result.success) {
            window.Auth.showMessage('Login realizado com sucesso!', 'success');
            
            setTimeout(() => {
                window.Auth.redirectToDashboard();
            }, 1500);
        } else {
            window.Auth.showMessage(result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erro no login:', error);
        window.Auth.showMessage('Erro inesperado. Tente novamente.', 'error');
    } finally {
        loginBtn.disabled = false;
        loginBtn.textContent = originalText;
    }
}

async function handleAdminLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const credentials = {
        email: formData.get('email'),
        password: formData.get('password'),
        security_code: formData.get('security_code')
    };
    const remember = formData.get('remember') === 'on';
    
    const loginBtn = event.target.querySelector('button[type="submit"]');
    const originalText = loginBtn.textContent;
    
    // Loading state
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    
    try {
        const result = await window.Auth.adminLogin(credentials, remember);
        
        if (result.success) {
            window.Auth.showMessage('Acesso administrativo autorizado!', 'success');
            
            setTimeout(() => {
                window.location.href = '/admin.html';
            }, 1500);
        } else {
            window.Auth.showMessage(result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erro no login admin:', error);
        window.Auth.showMessage('Erro inesperado. Tente novamente.', 'error');
    } finally {
        loginBtn.disabled = false;
        loginBtn.textContent = originalText;
    }
}

// ================================
// UTILITÁRIOS DE PÁGINA
// ================================

// Função para atualizar UI baseada no estado de autenticação
function updateAuthUI() {
    const isAuth = window.Auth.isAuthenticated();
    const user = window.Auth.getUser();
    
    // Elementos que mostram quando logado
    document.querySelectorAll('[data-show-when="authenticated"]').forEach(el => {
        el.style.display = isAuth ? 'block' : 'none';
    });
    
    // Elementos que mostram quando não logado
    document.querySelectorAll('[data-show-when="unauthenticated"]').forEach(el => {
        el.style.display = !isAuth ? 'block' : 'none';
    });
    
    // Mostrar dados do usuário
    if (isAuth && user) {
        document.querySelectorAll('[data-user="name"]').forEach(el => {
            el.textContent = user.name;
        });
        
        document.querySelectorAll('[data-user="email"]').forEach(el => {
            el.textContent = user.email;
        });
        
        document.querySelectorAll('[data-user="perfil"]').forEach(el => {
            el.textContent = user.perfil;
        });
    }
}

// Escutar mudanças de estado de autenticação
document.addEventListener('authStateChanged', updateAuthUI);

// Atualizar UI inicial
document.addEventListener('DOMContentLoaded', updateAuthUI);

console.log('🔐 Sistema de Autenticação TemDeTudo inicializado');