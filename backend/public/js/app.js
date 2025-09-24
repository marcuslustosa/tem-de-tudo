// Configuração da API
const API_BASE = '/api';

// Estado global da aplicação
let currentUser = null;
let userPoints = 0;
let userLevel = 'Bronze';

// Inicializar aplicação
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Tem de Tudo - Programa de Fidelidade iniciado!');
    
    // Verificar se usuário está logado
    checkAuthStatus();
    
    // Configurar menu mobile
    setupMobileMenu();
    
    // Configurar formulários
    setupForms();
    
    // Configurar navegação
    setupNavigation();
});

// Verificar status de autenticação
function checkAuthStatus() {
    const token = localStorage.getItem('auth_token');
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

// Calcular nível do usuário baseado nos pontos
function calculateUserLevel(points) {
    if (points >= 10000) return 'Diamante';
    if (points >= 5000) return 'Ouro';
    if (points >= 1000) return 'Prata';
    return 'Bronze';
}

// Atualizar UI para usuário logado
function updateUIForLoggedUser() {
    const navLinks = document.getElementById('nav-links');
    if (navLinks && currentUser) {
        navLinks.innerHTML = `
            <li><a href="/profile-client.html">👤 ${currentUser.name}</a></li>
            <li><a href="#" onclick="logout()" class="btn btn-secondary">Sair</a></li>
        `;
    }
}

// Configurar menu mobile
function setupMobileMenu() {
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

// Configurar formulários
function setupForms() {
    // Formulário de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
        console.log('Login form configurado');
    }
    
    // Formulário de registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
        console.log('Register form configurado');
    }
}

// Lidar com login
async function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    try {
        showLoading('Entrando...');
        
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            // Login bem-sucedido
            localStorage.setItem('auth_token', result.token);
            currentUser = result.user;
            userPoints = result.user.pontos || 0;
            userLevel = calculateUserLevel(userPoints);
            
            showSuccess('Login realizado com sucesso! 🎉');
            
            setTimeout(() => {
                window.location.href = '/profile-client.html';
            }, 1500);
            
        } else {
            throw new Error(result.message || 'Erro no login');
        }
        
    } catch (error) {
        console.error('Erro no login:', error);
        showError('Erro no login: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Lidar com registro
async function handleRegister(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        password: formData.get('password'),
        password_confirmation: formData.get('password_confirmation')
    };
    
    // Validar senhas
    if (data.password !== data.password_confirmation) {
        showError('As senhas não coincidem!');
        return;
    }
    
    try {
        showLoading('Criando conta...');
        
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            // Registro bem-sucedido
            localStorage.setItem('auth_token', result.token);
            currentUser = result.user;
            userPoints = result.user.pontos || 100; // Bônus de boas-vindas
            userLevel = calculateUserLevel(userPoints);
            
            showSuccess('Conta criada com sucesso! 🎉 Você ganhou 100 pontos de boas-vindas!');
            
            setTimeout(() => {
                window.location.href = '/profile-client.html';
            }, 2000);
            
        } else {
            throw new Error(result.message || 'Erro no cadastro');
        }
        
    } catch (error) {
        console.error('Erro no registro:', error);
        showError('Erro no cadastro: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Logout
function logout() {
    localStorage.removeItem('auth_token');
    currentUser = null;
    userPoints = 0;
    userLevel = 'Bronze';
    
    showSuccess('Logout realizado com sucesso!');
    setTimeout(() => {
        window.location.href = '/';
    }, 1000);
}

// Configurar navegação
function setupNavigation() {
    // Adicionar classes ativas nos links
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
}

// Funções de UI feedback
function showLoading(message = 'Carregando...') {
    // Criar overlay de loading se não existir
    let loadingDiv = document.getElementById('loading-overlay');
    if (!loadingDiv) {
        loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-overlay';
        loadingDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            font-size: 1.2rem;
        `;
        document.body.appendChild(loadingDiv);
    }
    loadingDiv.innerHTML = `<div>⏳ ${message}</div>`;
    loadingDiv.style.display = 'flex';
}

function hideLoading() {
    const loadingDiv = document.getElementById('loading-overlay');
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
}

function showSuccess(message) {
    showToast(message, 'success');
}

function showError(message) {
    showToast(message, 'error');
}

function showToast(message, type = 'info') {
    // Criar toast se não existir
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        `;
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        padding: 1rem 1.5rem;
        margin-bottom: 10px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        min-width: 300px;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#1e40af'};
    `;
    toast.textContent = message;
    
    toastContainer.appendChild(toast);
    
    // Remover toast após 5 segundos
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            toastContainer.removeChild(toast);
        }, 300);
    }, 5000);
}

// Adicionar estilos para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .nav-links.active {
        display: flex !important;
    }
    
    .menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    
    .menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }
    
    .menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }
`;
document.head.appendChild(style);

// Exportar funções para uso global
window.logout = logout;
window.showSuccess = showSuccess;
window.showError = showError;
window.showLoading = showLoading;
window.hideLoading = hideLoading;