/**
 * AUTH GUARD - TEM DE TUDO
 * Proteção automática de rotas
 * 
 * @version 3.0.0
 * @author Tem de Tudo Team
 * 
 * USO:
 * <script src="/js/auth-guard.js" data-require-auth="cliente"></script>
 * 
 * Tipos suportados: cliente, empresa, admin
 */

// MIGRAÇÃO AUTOMÁTICA DE TOKENS
(function migrarTokens() {
    // Migrar token antigo para novo formato
    const oldToken = localStorage.getItem('tem_de_tudo_token');
    const oldUser = localStorage.getItem('tem_de_tudo_user');
    const newToken = localStorage.getItem('token');
    const newUser = localStorage.getItem('user');
    
    if (oldToken && !newToken) {
        console.log('🔄 Migrando token antigo...');
        localStorage.setItem('token', oldToken);
    }
    
    if (oldUser && !newUser) {
        console.log('🔄 Migrando dados de usuário antigos...');
        localStorage.setItem('user', oldUser);
    }
})();

(function() {
    'use strict';
    
    // Obter configuração do script
    const currentScript = document.currentScript;
    const requireAuth = currentScript ? currentScript.getAttribute('data-require-auth') : null;
    const requireAdmin = currentScript ? currentScript.hasAttribute('data-require-admin') : false;
    
    /**
     * Verificar autenticação
     */
    function checkAuthInternal() {
        // SISTEMA UNIFICADO - usar sempre 'token' e 'user'
        const token = localStorage.getItem('token') || localStorage.getItem('tem_de_tudo_token');
        const userData = localStorage.getItem('user') || localStorage.getItem('tem_de_tudo_user');
        
        if (!token || !userData) {
            console.warn('🔒 Acesso negado: Usuário não autenticado');
            window.location.href = '/entrar.html';
            return false;
        }
        
        // Verificar autenticação admin
        if (requireAdmin) {
            try {
                const user = JSON.parse(userData);
                if (user.perfil !== 'admin' && user.role !== 'admin') {
                    console.warn('🔒 Acesso negado: Admin requerido');
                    window.location.href = '/entrar.html';
                    return false;
                }
            } catch (error) {
                console.error('❌ Erro ao validar admin:', error);
                localStorage.clear();
                window.location.href = '/entrar.html';
                return false;
            }
            return true;
        }
        
        // Se requer tipo específico de usuário
        if (requireAuth) {
            try {
                const user = JSON.parse(userData);
                const userType = user.user_type || user.perfil || 'cliente';
                
                if (userType !== requireAuth) {
                    console.warn(`🔒 Acesso negado: Requer perfil ${requireAuth}, mas usuário é ${userType}`);
                    
                    // Redirecionar para dashboard correto
                    const redirectMap = {
                        'cliente': '/app-inicio.html',
                        'empresa': '/dashboard-empresa.html',
                        'admin': '/admin-dashboard.html'
                    };
                    
                    window.location.href = redirectMap[userType] || '/entrar.html';
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
    
    // DESATIVAR verificação periódica que pode causar loop
    // function setupTokenCheck() {
    //     // Verificação desativada temporariamente
    // }
    
    // Executar verificação assim que possível
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            checkAuthInternal();
        });
    } else {
        checkAuthInternal();
    }
    
    console.log('🛡️ Auth Guard ativo' + (requireAuth ? ` (requer: ${requireAuth})` : '') + (requireAdmin ? ' (admin)' : ''));
})();

/**
 * FUNÇÕES GLOBAIS PARA USO NAS PÁGINAS
 */

/**
 * Verifica se está autenticado (UNIFICADO)
 * @returns {boolean}
 */
function checkAuth() {
    const token = localStorage.getItem('token') || localStorage.getItem('tem_de_tudo_token');
    if (!token) {
        window.location.href = '/entrar.html';
        return false;
    }
    return true;
}

/**
 * Logout universal (LIMPA TUDO)
 */
function logout() {
    // Limpar todas as possíveis chaves de autenticação
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('tem_de_tudo_token');
    localStorage.removeItem('tem_de_tudo_user');
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    
    // Redirecionar
    window.location.href = '/entrar.html';
}

/**
 * Obter dados do usuário atual (UNIFICADO)
 * @returns {Object|null}
 */
function getCurrentUser() {
    const userStr = localStorage.getItem('user') || localStorage.getItem('tem_de_tudo_user');
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
    
    const userProfile = user.perfil || user.role || user.user_type || 'cliente';
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
 * Obter token de autenticação (UNIFICADO)
 * @returns {string|null}
 */
function getAuthToken() {
    return localStorage.getItem('token') || localStorage.getItem('tem_de_tudo_token');
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

console.log('✅ Auth Guard UNIFICADO carregado');
