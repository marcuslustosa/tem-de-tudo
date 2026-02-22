/* SISTEMA DE AUTENTICAÇÃO GLOBAL UNIFICADO - TEM DE TUDO */

// Sistema de bypass para desenvolvimento local
function initializeGlobalAuth() {
    // Verificar se estamos em localhost (ambiente de desenvolvimento)
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' || 
                       window.location.hostname.includes('localhost');

    if (isLocalhost) {
        console.log('🔧 Modo desenvolvimento detectado - inicializando bypass de autenticação');
        setupDevelopmentAuth();
    }
    
    // Verificar autenticação existente
    return checkAuthentication();
}

// Configuração de usuários fictícios para desenvolvimento
function setupDevelopmentAuth() {
    const currentPage = window.location.pathname.toLowerCase();
    
    // Se não há token, criar um usuário fictício baseado na página atual
    if (!localStorage.getItem('token')) {
        let fakeUser = {};
        
        if (currentPage.includes('admin')) {
            // Páginas administrativas
            fakeUser = {
                id: 1,
                name: 'Admin Desenvolvimento',
                email: 'admin@temdetudo.com',
                perfil: 'admin',
                tipo: 'admin',
                empresa_id: null
            };
            localStorage.setItem('token', 'fake_admin_token_dev_' + Date.now());
            console.log('👨‍💼 Usuário admin fictício criado para desenvolvimento');
            
        } else if (currentPage.includes('empresa')) {
            // Páginas empresariais
            fakeUser = {
                id: 2,
                name: 'Empresa Desenvolvimento',
                email: 'empresa@temdetudo.com',
                perfil: 'empresa',
                tipo: 'empresa',
                empresa_id: 1,
                empresa_nome: 'Loja Demo'
            };
            localStorage.setItem('token', 'fake_empresa_token_dev_' + Date.now());
            console.log('🏢 Usuário empresa fictício criado para desenvolvimento');
            
        } else {
            // Páginas de usuário normal (app-*)
            fakeUser = {
                id: 3,
                name: 'Usuario Desenvolvimento',
                email: 'usuario@temdetudo.com',
                perfil: 'client',
                tipo: 'client',
                pontos: 250,
                telefone: '(11) 99999-9999'
            };
            localStorage.setItem('token', 'fake_user_token_dev_' + Date.now());
            console.log('👤 Usuário cliente fictício criado para desenvolvimento');
        }
        
        localStorage.setItem('user', JSON.stringify(fakeUser));
        localStorage.setItem('isDevMode', 'true');
    }
}

// Verificação de autenticação unificada
function checkAuthentication() {
    const token = localStorage.getItem('token');
    const userStr = localStorage.getItem('user');
    
    if (!token || !userStr) {
        console.log('❌ Token ou usuário não encontrado');
        return false;
    }
    
    try {
        const user = JSON.parse(userStr);
        console.log('✅ Usuário autenticado:', user.name, '(' + user.perfil + ')');
        
        // Atualizar informações da navbar se existir
        updateNavbarUserInfo(user);
        
        return {
            token: token,
            user: user,
            isAuthenticated: true
        };
    } catch (error) {
        console.error('❌ Erro ao parsear dados do usuário:', error);
        return false;
    }
}

// Atualizar informações do usuário na navbar
function updateNavbarUserInfo(user) {
    // Atualizar avatar se existe
    const avatar = document.querySelector('.profile-avatar');
    if (avatar && user.name) {
        const initials = user.name.split(' ').map(n => n[0]).join('').substr(0, 2).toUpperCase();
        avatar.textContent = initials;
        avatar.title = user.name + ' (' + user.perfil + ')';
    }
    
    // Atualizar nome do usuário em elementos com classe user-name
    const nameElements = document.querySelectorAll('.user-name');
    nameElements.forEach(el => {
        el.textContent = user.name;
    });
    
    // Atualizar email em elementos com classe user-email
    const emailElements = document.querySelectorAll('.user-email');
    emailElements.forEach(el => {
        el.textContent = user.email;
    });
}

