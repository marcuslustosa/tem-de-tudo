/**
 * SPA Main App - Tem de Tudo
 * Inicializa e coordena toda a aplicação SPA
 */

class SPAApp {
    constructor() {
        this.isInitialized = false;
        this.currentUserRole = null;
    }
    
    /**
     * Inicializar aplicação
     */
    async init() {
        console.log('🚀 Inicializando SPA...');
        
        // Mostrar loading
        this.showLoading();
        
        // Verificar autenticação
        if (!window.spaAuth.isAuthenticated()) {
            console.log('❌ Usuário não autenticado, redirecionando...');
            window.location.href = '/entrar.html';
            return;
        }
        
        // Obter role do usuário
        this.currentUserRole = window.spaAuth.getUserRole();
        console.log('👤 Usuário autenticado como:', this.currentUserRole);
        
        // Registrar rotas baseadas no role
        this.registerRoutes();
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Atualizar interface
        window.spaAuth.updateUserInterface();
        
        // Esconder loading
        this.hideLoading();
        
        // Marcar como inicializado
        this.isInitialized = true;
        
        console.log('✅ SPA inicializado com sucesso!');
    }
    
    /**
     * Registrar rotas baseadas no papel do usuário
     */
    registerRoutes() {
        const routes = this.getRoutesForRole(this.currentUserRole);
        
        // Registrar cada rota
        Object.entries(routes).forEach(([path, component]) => {
            window.spaRouter.register(path, component, true);
        });
        
        // Definir rota padrão
        window.spaRouter.setDefault('/');
        
        console.log(`📍 ${Object.keys(routes).length} rotas registradas para ${this.currentUserRole}`);
    }
    
