/* SISTEMA DE NAVEGAÇÃO GLOBAL UNIFICADO - TEM DE TUDO */

// Configuração de navegação por contexto
const navConfigs = {
    app: {
        title: 'Tem de Tudo',
        logo: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iYXBwR3JhZGllbnQiIHgxPSIwIiB5MT0iMCIgeDI9IjEiIHkyPSIxIj4KPHN0b3Agc3RvcC1jb2xvcj0iIzZGMUFCNiIvPgo8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiM5MzMzRUEiLz4KPC9saW5lYXJHcmFkaWVudD4KPC9kZWZzPgo8cmVjdCB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHJ4PSI4IiBmaWxsPSJ1cmwoI2FwcEdyYWRpZW50KSIvPgo8dGV4dCB4PSIxNiIgeT0iMjIiIGZvbnQtZmFtaWx5PSJJbnRlciIgZm9udC1zaXplPSIxMiIgZm9udC13ZWlnaHQ9IjcwMCIgZmlsbD0iI0ZGRkZGRiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+VERUPC90ZXh0Pgo8L3N2Zz4K',
        items: [
            { href: '/app-inicio.html', icon: 'fas fa-home', text: 'Início' },
            { href: '/app-meus-pontos.html', icon: 'fas fa-coins', text: 'Pontos' },
            { href: '/app-qrcode.html', icon: 'fas fa-qrcode', text: 'QR Code' },
            { href: '/app-perfil.html', icon: 'fas fa-user', text: 'Perfil' }
        ],
        bottomNav: true
    },
    empresa: {
        title: 'Painel Empresa',
        logo: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iZW1wcmVzYUdyYWRpZW50IiB4MT0iMCIgeTE9IjAiIHgyPSIxIiB5Mj0iMSI+CjxzdG9wIHN0b3AtY29sb3I9IiM2RjFBQjYiLz4KPHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjNEEwRThDIi8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iOCIgZmlsbD0idXJsKCNlbXByZXNhR3JhZGllbnQpIi8+CjxyZWN0IHg9IjgiIHk9IjEwIiB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIHJ4PSIyIiBmaWxsPSIjRkZGRkZGIiBvcGFjaXR5PSIwLjkiLz4KPHN2ZyB4PSI4IiB5PSI2IiB3aWR0aD0iMTYiIGhlaWdodD0iNCIgdmlld0JveD0iMCAwIDE2IDQiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiByeD0iMSIgZmlsbD0iI0ZGRkZGRiIvPgo8cmVjdCB4PSI2IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiByeD0iMSIgZmlsbD0iI0ZGRkZGRiIvPgo8cmVjdCB4PSIxMiIgd2lkdGg9IjQiIGhlaWdodD0iNCIgcng9IjEiIGZpbGw9IiNGRkZGRkYiLz4KPC9zdmc+Cjwvc3ZnPgo=',
        items: [
            { href: '/empresa-dashboard.html', icon: 'fas fa-chart-line', text: 'Dashboard' },
            { href: '/empresa-clientes.html', icon: 'fas fa-users', text: 'Clientes' },
            { href: '/empresa-promocoes.html', icon: 'fas fa-tags', text: 'Promoções' },
            { href: '/empresa-relatorios.html', icon: 'fas fa-chart-bar', text: 'Relatórios' }
        ],
        bottomNav: false
    },
    admin: {
        title: 'Administração',
        logo: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iYWRtaW5HcmFkaWVudCIgeDE9IjAiIHkxPSIwIiB4Mj0iMSIgeTI9IjEiPgo8c3RvcCBzdG9wLWNvbG9yPSIjNkYxQUI2Ii8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzRBMEU4QyIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+CjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgcng9IjgiIGZpbGw9InVybCgjYWRtaW5HcmFkaWVudCkiLz4KPHBvbHlnb24gcG9pbnRzPSIxMCw4IDIyLDggMjQsMTAgMjQsMjQgOCwyNCAxMCwxMCIgZmlsbD0iI0ZGRkZGRiIgb3BhY2l0eT0iMC45Ii8+CjxjaXJjbGUgY3g9IjE2IiBjeT0iMTQiIHI9IjIiIGZpbGw9IiM2RjFBQjYiLz4KPHN2ZyB4PSIxMiIgeT0iMTgiIHdpZHRoPSI4IiBoZWlnaHQ9IjQiIHZpZXdCb3g9IjAgMCA4IDQiPgo8cG9seWdvbiBwb2ludHM9IjAsMCA0LDQgOCwwIiBmaWxsPSIjNkYxQUI2Ii8+Cjwvc3ZnPgo8L3N2Zz4K',
        items: [
            { href: '/admin-painel.html', icon: 'fas fa-tachometer-alt', text: 'Painel' },
            { href: '/admin-criar-usuario.html', icon: 'fas fa-user-plus', text: 'Usuários' },
            { href: '/admin-promocoes.html', icon: 'fas fa-bullhorn', text: 'Promoções' },
            { href: '/admin-relatorios.html', icon: 'fas fa-file-chart', text: 'Relatórios' }
        ],
        bottomNav: false
    }
};

