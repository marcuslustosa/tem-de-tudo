// =====================================================
// APLICATIVO MOBILE TEM DE TUDO - SISTEMA DE FIDELIDADE
// =====================================================

// Configuração da API
const API_BASE = '/api';

// Variáveis globais
let currentUser = null;
let userPoints = 0;
let userLevel = 'Bronze';
let isLoggedIn = false;

// =====================================================
// INICIALIZAÇÃO DO APP
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 App Tem de Tudo iniciado');
    
    // Verificar status de autenticação
    checkAuthStatus();
    
    // Configurar formulários
    setupForms();
    
    // Configurar navegação
    setupNavigation();
    
    // Configurar animações
    setupAnimations();
});

// =====================================================
// FUNÇÕES DE AUTENTICAÇÃO
// =====================================================

function checkAuthStatus() {
    const token = localStorage.getItem('token');
    
    if (token) {
        // Verificar se token é válido
        fetch(`${API_BASE}/user`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Token inválido');
        })
        .then(data => {
            currentUser = data.user;
            userPoints = data.user.pontos || 0;
            userLevel = calculateUserLevel(userPoints);
            updateUIForLoggedUser();
        })
        .catch(error => {
            console.log('Token inválido, removendo...');
            localStorage.removeItem('auth_token');
        });
    }
}

function calculateUserLevel(points) {
    if (points >= 10000) return 'Diamante';
    if (points >= 5000) return 'Platina';
    if (points >= 2500) return 'Ouro';
    if (points >= 1000) return 'Prata';
    return 'Bronze';
}

function updateUIForLoggedUser() {
    isLoggedIn = true;
    
    // Atualizar elementos da interface
    const pointsElement = document.getElementById('userPoints');
    const levelElement = document.getElementById('userLevel');
    
    if (pointsElement) {
        pointsElement.textContent = userPoints;
    }
    
    if (levelElement) {
        levelElement.textContent = userLevel;
    }
}

// =====================================================
// CONFIGURAÇÃO DE FORMULÁRIOS
// =====================================================

function setupForms() {
    // Formulário de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
        console.log('Login form configurado');
    }
    
    // Formulário de cadastro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
        console.log('Register form configurado');
    }
}

async function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const loginData = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    try {
        showLoading(true);
        
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Login bem-sucedido
            localStorage.setItem('token', data.data.token);
            currentUser = data.data.user;
            userPoints = data.data.user.pontos || 0;
            userLevel = calculateUserLevel(userPoints);
            
            showSuccess('Login realizado com sucesso! 🎉');
            
            // Redirecionar após 1.5 segundos usando URL do backend
            setTimeout(() => {
                window.location.href = data.data.redirect_to || '/';
            }, 1500);
            
        } else {
            throw new Error(data.message || 'Erro no login');
        }
        
    } catch (error) {
        console.error('Erro no login:', error);
        showError(error.message || 'Erro ao fazer login');
    } finally {
        showLoading(false);
    }
}

async function handleRegister(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const perfil = formData.get('perfil');
    
    const registerData = {
        name: formData.get('name'),
        email: formData.get('email'),
        telefone: perfil === 'cliente' ? formData.get('telefone_cliente') : formData.get('telefone'),
        password: formData.get('password'),
        password_confirmation: formData.get('password_confirmation'),
        perfil: perfil
    };
    
    // Validações básicas
    if (registerData.password !== registerData.password_confirmation) {
        showError('As senhas não coincidem');
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(registerData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Salvar token e usuário retornados
            localStorage.setItem('token', data.data.token);
            currentUser = data.data.user;
            userPoints = data.data.user.pontos || 0;
            userLevel = calculateUserLevel(userPoints);
            
            showSuccess('Cadastro realizado com sucesso! 🎉');
            
            // Redirecionar após 2 segundos usando URL do backend
            setTimeout(() => {
                window.location.href = data.data.redirect_to || '/login.html';
            }, 2000);
            
        } else {
            throw new Error(data.message || 'Erro no cadastro');
        }
        
    } catch (error) {
        console.error('Erro no cadastro:', error);
        showError(error.message || 'Erro ao criar conta');
    } finally {
        showLoading(false);
    }
}

// =====================================================
// FUNÇÕES DE NAVEGAÇÃO
// =====================================================

function setupNavigation() {
    // Configurar links ativos
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-menu a, .mobile-nav-menu a');
    
    navLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        if (linkPath === currentPath) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// =====================================================
// ANIMAÇÕES E EFEITOS
// =====================================================

function setupAnimations() {
    // Animação de entrada para cards
    const cards = document.querySelectorAll('.card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// =====================================================
// FUNÇÕES DE FEEDBACK
// =====================================================

function showLoading(show = true) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    // Remover notificação existente
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    // Criar nova notificação
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Estilos inline
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    document.body.appendChild(notification);
    
    // Remover após 4 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Adicionar CSS das animações
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .notification i {
            font-size: 1.25rem;
        }
    `;
    document.head.appendChild(style);
}

// =====================================================
// UTILITÁRIOS
// =====================================================

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatPoints(points) {
    return new Intl.NumberFormat('pt-BR').format(points);
}

// =====================================================
// EXPORTAR FUNÇÕES GLOBAIS
// =====================================================

window.showSuccess = showSuccess;
window.showError = showError;
window.showLoading = showLoading;