// Função de logout global
function globalLogout() {
    if (confirm('Tem certeza que deseja sair do sistema?')) {
        localStorage.clear();
        console.log('👋 Logout realizado - redirecionando para login');
        window.location.href = '/entrar.html';
    }
}

// Redirecionamento seguro baseado no perfil do usuário
function redirectBasedOnProfile() {
    const auth = checkAuthentication();
    if (!auth) {
        window.location.href = '/entrar.html';
        return;
    }
    
    const user = auth.user;
    const currentPage = window.location.pathname.toLowerCase();
    
    // Verificar se usuário está na página correta baseado no perfil
    if (user.perfil === 'admin' && !currentPage.includes('admin') && !currentPage.includes('entrar')) {
        console.log('📋 Redirecionando admin para painel administrativo');
        window.location.href = '/admin-painel.html';
        return;
    }
    
    if (user.perfil === 'empresa' && !currentPage.includes('empresa') && !currentPage.includes('entrar')) {
        console.log('🏢 Redirecionando empresa para dashboard');
        window.location.href = '/empresa-dashboard.html';
        return;
    }
    
    if (user.perfil === 'client' && currentPage.includes('admin')) {
        console.log('👤 Cliente tentando acessar área admin - redirecionando');
        window.location.href = '/app-perfil.html';
        return;
    }
}

// Verificação de permissões por página
function checkPagePermissions() {
    const auth = checkAuthentication();
    if (!auth) return false;
    
    const user = auth.user;
    const currentPage = window.location.pathname.toLowerCase();
    
    // Páginas administrativas - apenas admins
    if (currentPage.includes('admin-') && user.perfil !== 'admin') {
        console.log('🚫 Acesso negado - página administrativa requer perfil admin');
        window.location.href = '/app-perfil.html';
        return false;
    }
    
    // Páginas empresariais - apenas empresas
    if (currentPage.includes('empresa-') && user.perfil !== 'empresa') {
        console.log('🚫 Acesso negado - página empresarial requer perfil empresa');
        if (user.perfil === 'admin') {
            window.location.href = '/admin-painel.html';
        } else {
            window.location.href = '/app-perfil.html';
        }
        return false;
    }
    
    return true;
}

// Função utilitária para fazer requisições autenticadas
function authenticatedRequest(url, options = {}) {
    const token = localStorage.getItem('token');
    const defaultOptions = {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            ...options.headers
        }
    };
    
    return fetch(url, { ...defaultOptions, ...options })
        .catch(error => {
            console.error('Erro na requisição autenticada:', error);
            // Em caso de erro de autenticação, limpar dados e redirecionar
            if (error.status === 401 || error.status === 403) {
                localStorage.clear();
                window.location.href = '/entrar.html';
            }
            throw error;
        });
}

// Inicialização automática quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de autenticação global...');
    
    // Pequeno delay para garantir que outros scripts carreguem primeiro
    setTimeout(() => {
        const auth = initializeGlobalAuth();
        
        // Apenas verificar permissões se não estamos na página de login
        if (!window.location.pathname.includes('entrar.html')) {
            checkPagePermissions();
        }
        
        // Se estamos autenticados, inicializar recursos dependentes de auth
        if (auth && auth.isAuthenticated) {
            console.log('✅ Sistema de autenticação inicializado com sucesso');
            
            // Disparar evento customizado para outros scripts
            window.dispatchEvent(new CustomEvent('authInitialized', { 
                detail: { auth: auth } 
            }));
        }
    }, 100);
});

// Expor funções globais
window.globalAuth = {
    check: checkAuthentication,
    logout: globalLogout,
    request: authenticatedRequest,
    init: initializeGlobalAuth,
    checkPermissions: checkPagePermissions
};