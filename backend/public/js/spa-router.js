/**
 * SPA Router - Tem de Tudo
 * Gerencia roteamento baseado em hash para Single Page Application
 */

class SPARouter {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
        this.defaultRoute = null;
        
        // Escutar mudanças de hash
        window.addEventListener('hashchange', () => {
            this.handleRouteChange();
        });
        
        // Escutar carregamento da página
        window.addEventListener('load', () => {
            this.handleRouteChange();
        });
    }
    
    /**
     * Registrar uma nova rota
     */
    register(path, component, requiresAuth = true) {
        this.routes[path] = {
            component: component,
            requiresAuth: requiresAuth
        };
    }
    
    /**
     * Definir rota padrão
     */
    setDefault(path) {
        this.defaultRoute = path;
    }
    
    /**
     * Navegar para uma rota
     */
    navigate(path) {
        window.location.hash = path;
    }
    
    /**
     * Obter rota atual
     */
    getCurrentPath() {
        return window.location.hash.slice(1) || '/';
    }
    
    /**
     * Lidar com mudança de rota
     */
    async handleRouteChange() {
        const path = this.getCurrentPath();
        let route = this.routes[path];
        
        // Se rota não existe, usar padrão
        if (!route && this.defaultRoute) {
            route = this.routes[this.defaultRoute];
            window.location.hash = this.defaultRoute;
            return;
        }
        
        // Se ainda não encontrou, mostrar erro
        if (!route) {
            this.render404();
            return;
        }
        
        // Verificar autenticação se necessário
        if (route.requiresAuth && !window.spaAuth.isAuthenticated()) {
            window.location.href = '/entrar.html';
            return;
        }
        
        // Renderizar componente
        this.currentRoute = path;
        await this.renderRoute(route);
        
        // Atualizar navegação ativa
        this.updateActiveNavigation();
    }
    
    /**
     * Renderizar rota
     */
    async renderRoute(route) {
        const contentArea = document.getElementById('contentArea');
        
        if (!contentArea) {
            console.error('Content area not found');
            return;
        }
        
        try {
            // Esconder conteúdo durante transição
            contentArea.classList.remove('visible');
            
            // Aguardar um pouco para animação
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Renderizar componente
            if (typeof route.component === 'function') {
                const content = await route.component();
                contentArea.innerHTML = content;
            } else {
                contentArea.innerHTML = route.component;
            }
            
            // Mostrar conteúdo
            contentArea.classList.add('visible');
            
        } catch (error) {
            console.error('Erro ao renderizar rota:', error);
            this.renderError('Erro ao carregar página');
        }
    }
    
    /**
     * Renderizar página 404
     */
    render404() {
        const content = `
            <div class="error-container">
                <div class="error-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Página não encontrada</h2>
                    <p>A página que você procura não existe.</p>
                    <button class="btn btn-primary" onclick="spaRouter.navigate('/')">
                        <i class="fas fa-home"></i>
                        Voltar ao início
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('contentArea').innerHTML = content;
        document.getElementById('contentArea').classList.add('visible');
    }
    
    /**
     * Renderizar erro genérico
     */
    renderError(message) {
        const content = `
            <div class="error-container">
                <div class="error-card">
                    <i class="fas fa-exclamation-circle"></i>
                    <h2>Ops! Algo deu errado</h2>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-refresh"></i>
                        Tentar novamente
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('contentArea').innerHTML = content;
        document.getElementById('contentArea').classList.add('visible');
    }
    
    /**
     * Atualizar navegação ativa
     */
    updateActiveNavigation() {
        // Atualizar side nav
        const sideNavLinks = document.querySelectorAll('#navMenu .nav-link');
        sideNavLinks.forEach(link => {
            link.classList.remove('active');
            if (link.dataset.route === this.currentRoute) {
                link.classList.add('active');
            }
        });
        
        // Atualizar bottom nav
        const bottomNavLinks = document.querySelectorAll('#bottomNavMenu .bottom-nav-item');
        bottomNavLinks.forEach(link => {
            link.classList.remove('active');
            if (link.dataset.route === this.currentRoute) {
                link.classList.add('active');
            }
        });
    }
}

// Instância global do router
window.spaRouter = new SPARouter();