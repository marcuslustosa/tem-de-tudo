// Configuração dinâmica de API baseada no ambiente
const API_CONFIG = {
    // Detecta automaticamente se está em produção (Render) ou local
    getBaseURL() {
        const hostname = window.location.hostname;
        
        // Produção no Render
        if (hostname.includes('onrender.com') || hostname.includes('render.com')) {
            return window.location.origin;
        }
        
        // Desenvolvimento local
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            return 'http://127.0.0.1:8001';
        }
        
        // Fallback
        return window.location.origin;
    },
    
    // URLs completas dos endpoints
    get login() {
        return `${this.getBaseURL()}/api/auth/login`;
    },
    
    get register() {
        return `${this.getBaseURL()}/api/auth/register`;
    },
    
    get empresas() {
        return `${this.getBaseURL()}/api/cliente/empresas`;
    },
    
    get historicoPontos() {
        return `${this.getBaseURL()}/api/cliente/historico-pontos`;
    },
    
    get debug() {
        return `${this.getBaseURL()}/api/debug`;
    },
    
    // Helper para fazer requisições autenticadas
    async fetchWithAuth(url, options = {}) {
        const token = localStorage.getItem('tem_de_tudo_token');
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` })
            }
        };
        
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, mergedOptions);
            const data = await response.json();
            
            // Se token expirado, redirecionar para login
            if (response.status === 401) {
                localStorage.removeItem('tem_de_tudo_token');
                localStorage.removeItem('tem_de_tudo_user');
                window.location.href = '/entrar.html';
            }
            
            return { response, data };
        } catch (error) {
            console.error('Erro na requisição:', error);
            throw error;
        }
    },
    
    // Verificar se servidor está online
    async checkHealth() {
        try {
            const response = await fetch(this.debug);
            const data = await response.json();
            return data.status === 'OK';
        } catch {
            return false;
        }
    }
};

// Expor globalmente
window.API_CONFIG = API_CONFIG;

console.log('🚀 API Config carregado:', API_CONFIG.getBaseURL());
