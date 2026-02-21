/**
 * SPA Authentication - Tem de Tudo
 * Gerencia autenticação e perfis de usuário no SPA
 */

class SPAAuth {
    constructor() {
        this.user = null;
        this.token = null;
        this.userRole = null;
        
        // Tentar recuperar dados do localStorage
        this.loadFromStorage();
    }
    
    /**
     * Carregar dados do localStorage
     */
    loadFromStorage() {
        this.token = localStorage.getItem('token');
        const userData = localStorage.getItem('user_data');
        
        if (userData) {
            try {
                this.user = JSON.parse(userData);
                this.userRole = this.user.tipo || 'cliente';
            } catch (error) {
                console.error('Erro ao carregar dados do usuário:', error);
                this.clearAuth();
            }
        }
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    isAuthenticated() {
        return !!(this.token && this.user);
    }
    
    /**
     * Obter token de autenticação
     */
    getToken() {
        return this.token;
    }
    
    /**
     * Obter dados do usuário
     */
    getUser() {
        return this.user;
    }
    
    /**
     * Obter papel/role do usuário
     */
    getUserRole() {
        return this.userRole;
    }
    
    /**
     * Fazer login (chamado após autenticação bem-sucedida)
     */
    setAuth(token, userData) {
        this.token = token;
        this.user = userData;
        this.userRole = userData.tipo || 'cliente';
        
        // Salvar no localStorage
        localStorage.setItem('token', token);
        localStorage.setItem('user_data', JSON.stringify(userData));
        
        // Atualizar UI
        this.updateUserInterface();
    }
    
    /**
     * Fazer logout
     */
    logout() {
        this.clearAuth();
        window.location.href = '/entrar.html';
    }
    
    /**
     * Limpar dados de autenticação
     */
    clearAuth() {
        this.user = null;
        this.token = null;
        this.userRole = null;
        
        localStorage.removeItem('token');
        localStorage.removeItem('user_data');
    }
    
    /**
     * Atualizar interface do usuário
     */
    updateUserInterface() {
        if (!this.user) return;
        
        // Atualizar avatar e nome
        const userAvatar = document.getElementById('userAvatar');
        const userName = document.getElementById('userName');
        
        if (userAvatar) {
            userAvatar.textContent = this.user.nome ? this.user.nome.charAt(0).toUpperCase() : '?';
        }
        
        if (userName) {
            userName.textContent = this.user.nome || 'Usuário';
        }
        
        // Gerar menu baseado no role
        this.generateNavigation();
    }
    
    /**
     * Gerar navegação baseada no papel do usuário
     */
    generateNavigation() {
        const menus = this.getMenusForRole(this.userRole);
        
        // Gerar side navigation
        this.generateSideNavigation(menus.sideNav);
        
        // Gerar bottom navigation para mobile
        this.generateBottomNavigation(menus.bottomNav);
    }
    
    /**
     * Obter menus baseados no papel do usuário
     */
    getMenusForRole(role) {
        const menus = {
            cliente: {
                sideNav: [
                    { icon: 'fas fa-home', label: 'Dashboard', route: '/' },
                    { icon: 'fas fa-search', label: 'Buscar Empresas', route: '/buscar' },
                    { icon: 'fas fa-qrcode', label: 'Meu QR Code', route: '/meu-qr' },
                    { icon: 'fas fa-camera', label: 'Scanner', route: '/scanner' },
                    { icon: 'fas fa-gift', label: 'Promoções', route: '/promocoes' },
                    { icon: 'fas fa-history', label: 'Histórico', route: '/historico' },
                    { icon: 'fas fa-star', label: 'Minhas Avaliações', route: '/avaliacoes' },
                    { icon: 'fas fa-user', label: 'Perfil', route: '/perfil' },
                    { icon: 'fas fa-headset', label: 'Ajuda', route: '/ajuda' }
                ],
                bottomNav: [
                    { icon: 'fas fa-home', label: 'Início', route: '/' },
                    { icon: 'fas fa-search', label: 'Buscar', route: '/buscar' },
                    { icon: 'fas fa-qrcode', label: 'Scanner', route: '/scanner' },
                    { icon: 'fas fa-wallet', label: 'Pontos', route: '/historico' },
                    { icon: 'fas fa-user', label: 'Perfil', route: '/perfil' }
                ]
            },
            empresa: {
                sideNav: [
                    { icon: 'fas fa-tachometer-alt', label: 'Dashboard', route: '/' },
                    { icon: 'fas fa-camera', label: 'Scanner Cliente', route: '/scanner-cliente' },
                    { icon: 'fas fa-users', label: 'Clientes', route: '/clientes' },
                    { icon: 'fas fa-tags', label: 'Promoções', route: '/promocoes' },
                    { icon: 'fas fa-qrcode', label: 'QR Codes', route: '/qrcodes' },
                    { icon: 'fas fa-star', label: 'Avaliações', route: '/avaliacoes' },
                    { icon: 'fas fa-chart-bar', label: 'Relatórios', route: '/relatorios' },
                    { icon: 'fas fa-building', label: 'Perfil Empresa', route: '/perfil' }
                ],
                bottomNav: [
                    { icon: 'fas fa-tachometer-alt', label: 'Dashboard', route: '/' },
                    { icon: 'fas fa-camera', label: 'Scanner', route: '/scanner-cliente' },
                    { icon: 'fas fa-users', label: 'Clientes', route: '/clientes' },
                    { icon: 'fas fa-tags', label: 'Promoções', route: '/promocoes' },
                    { icon: 'fas fa-building', label: 'Perfil', route: '/perfil' }
                ]
            },
            admin: {
                sideNav: [
                    { icon: 'fas fa-chart-line', label: 'Dashboard Geral', route: '/' },
                    { icon: 'fas fa-users-cog', label: 'Usuários', route: '/usuarios' },
                    { icon: 'fas fa-building', label: 'Empresas', route: '/empresas' },
                    { icon: 'fas fa-check-circle', label: 'Aprovar Check-ins', route: '/checkins' },
                    { icon: 'fas fa-file-alt', label: 'Relatórios', route: '/relatorios' },
                    { icon: 'fas fa-cog', label: 'Configurações', route: '/configuracoes' },
                    { icon: 'fas fa-list', label: 'Logs do Sistema', route: '/logs' }
                ],
                bottomNav: [
                    { icon: 'fas fa-chart-line', label: 'Dashboard', route: '/' },
                    { icon: 'fas fa-users-cog', label: 'Usuários', route: '/usuarios' },
                    { icon: 'fas fa-building', label: 'Empresas', route: '/empresas' },
                    { icon: 'fas fa-check-circle', label: 'Check-ins', route: '/checkins' },
                    { icon: 'fas fa-cog', label: 'Config', route: '/configuracoes' }
                ]
            }
        };
        
        return menus[role] || menus.cliente;
    }
    
    /**
     * Gerar side navigation
     */
    generateSideNavigation(menuItems) {
        const navMenu = document.getElementById('navMenu');
        if (!navMenu) return;
        
        navMenu.innerHTML = menuItems.map(item => `
            <li class="nav-item">
                <a class="nav-link" data-route="${item.route}" onclick="spaRouter.navigate('${item.route}')">
                    <i class="${item.icon}"></i>
                    <span>${item.label}</span>
                </a>
            </li>
        `).join('');
    }
    
    /**
     * Gerar bottom navigation
     */
    generateBottomNavigation(menuItems) {
        const bottomNavMenu = document.getElementById('bottomNavMenu');
        if (!bottomNavMenu) return;
        
        bottomNavMenu.innerHTML = menuItems.map(item => `
            <a class="bottom-nav-item" data-route="${item.route}" onclick="spaRouter.navigate('${item.route}')">
                <i class="${item.icon}"></i>
                <span>${item.label}</span>
            </a>
        `).join('');
    }
    
    /**
     * Fazer requisição autenticada
     */
    async fetchAuthenticated(url, options = {}) {
        const headers = {
            'Authorization': `Bearer ${this.token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        const response = await fetch(url, {
            ...options,
            headers
        });
        
        // Se não autorizado, fazer logout
        if (response.status === 401) {
            this.logout();
            return null;
        }
        
        return response;
    }
}

// Instância global de autenticação
window.spaAuth = new SPAAuth();