// Detectar contexto da página atual
function detectPageContext() {
    const path = window.location.pathname.toLowerCase();
    
    if (path.includes('admin-')) return 'admin';
    if (path.includes('empresa-')) return 'empresa';
    return 'app'; // default para páginas do usuário
}

// Criar navbar contextual
function createContextualNavbar() {
    const context = detectPageContext();
    const config = navConfigs[context];
    
    if (!config) {
        console.error('Configuração de navegação não encontrada para contexto:', context);
        return;
    }
    
    // Verificar se já existe navbar
    let existingNav = document.querySelector('.global-navbar');
    if (existingNav) {
        existingNav.remove();
    }
    
    // Criar nova navbar
    const navbar = document.createElement('nav');
    navbar.className = 'global-navbar';
    
    navbar.innerHTML = `
        <div class="navbar-content">
            <a href="/${context === 'app' ? 'app-inicio' : context === 'empresa' ? 'empresa-dashboard' : 'admin-painel'}.html" class="navbar-logo">
                <img src="${config.logo}" alt="Logo">
                <span class="navbar-title">${config.title}</span>
            </a>
            
            <div class="navbar-menu">
                ${config.items.map(item => `
                    <a href="${item.href}" class="navbar-item ${isCurrentPage(item.href) ? 'active' : ''}">
                        <i class="${item.icon}"></i>
                        <span>${item.text}</span>
                    </a>
                `).join('')}
            </div>
            
            <div class="navbar-profile" onclick="toggleProfileMenu()">
                <div class="profile-avatar" title="Clique para ver opções">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Menu de perfil (dropdown) -->
        <div id="profileMenu" class="profile-menu" style="display: none;">
            <div class="profile-menu-content">
                <div class="profile-info">
                    <div class="profile-avatar-large">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-details">
                        <div class="user-name">Carregando...</div>
                        <div class="user-email">Carregando...</div>
                    </div>
                </div>
                <div class="profile-actions">
                    ${context === 'app' ? `<a href="/app-editar-perfil.html" class="profile-action"><i class="fas fa-edit"></i> Editar Perfil</a>` : ''}
                    ${context === 'app' ? `<a href="/app-configuracoes.html" class="profile-action"><i class="fas fa-cog"></i> Configurações</a>` : ''}
                    ${context === 'empresa' ? `<a href="/empresa-configuracoes.html" class="profile-action"><i class="fas fa-cog"></i> Configurações</a>` : ''}
                    <a href="javascript:void(0)" onclick="globalLogout()" class="profile-action logout">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    `;
    
    // Inserir no início do body
    document.body.insertBefore(navbar, document.body.firstChild);
    
    // Criar bottom navigation se necessário (apenas para app)
    if (config.bottomNav) {
        createBottomNav(config);
    }
    
    // Adicionar estilos do menu de perfil
    addProfileMenuStyles();
    
    console.log(`✅ Navbar contextual criada para: ${context}`);
}

