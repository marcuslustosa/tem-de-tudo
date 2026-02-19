/**
 * SISTEMA DE AUTENTICAÇÃO UNIFICADO - TEM DE TUDO
 * Gerenciamento completo e robusto de autenticação
 * 
 * @version 2.0.0
 * @author Tem de Tudo Team
 */

// ================================
// CONFIGURAÇÕES
// ================================
const AUTH_CONFIG = {
    STORAGE_KEYS: {
        TOKEN: 'token',
        USER: 'user',
        ADMIN_TOKEN: 'admin_token',
        ADMIN_USER: 'admin_user'
    },
    PAGES: {
        LOGIN: '/entrar.html',
        ADMIN_LOGIN: '/admin-login.html',
        CLIENTE_DASHBOARD: '/app-inicio.html',
        EMPRESA_DASHBOARD: '/dashboard-empresa.html',
        ADMIN_DASHBOARD: '/admin.html'
    }
};

// ================================
// CLASSE DE GERENCIAMENTO DE AUTENTICAÇÃO
// ================================
class AuthManager {
    constructor() {
        this.token = null;
        this.user = null;
        this.init();
    }

    /**
     * Inicializar autenticação
     */
    init() {
        this.loadStoredAuth();
    }

    /**
     * Carregar autenticação do storage
     */
    loadStoredAuth() {
        try {
            const token = localStorage.getItem(AUTH_CONFIG.STORAGE_KEYS.TOKEN);
            const userData = localStorage.getItem(AUTH_CONFIG.STORAGE_KEYS.USER);

            if (token && userData) {
                this.token = token;
                this.user = JSON.parse(userData);
                return true;
            }
            return false;
        } catch (error) {
            console.error('❌ Erro ao carregar autenticação:', error);
            this.clearAuth();
            return false;
        }
    }

    /**
     * Login de usuário
     * @param {Object} credentials - Email e senha
     * @param {boolean} remember - Lembrar login
     */
    async login(credentials, remember = false) {
        try {
            const response = await fetch(API_CONFIG.login, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(credentials)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.saveAuth(data.data.token, data.data.user);
                return {
                    success: true,
                    user: data.data.user,
                    redirect: data.data.redirect_to || this.getDefaultRedirect(data.data.user)
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Credenciais inválidas'
                };
            }
        } catch (error) {
            console.error('❌ Erro no login:', error);
            return {
                success: false,
                message: 'Erro de conexão. Verifique sua internet.'
            };
        }
    }

    /**
     * Login de admin
     * @param {Object} credentials - Email e senha
     */
    async adminLogin(credentials) {
        try {
            const baseURL = API_CONFIG.getBaseURL();
            const response = await fetch(`${baseURL}/api/admin/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(credentials)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const adminUser = data.data.admin || data.data.user;
                adminUser.perfil = 'admin';
                
                this.saveAuth(data.data.token, adminUser);
                
                return {
                    success: true,
                    admin: adminUser,
                    redirect: data.data.redirect_to || AUTH_CONFIG.PAGES.ADMIN_DASHBOARD
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Credenciais administrativas inválidas'
                };
            }
        } catch (error) {
            console.error('❌ Erro no login admin:', error);
            return {
                success: false,
                message: 'Erro de conexão. Verifique sua internet.'
            };
        }
    }

    /**
     * Registrar novo usuário
     * @param {Object} userData - Dados do usuário
     */
    async register(userData) {
        try {
            const response = await fetch(API_CONFIG.register, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Salvar autenticação se retornar token
                if (data.data && data.data.token) {
                    this.saveAuth(data.data.token, data.data.user);
                }

                return {
                    success: true,
                    user: data.data.user,
                    redirect: data.data.redirect_to || this.getDefaultRedirect(data.data.user)
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Erro ao criar conta',
                    errors: data.errors || {}
                };
            }
        } catch (error) {
            console.error('❌ Erro no cadastro:', error);
            return {
                success: false,
                message: 'Erro de conexão. Verifique sua internet.'
            };
        }
    }

    /**
     * Salvar autenticação
     * @param {string} token - Token JWT
     * @param {Object} user - Dados do usuário
     */
    saveAuth(token, user) {
        this.token = token;
        this.user = user;
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        
        // Definir userType baseado no perfil
        const userType = user.perfil || user.role || user.user_type || 'cliente';
        localStorage.setItem('userType', userType);
    }

    /**
     * Limpar autenticação
     */
    clearAuth() {
        this.token = null;
        this.user = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('userType');
    }

    /**
     * Logout do usuário
     */
    logout() {
        this.clearAuth();
        window.location.href = AUTH_CONFIG.PAGES.LOGIN;
    }

    /**
     * Logout do admin
     */
    adminLogout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('userType');
        window.location.href = AUTH_CONFIG.PAGES.ADMIN_LOGIN;
    }

    /**
     * Verificar se está autenticado
     */
    isAuthenticated() {
        return !!(this.token && this.user);
    }

    /**
     * Verificar se é admin autenticado
     */
    isAdminAuthenticated() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');
        const userType = localStorage.getItem('userType');
        return !!(token && user && userType === 'admin');
    }

    /**
     * Obter usuário atual
     */
    getCurrentUser() {
        return this.user;
    }

    /**
     * Obter token atual
     */
    getToken() {
        return this.token;
    }

    /**
     * Verificar tipo de usuário
     * @param {string} requiredType - Tipo requerido (cliente, empresa, admin)
     */
    checkUserType(requiredType) {
        if (!this.isAuthenticated()) {
            return false;
        }
        return this.user.user_type === requiredType;
    }

    /**
     * Obter redirect padrão baseado no tipo de usuário
     * @param {Object} user - Dados do usuário
     */
    getDefaultRedirect(user) {
        const redirectMap = {
            'cliente': AUTH_CONFIG.PAGES.CLIENTE_DASHBOARD,
            'empresa': AUTH_CONFIG.PAGES.EMPRESA_DASHBOARD,
            'admin': AUTH_CONFIG.PAGES.ADMIN_DASHBOARD
        };
        return redirectMap[user.user_type] || AUTH_CONFIG.PAGES.LOGIN;
    }

    /**
     * Proteger página - redireciona se não autenticado
     * @param {string} requiredType - Tipo de usuário requerido (opcional)
     */
    requireAuth(requiredType = null) {
        if (!this.isAuthenticated()) {
            window.location.href = AUTH_CONFIG.PAGES.LOGIN;
            return false;
        }

        if (requiredType && !this.checkUserType(requiredType)) {
            window.location.href = this.getDefaultRedirect(this.user);
            return false;
        }

        return true;
    }

    /**
     * Proteger página admin
     */
    requireAdminAuth() {
        if (!this.isAdminAuthenticated()) {
            window.location.href = AUTH_CONFIG.PAGES.ADMIN_LOGIN;
            return false;
        }
        return true;
    }
}

// ================================
// INSTÂNCIA GLOBAL
// ================================
const authManager = new AuthManager();

// Expor globalmente
window.authManager = authManager;

// Compatibilidade com código antigo
window.logout = () => authManager.logout();
window.adminLogout = () => authManager.adminLogout();

console.log('🔐 AuthManager carregado');
