/**
 * API para sistema de pontos e check-ins
 * Tem de Tudo - Sistema de Fidelidade
 */

class PontosAPI {
    constructor() {
        this.baseURL = '/api';
        this.token = localStorage.getItem('auth_token');
    }

    // Configurar token de autorização
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    // Headers padrão para requisições
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (this.token) {
            headers.Authorization = `Bearer ${this.token}`;
        }

        return headers;
    }

    // Método genérico para fazer requisições
    async request(url, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${url}`, {
                headers: this.getHeaders(),
                ...options
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro na requisição');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // === AUTENTICAÇÃO === //

    async login(email, password) {
        const response = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        if (response.success && response.token) {
            this.setToken(response.token);
        }

        return response;
    }

    async register(name, email, password, telefone) {
        const response = await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify({ 
                name, 
                email, 
                password, 
                password_confirmation: password,
                telefone 
            })
        });

        if (response.success && response.token) {
            this.setToken(response.token);
        }

        return response;
    }

    async logout() {
        try {
            await this.request('/logout', { method: 'POST' });
        } finally {
            this.token = null;
            localStorage.removeItem('auth_token');
        }
    }

    async getUser() {
        return await this.request('/user');
    }

    // === CHECK-IN === //

    async checkin(empresaId, valorCompra, fotoCupom, latitude = null, longitude = null, observacoes = null) {
        const formData = new FormData();
        formData.append('empresa_id', empresaId);
        formData.append('valor_compra', valorCompra);
        formData.append('foto_cupom', fotoCupom);
        
        if (latitude) formData.append('latitude', latitude);
        if (longitude) formData.append('longitude', longitude);
        if (observacoes) formData.append('observacoes', observacoes);

        const response = await fetch(`${this.baseURL}/pontos/checkin`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Erro no check-in');
        }

        return data;
    }

    // === PONTOS === //

    async getMeusDados() {
        return await this.request('/pontos/meus-dados');
    }

    async getHistoricoPontos() {
        return await this.request('/pontos/historico');
    }

    async resgatarPontos(tipoRecompensa, custoPontos, descricao) {
        return await this.request('/pontos/resgatar', {
            method: 'POST',
            body: JSON.stringify({
                recompensa_tipo: tipoRecompensa,
                custo_pontos: custoPontos,
                descricao: descricao
            })
        });
    }

    // === CUPONS === //

    async getMeusCupons() {
        return await this.request('/pontos/meus-cupons');
    }

    async usarCupom(cupomId) {
        return await this.request(`/pontos/usar-cupom/${cupomId}`, {
            method: 'POST'
        });
    }

    // === EMPRESAS === //

    async getEmpresas() {
        return await this.request('/empresas');
    }
}

// Instância global da API
const pontosAPI = new PontosAPI();

// === FUNÇÕES DE UTILIDADE === //

function showError(message) {
    // Criar toast de erro
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10001;
        max-width: 350px;
        word-wrap: break-word;
    `;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-times-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function showSuccess(message) {
    // Criar toast de sucesso
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10001;
        max-width: 350px;
        word-wrap: break-word;
    `;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function showLoading(message = 'Carregando...') {
    // Remover loading anterior se existir
    const existingLoader = document.getElementById('globalLoader');
    if (existingLoader) {
        existingLoader.remove();
    }

    const loader = document.createElement('div');
    loader.id = 'globalLoader';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        z-index: 10002;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    loader.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div style="width: 40px; height: 40px; border: 3px solid #f3f4f6; border-top: 3px solid #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
            <p style="margin: 0; color: #374151;">${message}</p>
        </div>
    `;
    
    // Adicionar CSS da animação se não existir
    if (!document.getElementById('spinAnimation')) {
        const style = document.createElement('style');
        style.id = 'spinAnimation';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('globalLoader');
    if (loader) {
        loader.remove();
    }
}

// === FUNÇÕES DE GEOLOCALIZAÇÃO === //

function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocalização não suportada'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
            },
            (error) => {
                reject(new Error('Erro ao obter localização: ' + error.message));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutos
            }
        );
    });
}

// === FUNÇÕES DE CAMERA === //

function initCamera(videoElement, canvasElement) {
    return new Promise((resolve, reject) => {
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        })
        .then(stream => {
            videoElement.srcObject = stream;
            videoElement.play();
            resolve(stream);
        })
        .catch(error => {
            reject(new Error('Erro ao acessar câmera: ' + error.message));
        });
    });
}

function capturePhoto(videoElement, canvasElement) {
    const context = canvasElement.getContext('2d');
    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;
    
    context.drawImage(videoElement, 0, 0);
    
    return new Promise((resolve) => {
        canvasElement.toBlob((blob) => {
            resolve(blob);
        }, 'image/jpeg', 0.8);
    });
}

// === UTILITÁRIOS DE FORMATAÇÃO === //

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatPoints(points) {
    return new Intl.NumberFormat('pt-BR').format(points);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(new Date(date));
}

function formatDateTime(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// === VALIDAÇÕES === //

function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validatePhone(phone) {
    const regex = /^\(\d{2}\)\s\d{4,5}-\d{4}$/;
    return regex.test(phone);
}

function validateCurrency(value) {
    const numericValue = parseFloat(value.replace(/[^\d,]/g, '').replace(',', '.'));
    return !isNaN(numericValue) && numericValue > 0;
}

// === MÁSCARAS DE INPUT === //

function maskPhone(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
        value = value.replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3');
    }
    
    input.value = value;
}

function maskCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) / 100).toFixed(2);
    value = value.replace('.', ',');
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = 'R$ ' + value;
}

// === INICIALIZAÇÃO === //

// Verificar autenticação ao carregar
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('auth_token');
    
    // Se estiver em página que requer login e não tiver token
    const requiresAuth = ['/checkin.html', '/pontos.html', '/profile-client.html'];
    const currentPath = window.location.pathname;
    
    if (requiresAuth.some(path => currentPath.includes(path)) && !token) {
        window.location.href = '/login.html';
        return;
    }
    
    // Se tiver token, verificar se ainda é válido
    if (token) {
        pontosAPI.getUser().catch(() => {
            localStorage.removeItem('auth_token');
            if (requiresAuth.some(path => currentPath.includes(path))) {
                window.location.href = '/login.html';
            }
        });
    }
});

// Exportar para uso global
window.pontosAPI = pontosAPI;
window.showError = showError;
window.showSuccess = showSuccess;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.getCurrentPosition = getCurrentPosition;
window.initCamera = initCamera;
window.capturePhoto = capturePhoto;