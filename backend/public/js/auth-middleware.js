// Middleware JavaScript para proteção de rotas por perfil
class AuthMiddleware {
    constructor() {
        this.perfilRoutes = {
            'administrador': '/admin/dashboard.html',
            'gestor': '/gestor/home.html',
            'recepcionista': '/recepcao/index.html',
            'usuario_comum': '/app/home.html'
        };
    }

    // Verificar se usuário está logado e tem token válido
    isAuthenticated() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');

        if (!token || !user) {
            return false;
        }

        try {
            const userData = JSON.parse(user);
            return userData && userData.perfil;
        } catch (e) {
            return false;
        }
    }

    // Obter dados do usuário logado
    getCurrentUser() {
        try {
            const user = localStorage.getItem('user');
            return user ? JSON.parse(user) : null;
        } catch (e) {
            return null;
        }
    }

    // Obter perfil do usuário logado
    getCurrentPerfil() {
        const user = this.getCurrentUser();
        return user ? user.perfil : null;
    }

    // Verificar se usuário tem acesso à rota atual
    hasAccessToCurrentRoute() {
        const currentPath = window.location.pathname;
        const userPerfil = this.getCurrentPerfil();

        if (!userPerfil) {
            return false;
        }

        // Verificar se a rota atual corresponde ao perfil do usuário
        const expectedRoute = this.perfilRoutes[userPerfil];
        if (!expectedRoute) {
            return false;
        }

        // Verificar se o caminho atual começa com a rota esperada
        return currentPath.startsWith(expectedRoute.replace('.html', '').replace('/index.html', ''));
    }

    // Redirecionar usuário para sua rota correta
    redirectToCorrectRoute() {
        const userPerfil = this.getCurrentPerfil();
        if (userPerfil && this.perfilRoutes[userPerfil]) {
            window.location.href = this.perfilRoutes[userPerfil];
        } else {
            this.logout();
        }
    }

    // Logout do usuário
    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login.html';
    }

    // Verificar acesso na inicialização da página
    checkAccess() {
        // Se não está logado, redirecionar para login
        if (!this.isAuthenticated()) {
            if (window.location.pathname !== '/login.html' && window.location.pathname !== '/register.html') {
                window.location.href = '/login.html';
            }
            return;
        }

        // Se está logado mas não tem acesso à rota atual, redirecionar
        if (!this.hasAccessToCurrentRoute()) {
            this.redirectToCorrectRoute();
        }
    }

    // Atualizar dados do usuário (após refresh do token, etc.)
    updateUserData(userData) {
        localStorage.setItem('user', JSON.stringify(userData));
    }

    // Verificar se token ainda é válido (chamada para API)
    async validateToken() {
        const token = localStorage.getItem('token');
        if (!token) {
            return false;
        }

        try {
            const baseUrl = window.location.origin;
            const response = await fetch(`${baseUrl}/api/user`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.data && data.data.user) {
                    this.updateUserData(data.data.user);
                    return true;
                }
            }

            // Token inválido, fazer logout
            this.logout();
            return false;
        } catch (error) {
            console.error('Erro ao validar token:', error);
            this.logout();
            return false;
        }
    }
}

// Instância global do middleware
const authMiddleware = new AuthMiddleware();

// Verificar acesso quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    authMiddleware.checkAccess();
});

// Função global para logout
window.logout = function() {
    authMiddleware.logout();
};

// Função global para obter usuário atual
window.getCurrentUser = function() {
    return authMiddleware.getCurrentUser();
};

// Função global para obter perfil atual
window.getCurrentPerfil = function() {
    return authMiddleware.getCurrentPerfil();
};
