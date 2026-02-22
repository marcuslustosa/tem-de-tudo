/**
 * AUTH GUARD - TEM DE TUDO
 * Proteção automática de rotas - ATIVADO
 * 
 * @version 5.0.0 - TOKENS PADRONIZADOS: 'token' e 'user'
 * 
 * USO:
 * <script src="/js/auth-guard.js" data-require-auth="cliente"></script>
 */

// MIGRAÇÃO AUTOMÁTICA DE TOKENS ANTIGOS
(function migrarTokens() {
    const oldToken = localStorage.getItem('token');
    const oldUser = localStorage.getItem('user');
    const adminToken = localStorage.getItem('admin_token');
    const adminUser = localStorage.getItem('admin_user');
    
    // Migrar tokens antigos para novos
    if (oldToken) {
        console.log('🔄 Migrando token antigo...');
        localStorage.setItem('token', oldToken);
        localStorage.removeItem('token');
    }
    
    if (oldUser) {
        console.log('🔄 Migrando dados de usuário antigos...');
        localStorage.setItem('user', oldUser);
        localStorage.removeItem('user');
    }
    
    // Admin mantém separado
    if (adminToken && !localStorage.getItem('token')) {
        localStorage.setItem('token', adminToken);
    }
    
    if (adminUser && !localStorage.getItem('user')) {
        localStorage.setItem('user', adminUser);
    }
})();

// ATIVAR VERIFICAÇÕES AUTOMÁTICAS
(function() {
    'use strict';
    
    const currentScript = document.currentScript;
    const requireAuth = currentScript ? currentScript.getAttribute('data-require-auth') : null;
    const requireAdmin = currentScript ? currentScript.hasAttribute('data-require-admin') : false;
    
    // MODO DESENVOLVIMENTO - permite acesso livre em localhost
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' ||
                         window.location.port === '8080';
    
    if (isDevelopment) {
        console.log('🔧 MODO DESENVOLVIMENTO - Auth Guard desabilitado');
        // Criar dados de usuário fake para desenvolvimento
        if (!localStorage.getItem('user')) {
            localStorage.setItem('user', JSON.stringify({
                id: 1, name: 'Usuário Teste', email: 'teste@teste.com',
                perfil: 'cliente', pontos: 2847
            }));
            localStorage.setItem('token', 'fake-dev-token-123');
        }
        return;
    }
    
    // Verificar autenticação se necessário (apenas em produção)
    if (requireAuth || requireAdmin) {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');
        
        if (!token || !user) {
            console.log('🚫 Não autenticado - redirecionando...');
            window.location.href = requireAdmin ? '/admin-login.html' : '/entrar.html';
            return;
        }
        
        // Verificar perfil se especificado
        if (requireAuth && requireAuth !== 'any') {
            try {
                const userData = JSON.parse(user);
                const userProfile = userData.perfil || userData.role || userData.user_type || 'cliente';
                
                if (userProfile !== requireAuth) {
                    console.log(`🚫 Acesso negado - perfil requerido: ${requireAuth}, atual: ${userProfile}`);
                    window.location.href = '/entrar.html';
                    return;
                }
            } catch (error) {
                console.error('Erro ao verificar perfil:', error);
                window.location.href = '/entrar.html';
                return;
            }
        }
        
        console.log('✅ Auth Guard: Acesso autorizado');
    }
})();

/**
 * FUNÇÕES GLOBAIS PARA USO NAS PÁGINAS
 */

/**
 * Verifica se está autenticado (UNIFICADO)
 * @returns {boolean}
 */
function checkAuth() {
    const token = localStorage.getItem('token');
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
    // Limpar tokens e dados
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('userType');
    
    // Limpar possíveis tokens antigos
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    
    // Redirecionar
    window.location.href = '/index.html';
}

/**
 * Obter dados do usuário atual (UNIFICADO)
 * @returns {Object|null}
 */
function getCurrentUser() {
    const userStr = localStorage.getItem('user');
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
    return localStorage.getItem('token');
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