// Criar navegação inferior (apenas para app)
function createBottomNav(config) {
    // Remover bottom nav existente
    const existingBottomNav = document.querySelector('.bottom-nav');
    if (existingBottomNav) {
        existingBottomNav.remove();
    }
    
    const bottomNav = document.createElement('nav');
    bottomNav.className = 'bottom-nav';
    
    bottomNav.innerHTML = `
        <div class="bottom-nav-container">
            ${config.items.map(item => `
                <a href="${item.href}" class="bottom-nav-item ${isCurrentPage(item.href) ? 'active' : ''}">
                    <i class="${item.icon}"></i>
                    <span>${item.text}</span>
                </a>
            `).join('')}
        </div>
    `;
    
    document.body.appendChild(bottomNav);
}

// Verificar se é a página atual
function isCurrentPage(href) {
    const currentPath = window.location.pathname;
    const targetPath = href.replace(/^\//, '');
    return currentPath.endsWith(targetPath) || currentPath.includes(targetPath.replace('.html', ''));
}

// Toggle do menu de perfil
function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
}

// Toggle do menu mobile
function toggleMobileMenu() {
    const navbarMenu = document.querySelector('.navbar-menu');
    if (navbarMenu) {
        navbarMenu.classList.toggle('mobile-active');
    }
}

// Fechar menu quando clicar fora
document.addEventListener('click', function(event) {
    const profileMenu = document.getElementById('profileMenu');
    const profileAvatar = document.querySelector('.navbar-profile');
    
    if (profileMenu && !profileAvatar?.contains(event.target)) {
        profileMenu.style.display = 'none';
    }
});

// Adicionar estilos do menu de perfil
function addProfileMenuStyles() {
    if (document.getElementById('profileMenuStyles')) return;
    
    const styles = document.createElement('style');
    styles.id = 'profileMenuStyles';
    styles.textContent = `
        .profile-menu {
            position: absolute;
            top: 100%;
            right: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--gray-200);
            min-width: 280px;
            z-index: 1001;
            animation: slideDown 0.2s ease;
        }
        
        .profile-menu-content {
            padding: 1.5rem;
        }
        
        .profile-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 1rem;
        }
        
        .profile-avatar-large {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--vivo-purple), var(--vivo-purple-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .profile-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 1rem;
        }
        
        .user-email {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }
        
        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .profile-action {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .profile-action:hover {
            background: var(--gray-100);
            color: var(--vivo-purple);
        }
        
        .profile-action.logout {
            color: var(--vivo-red);
            border-top: 1px solid var(--gray-200);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }
        
        .profile-action.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--vivo-red);
        }
        
        .navbar-menu.mobile-active {
            display: flex;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            border-top: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .profile-menu {
                right: 1rem;
                min-width: 250px;
            }
        }
    `;
    
    document.head.appendChild(styles);
}

// Atualizar navegação quando a página muda
function updateNavigation() {
    // Atualizar itens ativos
    document.querySelectorAll('.navbar-item, .bottom-nav-item').forEach(item => {
        item.classList.remove('active');
        if (isCurrentPage(item.getAttribute('href'))) {
            item.classList.add('active');
        }
    });
}

// Inicialização automática
function initializeGlobalNavigation() {
    console.log('🧭 Inicializando sistema de navegação global...');
    
    // Criar navbar contextual
    createContextualNavbar();
    
    // Escutar mudanças de autenticação
    window.addEventListener('authInitialized', function(event) {
        const auth = event.detail.auth;
        if (auth && auth.user) {
            console.log('👤 Atualizando navbar com dados do usuário:', auth.user.name);
        }
    });
    
    // Atualizar navegação regularmente
    updateNavigation();
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Pequeno delay para garantir que outros scripts carreguem primeiro
    setTimeout(initializeGlobalNavigation, 150);
});

// Expor funções globais
window.globalNavigation = {
    create: createContextualNavbar,
    update: updateNavigation,
    toggleProfile: toggleProfileMenu,
    toggleMobile: toggleMobileMenu
};