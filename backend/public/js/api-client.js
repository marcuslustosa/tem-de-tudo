/**
 * CLIENTE HTTP UNIFICADO - TEM DE TUDO
 * Cliente HTTP robusto com tratamento de erros
 * 
 * @version 2.0.0
 * @author Tem de Tudo Team
 */

// ================================
// CLASSE DE CLIENTE API
// ================================
class APIClient {
    constructor() {
        this.baseURL = API_CONFIG.getBaseURL();
    }

    /**
     * Obter headers padrão
     * @param {boolean} includeAuth - Incluir token de autenticação
     */
    getHeaders(includeAuth = true) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (includeAuth) {
            const token = localStorage.getItem('token');
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
        }

        return headers;
    }

    /**
     * Obter headers de admin
     */
    getAdminHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        const adminToken = localStorage.getItem('admin_token');
        if (adminToken) {
            headers['Authorization'] = `Bearer ${adminToken}`;
        }

        return headers;
    }

    /**
     * Fazer requisição
     * @param {string} endpoint - Endpoint da API
     * @param {Object} options - Opções do fetch
     */
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') 
            ? endpoint 
            : `${this.baseURL}${endpoint}`;

        const config = {
            ...options,
            headers: {
                ...this.getHeaders(options.auth !== false),
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            return await this.handleResponse(response);
        } catch (error) {
            console.error('❌ Erro na requisição:', error);
            throw new Error('Erro de conexão. Verifique sua internet.');
        }
    }

    /**
     * Tratar resposta
     * @param {Response} response - Resposta do fetch
     */
    async handleResponse(response) {
        // Token expirado ou inválido
        if (response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/entrar.html';
            throw new Error('Sessão expirada. Faça login novamente.');
        }

        // Forbidden
        if (response.status === 403) {
            throw new Error('Você não tem permissão para acessar este recurso.');
        }

        // Not Found
        if (response.status === 404) {
            throw new Error('Recurso não encontrado.');
        }

        // Server Error
        if (response.status >= 500) {
            throw new Error('Erro no servidor. Tente novamente mais tarde.');
        }

        // Parse JSON
        const data = await response.json();

        // Verificar se resposta foi bem-sucedida
        if (!response.ok) {
            throw new Error(data.message || 'Erro na requisição');
        }

        return data;
    }

    /**
     * GET request
     * @param {string} endpoint - Endpoint da API
     * @param {Object} options - Opções adicionais
     */
    async get(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'GET',
            ...options
        });
    }

    /**
     * POST request
     * @param {string} endpoint - Endpoint da API
     * @param {Object} data - Dados para enviar
     * @param {Object} options - Opções adicionais
     */
    async post(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
            ...options
        });
    }

    /**
     * PUT request
     * @param {string} endpoint - Endpoint da API
     * @param {Object} data - Dados para enviar
     * @param {Object} options - Opções adicionais
     */
    async put(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
            ...options
        });
    }

    /**
     * DELETE request
     * @param {string} endpoint - Endpoint da API
     * @param {Object} options - Opções adicionais
     */
    async delete(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            ...options
        });
    }

    /**
     * PATCH request
     * @param {string} endpoint - Endpoint da API
     * @param {Object} data - Dados para enviar
     * @param {Object} options - Opções adicionais
     */
    async patch(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(data),
            ...options
        });
    }
}

// ================================
// INSTÂNCIA GLOBAL
// ================================
const apiClient = new APIClient();

// Expor globalmente
window.apiClient = apiClient;

console.log('🌐 APIClient carregado');
