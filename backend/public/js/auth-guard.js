// ================================================
// AUTH-GUARD.JS - Proteção de Rotas por Perfil
// ================================================
// Este arquivo contém funções para proteger páginas
// baseadas no perfil do usuário autenticado
// ================================================

/**
 * Verifica se o usuário está autenticado E tem o perfil correto
 * @param {string} requiredProfile - Perfil necessário ('cliente', 'empresa', 'admin')
 * @returns {boolean} - true se autenticado e perfil correto, false caso contrário
 */
function checkAuthAndProfile(requiredProfile) {
    const token = localStorage.getItem('tem_de_tudo_token');
    const userStr = localStorage.getItem('tem_de_tudo_user');
    
    // Não tem token = não está logado
    if (!token) {
        console.warn('Usuário não autenticado. Redirecionando para login...');
        window.location.href = '/login.html';
        return false;
    }
    
    // Verificar perfil do usuário
    if (userStr) {
        try {
            const user = JSON.parse(userStr);
            const userProfile = user.perfil || user.role || 'cliente'; // fallback para compatibilidade
            
            console.log('Verificação de perfil:', {
                required: requiredProfile,
                current: userProfile,
                user: user.name
            });
            
            // Perfil incorreto = redirecionar para dashboard correto
            if (userProfile !== requiredProfile) {
                console.warn(`Perfil incorreto. Esperado: ${requiredProfile}, Atual: ${userProfile}`);
                
                // Redirecionar para o dashboard correto do usuário
                const redirectMap = {
                    'cliente': '/dashboard-cliente.html',
                    'empresa': '/dashboard-estabelecimento.html',
                    'admin': '/admin.html'
                };
                
                const correctDashboard = redirectMap[userProfile] || '/login.html';
                
                // Mostrar mensagem se disponível
                if (typeof showMessage === 'function') {
                    showMessage('Você não tem permissão para acessar esta página', 'warning');
                }
                
                setTimeout(() => {
                    window.location.href = correctDashboard;
                }, 1000);
                
                return false;
            }
            
            // Tudo certo!
            return true;
            
        } catch (error) {
            console.error('Erro ao verificar perfil do usuário:', error);
            // Em caso de erro, fazer logout
            logout();
            return false;
        }
    } else {
        console.warn('Dados do usuário não encontrados. Fazendo logout...');
        logout();
        return false;
    }
}

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
