/**
 * AUTH GUARD - TEM DE TUDO
 * Proteção automática de rotas
 * 
 * @version 2.0.0
 * @author Tem de Tudo Team
 * 
 * USO:
 * <script src="/js/auth-guard.js" data-require-auth="cliente"></script>
 * 
 * Tipos suportados: cliente, empresa, admin
 */

(function() {
    'use strict';
    
    // Obter configuração do script
    const currentScript = document.currentScript;
    const requireAuth = currentScript ? currentScript.getAttribute('data-require-auth') : null;
    const requireAdmin = currentScript ? currentScript.hasAttribute('data-require-admin') : false;
    
    /**
     * Verificar autenticação
     */
    function checkAuth() {
        // Verificar autenticação admin
        if (requireAdmin) {
            const adminToken = localStorage.getItem('admin_token');
            const adminUser = localStorage.getItem('admin_user');
            
            if (!adminToken || !adminUser) {
                console.warn('🔒 Acesso negado: Admin não autenticado');
                window.location.href = '/admin-login.html';
                return false;
            }
            
            return true;
        }
        
        // Verificar autenticação regular
        const token = localStorage.getItem('token');
        const userData = localStorage.getItem('user');
        
        if (!token || !userData) {
            console.warn('🔒 Acesso negado: Usuário não autenticado');
            window.location.href = '/entrar.html';
            return false;
        }
        
        // Se requer tipo específico de usuário
        if (requireAuth) {
            try {
                const user = JSON.parse(userData);
                
                if (user.user_type !== requireAuth) {
                    console.warn(`🔒 Acesso negado: Requer perfil ${requireAuth}, mas usuário é ${user.user_type}`);
                    
                    // Redirecionar para dashboard correto
                    const redirectMap = {
                        'cliente': '/app-inicio.html',
                        'empresa': '/dashboard-empresa.html',
                        'admin': '/admin.html'
                    };
                    
                    window.location.href = redirectMap[user.user_type] || '/entrar.html';
                    return false;
                }
            } catch (error) {
                console.error('❌ Erro ao validar usuário:', error);
                localStorage.clear();
                window.location.href = '/entrar.html';
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verificar token expirado periodicamente
     */
    function setupTokenCheck() {
        // Verificar a cada 5 minutos
        setInterval(async () => {
            const token = localStorage.getItem('token');
            
            if (!token) {
                console.warn('🔒 Token não encontrado');
                window.location.href = '/entrar.html';
                return;
            }
            
            try {
                // Fazer uma requisição leve para verificar se token é válido
                const baseURL = API_CONFIG ? API_CONFIG.getBaseURL() : '';
                const response = await fetch(`${baseURL}/api/user`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.status === 401) {
                    console.warn('🔒 Token expirado');
                    localStorage.clear();
                    window.location.href = '/entrar.html';
                }
            } catch (error) {
                // Ignorar erros de rede
                console.debug('Erro ao verificar token:', error);
            }
        }, 5 * 60 * 1000); // 5 minutos
    }
    
    // Executar verificação assim que possível
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (checkAuth()) {
                setupTokenCheck();
            }
        });
    } else {
        if (checkAuth()) {
            setupTokenCheck();
        }
    }
    
    console.log('🛡️ Auth Guard ativo' + (requireAuth ? ` (requer: ${requireAuth})` : '') + (requireAdmin ? ' (admin)' : ''));
})();

/**
 * Verifica apenas se está autenticado (sem verificar perfil)
 * Use apenas em páginas públicas ou comuns a todos
 * @returns {boolean}
 */
function checkAuth() {
    const token = localStorage.getItem('tem_de_tudo_token');
    if (!token) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}

/**
 * Logout universal
 */
function logout() {
    localStorage.removeItem('tem_de_tudo_token');
    localStorage.removeItem('tem_de_tudo_user');
    window.location.href = '/login.html';
}

/**
 * Obter dados do usuário atual
 * @returns {Object|null}
 */
function getCurrentUser() {
    const userStr = localStorage.getItem('tem_de_tudo_user');
    if (userStr) {
        try {
            return JSON.parse(userStr);
        } catch (error) {
            console.error('Erro ao parsear dados do usuário:', error);
            return null;
        }
    }
    return null;
}

/**
 * Verificar se usuário tem perfil específico
 * @param {string} profile - Perfil para verificar
 * @returns {boolean}
 */
function hasProfile(profile) {
    const user = getCurrentUser();
    if (!user) return false;
    
    const userProfile = user.perfil || user.role || 'cliente';
    return userProfile === profile;
}

/**
 * Verificar se é admin
 * @returns {boolean}
 */
function isAdmin() {
    return hasProfile('admin');
}

/**
 * Verificar se é empresa
 * @returns {boolean}
 */
function isEmpresa() {
    return hasProfile('empresa');
}

/**
 * Verificar se é cliente
 * @returns {boolean}
 */
function isCliente() {
    return hasProfile('cliente');
}

/**
 * Obter token de autenticação
 * @returns {string|null}
 */
function getAuthToken() {
    return localStorage.getItem('tem_de_tudo_token');
}

/**
 * Criar headers padrão para requisições autenticadas
 * @returns {Object}
 */
function getAuthHeaders() {
    const token = getAuthToken();
    return {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
}

// ================================================
// EXEMPLO DE USO NAS PÁGINAS
// ================================================
//
// PÁGINA DE CLIENTE:
// if (!checkAuthAndProfile('cliente')) return;
//
// PÁGINA DE EMPRESA:
// if (!checkAuthAndProfile('empresa')) return;
//
// PÁGINA DE ADMIN:
// if (!checkAuthAndProfile('admin')) return;
//
// ================================================

console.log('✅ Auth Guard carregado');