    /**
     * Obter rotas para um papel específico
     */
    getRoutesForRole(role) {
        const allRoutes = {
            cliente: {
                '/': window.spaComponents.clienteDashboard,
                '/buscar': window.spaComponents.clienteBuscarEmpresas,
                '/meu-qr': window.spaComponents.clienteMeuQR,
                '/scanner': () => this.renderScanner('cliente'),
                '/promocoes': () => this.renderPromocoes(),
                '/historico': () => this.renderHistorico(),
                '/avaliacoes': () => this.renderAvaliacoes(),
                '/perfil': () => this.renderPerfil(),
                '/ajuda': () => this.renderAjuda()
            },
            empresa: {
                '/': window.spaComponents.empresaDashboard,
                '/scanner-cliente': () => this.renderScanner('empresa'),
                '/clientes': () => this.renderClientes(),
                '/promocoes': () => this.renderGerenciarPromocoes(),
                '/qrcodes': () => this.renderQRCodes(),
                '/avaliacoes': () => this.renderAvaliacoesEmpresa(),
                '/relatorios': () => this.renderRelatorios(),
                '/perfil': () => this.renderPerfilEmpresa()
            },
            admin: {
                '/': window.spaComponents.adminDashboard,
                '/usuarios': () => this.renderGerenciarUsuarios(),
                '/empresas': () => this.renderGerenciarEmpresas(),
                '/checkins': () => this.renderAprovarCheckins(),
                '/relatorios': () => this.renderRelatoriosAdmin(),
                '/configuracoes': () => this.renderConfiguracoes(),
                '/logs': () => this.renderLogs()
            }
        };
        
        return allRoutes[role] || allRoutes.cliente;
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sideNav = document.getElementById('sideNav');
        const mainContent = document.getElementById('mainContent');
        
        if (menuToggle && sideNav) {
            menuToggle.addEventListener('click', () => {
                sideNav.classList.toggle('open');
                mainContent.classList.toggle('nav-open');
            });
        }
        
        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                if (confirm('Deseja sair da sua conta?')) {
                    window.spaAuth.logout();
                }
            });
        }
        
        // Fechar menu ao clicar fora (mobile)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                const isClickInsideNav = sideNav.contains(e.target);
                const isMenuToggle = menuToggle.contains(e.target);
                
                if (!isClickInsideNav && !isMenuToggle && sideNav.classList.contains('open')) {
                    sideNav.classList.remove('open');
                    mainContent.classList.remove('nav-open');
                }
            }
        });
        
        console.log('🎛️ Event listeners configurados');
    }
    
    /**
     * Mostrar loading
     */
    showLoading() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.remove('hidden');
        }
    }
    
    /**
     * Esconder loading
     */
    hideLoading() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            setTimeout(() => {
                loadingScreen.classList.add('hidden');
            }, 500);
        }
    }
    
    // ====== COMPONENTES DINÂMICOS ====== //
    
    /**
     * Renderizar scanner
     */
    renderScanner(tipo) {
        if (tipo === 'cliente') {
            return `
                <div class="scanner-container">
                    <h1 class="page-title">
                        <i class="fas fa-camera"></i>
                        Scanner de QR Code
                    </h1>
                    
                    <div class="scanner-card">
                        <div class="scanner-preview">
                            <video id="scanner-video" autoplay></video>
                            <div class="scanner-overlay">
                                <div class="scanner-frame"></div>
                            </div>
                        </div>
                        
                        <div class="scanner-instructions">
                            <h3>Como usar:</h3>
                            <ol>
                                <li>Posicione o QR Code da empresa no centro da tela</li>
                                <li>Aguarde a leitura automática</li>
                                <li>Seus pontos serão creditados automaticamente</li>
                            </ol>
                        </div>
                        
                        <div class="scanner-actions">
                            <button class="btn btn-primary" onclick="iniciarScanner()">
                                <i class="fas fa-camera"></i>
                                Iniciar Scanner
                            </button>
                            <button class="btn btn-secondary" onclick="pararScanner()">
                                <i class="fas fa-stop"></i>
                                Parar
                            </button>
                        </div>
                    </div>
                </div>
                
                <script>
                    async function iniciarScanner() {
                        try {
                            const video = document.getElementById('scanner-video');
                            const stream = await navigator.mediaDevices.getUserMedia({ 
                                video: { facingMode: 'environment' } 
                            });
                            video.srcObject = stream;
                        } catch (error) {
                            alert('Erro ao acessar câmera: ' + error.message);
                        }
                    }
                    
                    function pararScanner() {
                        const video = document.getElementById('scanner-video');
                        const stream = video.srcObject;
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                    }
                </script>
            `;
        } else {
            return `
                <div class="scanner-container">
                    <h1 class="page-title">
                        <i class="fas fa-camera"></i>
                        Scanner Cliente
                    </h1>
                    
                    <div class="scanner-card">
                        <p>Escaneie o QR Code do cliente para dar pontos</p>
                        
                        <div class="scanner-preview">
                            <video id="scanner-video-empresa" autoplay></video>
                            <div class="scanner-overlay">
                                <div class="scanner-frame"></div>
                            </div>
                        </div>
                        
                        <div class="scanner-actions">
                            <button class="btn btn-primary" onclick="iniciarScannerEmpresa()">
                                <i class="fas fa-camera"></i>
                                Iniciar Scanner
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * Renderizar promoções (placeholder)
     */
    renderPromocoes() {
        return `
            <div class="page-container">
                <h1 class="page-title">
                    <i class="fas fa-tags"></i>
                    Promoções Disponíveis
                </h1>
                
                <div class="placeholder-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando promoções...</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar histórico
     */
    renderHistorico() {
        return `
            <div class="page-container">
                <h1 class="page-title">
                    <i class="fas fa-history"></i>
                    Histórico de Pontos
                </h1>
                
                <div class="placeholder-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando histórico...</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar avaliações
     */
    renderAvaliacoes() {
        return `
            <div class="page-container">
                <h1 class="page-title">
                    <i class="fas fa-star"></i>
                    Minhas Avaliações
                </h1>
                
                <div class="placeholder-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando avaliações...</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar perfil
     */
    renderPerfil() {
        const user = window.spaAuth.getUser();
        return `
            <div class="page-container">
                <h1 class="page-title">
                    <i class="fas fa-user"></i>
                    Meu Perfil
                </h1>
                
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            ${user.nome.charAt(0).toUpperCase()}
                        </div>
                        <div class="profile-info">
                            <h2>${user.nome}</h2>
                            <p>${user.email}</p>
                            <span class="user-role">${user.tipo}</span>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-label">Pontos Totais</div>
                            <div class="stat-value">${user.pontos_totais || 0}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Membro desde</div>
                            <div class="stat-value">${window.spaComponents.formatDate(user.created_at)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar ajuda
     */
    renderAjuda() {
        return `
            <div class="page-container">
                <h1 class="page-title">
                    <i class="fas fa-headset"></i>
                    Central de Ajuda
                </h1>
                
                <div class="help-content">
                    <div class="help-section">
                        <h3>Como funciona o sistema?</h3>
                        <p>O Tem de Tudo é um sistema de fidelidade onde você acumula pontos comprando em estabelecimentos parceiros.</p>
                    </div>
                    
                    <div class="help-section">
                        <h3>Como ganhar pontos?</h3>
                        <ul>
                            <li>Escaneie o QR Code da empresa após uma compra</li>
                            <li>Mostre seu QR Code para o atendente escanear</li>
                            <li>Participe de promoções especiais</li>
                        </ul>
                    </div>
                    
                    <div class="help-section">
                        <h3>Como usar os pontos?</h3>
                        <p>Você pode resgatar seus pontos por cupons de desconto nas empresas parceiras.</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    // ====== PLACEHOLDERS PARA OUTRAS FUNCIONALIDADES ====== //
    
    renderClientes() {
        return '<div class="placeholder-content"><i class="fas fa-users"></i><p>Lista de Clientes em desenvolvimento...</p></div>';
    }
    
    renderGerenciarPromocoes() {
        return '<div class="placeholder-content"><i class="fas fa-tags"></i><p>Gerenciar Promoções em desenvolvimento...</p></div>';
    }
    
    renderQRCodes() {
        return '<div class="placeholder-content"><i class="fas fa-qrcode"></i><p>QR Codes da Empresa em desenvolvimento...</p></div>';
    }
    
    renderAvaliacoesEmpresa() {
        return '<div class="placeholder-content"><i class="fas fa-star"></i><p>Avaliações da Empresa em desenvolvimento...</p></div>';
    }
    
    renderRelatorios() {
        return '<div class="placeholder-content"><i class="fas fa-chart-bar"></i><p>Relatórios em desenvolvimento...</p></div>';
    }
    
    renderPerfilEmpresa() {
        return '<div class="placeholder-content"><i class="fas fa-building"></i><p>Perfil da Empresa em desenvolvimento...</p></div>';
    }
    
    renderGerenciarUsuarios() {
        return '<div class="placeholder-content"><i class="fas fa-users-cog"></i><p>Gerenciar Usuários em desenvolvimento...</p></div>';
    }
    
    renderGerenciarEmpresas() {
        return '<div class="placeholder-content"><i class="fas fa-building"></i><p>Gerenciar Empresas em desenvolvimento...</p></div>';
    }
    
    renderAprovarCheckins() {
        return '<div class="placeholder-content"><i class="fas fa-check-circle"></i><p>Aprovar Check-ins em desenvolvimento...</p></div>';
    }
    
    renderRelatoriosAdmin() {
        return '<div class="placeholder-content"><i class="fas fa-chart-line"></i><p>Relatórios Admin em desenvolvimento...</p></div>';
    }
    
    renderConfiguracoes() {
        return '<div class="placeholder-content"><i class="fas fa-cog"></i><p>Configurações em desenvolvimento...</p></div>';
    }
    
    renderLogs() {
        return '<div class="placeholder-content"><i class="fas fa-list"></i><p>Logs do Sistema em desenvolvimento...</p></div>';
    }
}

// ====== INICIALIZAR APLICAÇÃO ====== //

// Aguardar carregamento do DOM
document.addEventListener('DOMContentLoaded', async () => {
    console.log('📄 DOM carregado, inicializando SPA...');
    
    // Criar instância da aplicação
    window.spaApp = new SPAApp();
    
    // Inicializar
    await window.spaApp.init();
});

// Log de debug
console.log('📦 SPA App carregado');