/**
 * AUTH GUARD - TEM DE TUDO
 * Proteção automática de rotas - VERSÃO SIMPLIFICADA
 * 
 * @version 4.0.0 - SEM REDIRECTS AUTOMÁTICOS
 * 
 * USO:
 * <script src="/js/auth-guard.js" data-require-auth="cliente"></script>
 */

// MIGRAÇÃO AUTOMÁTICA DE TOKENS (SEM REDIRECT)
(function migrarTokens() {
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

// DESATIVAR VERIFICAÇÕES AUTOMÁTICAS POR ENQUANTO
// (function() {
//     'use strict';
//     
//     const currentScript = document.currentScript;
//     const requireAuth = currentScript ? currentScript.getAttribute('data-require-auth') : null;
//     const requireAdmin = currentScript ? currentScript.hasAttribute('data-require-admin') : false;
//     
//     // VERIFICAÇÕES AUTOMÁTICAS DESATIVADAS PARA EVITAR LOOP
//     console.log('🛡️ Auth Guard DESATIVADO temporariamente (versão 4.0.0)');
// })();

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
