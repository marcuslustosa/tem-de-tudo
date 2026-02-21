/**
 * SPA Components - Tem de Tudo
 * Todos os componentes/páginas do sistema baseados em papel do usuário
 */

// ====== COMPONENTES CLIENTE ====== //

/**
 * Dashboard do Cliente
 */
async function clienteDashboard() {
    try {
        const response = await window.spaAuth.fetchAuthenticated('/api/cliente/dashboard');
        if (!response) return '<div class="error">Erro de autenticação</div>';
        
        const data = await response.json();
        
        return `
            <div class="dashboard-container">
                <h1 class="page-title">
                    <i class="fas fa-home"></i>
                    Bem-vindo, ${window.spaAuth.getUser().nome}!
                </h1>
                
                <!-- Cards de Estatísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.pontos_totais || 0}</div>
                            <div class="stat-label">Pontos Totais</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.empresas_favoritas?.length || 0}</div>
                            <div class="stat-label">Empresas Favoritas</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.promocoes?.length || 0}</div>
                            <div class="stat-label">Promoções Disponíveis</div>
                        </div>
                    </div>
                </div>
                
                <!-- Empresas Favoritas -->
                ${data.empresas_favoritas?.length ? `
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-fire"></i>
                            Suas Empresas Favoritas
                        </h2>
                        <div class="companies-grid">
                            ${data.empresas_favoritas.map(empresa => `
                                <div class="company-card" onclick="spaRouter.navigate('/empresa/${empresa.id}')">
                                    <div class="company-logo">
                                        <i class="fas fa-${getEmpresaIcon(empresa.ramo)}"></i>
                                    </div>
                                    <div class="company-info">
                                        <div class="company-name">${empresa.nome}</div>
                                        <div class="company-category">${empresa.ramo}</div>
                                        <div class="company-points">${empresa.meus_pontos || 0} pontos</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <!-- Últimas Transações -->
                ${data.ultimas_transacoes?.length ? `
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-history"></i>
                            Últimas Transações
                        </h2>
                        <div class="transactions-list">
                            ${data.ultimas_transacoes.slice(0, 5).map(transacao => `
                                <div class="transaction-item">
                                    <div class="transaction-icon">
                                        <i class="fas fa-${transacao.tipo === 'ganho' ? 'plus' : 'minus'}"></i>
                                    </div>
                                    <div class="transaction-info">
                                        <div class="transaction-title">${transacao.empresa_nome}</div>
                                        <div class="transaction-date">${formatDate(transacao.data)}</div>
                                    </div>
                                    <div class="transaction-points ${transacao.tipo}">
                                        ${transacao.tipo === 'ganho' ? '+' : '-'}${transacao.pontos}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="text-center">
                            <button class="btn btn-outline" onclick="spaRouter.navigate('/historico')">
                                Ver todas as transações
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                <!-- Promoções Disponíveis -->
                ${data.promocoes?.length ? `
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-tags"></i>
                            Promoções em Destaque
                        </h2>
                        <div class="promotions-grid">
                            ${data.promocoes.slice(0, 6).map(promocao => `
                                <div class="promotion-card">
                                    <div class="promotion-discount">${promocao.desconto}% OFF</div>
                                    <div class="promotion-title">${promocao.titulo}</div>
                                    <div class="promotion-company">${promocao.empresa_nome}</div>
                                    <div class="promotion-cost">${promocao.custo_pontos} pontos</div>
                                    <button class="btn btn-sm btn-primary" onclick="resgatarPromocao(${promocao.id})">
                                        Resgatar
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                        <div class="text-center">
                            <button class="btn btn-outline" onclick="spaRouter.navigate('/promocoes')">
                                Ver todas as promoções
                            </button>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        return '<div class="error">Erro ao carregar dashboard</div>';
    }
}

/**
 * Buscar Empresas
 */
async function clienteBuscarEmpresas() {
    try {
        const response = await window.spaAuth.fetchAuthenticated('/api/cliente/empresas');
        if (!response) return '<div class="error">Erro de autenticação</div>';
        
        const empresas = await response.json();
        
        return `
            <div class="search-container">
                <h1 class="page-title">
                    <i class="fas fa-search"></i>
                    Buscar Empresas
                </h1>
                
                <!-- Barra de Busca -->
                <div class="search-bar">
                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar por nome, categoria..." oninput="filtrarEmpresas()">
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters">
                    <div class="filter-group">
                        <label>Categoria:</label>
                        <select id="categoryFilter" onchange="filtrarEmpresas()">
                            <option value="">Todas</option>
                            <option value="restaurante">Restaurante</option>
                            <option value="academia">Academia</option>
                            <option value="beleza">Beleza</option>
                            <option value="saude">Saúde</option>
                            <option value="varejo">Varejo</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lista de Empresas -->
                <div class="empresas-grid" id="empresasGrid">
                    ${empresas.map(empresa => `
                        <div class="empresa-card" data-category="${empresa.ramo}" data-name="${empresa.nome.toLowerCase()}">
                            <div class="empresa-header">
                                <div class="empresa-logo">
                                    <i class="fas fa-${getEmpresaIcon(empresa.ramo)}"></i>
                                </div>
                                <div class="empresa-rating">
                                    <i class="fas fa-star"></i>
                                    <span>${empresa.avaliacao_media || 4.5}</span>
                                </div>
                            </div>
                            <div class="empresa-info">
                                <h3 class="empresa-nome">${empresa.nome}</h3>
                                <p class="empresa-categoria">${empresa.ramo}</p>
                                <p class="empresa-endereco">${empresa.endereco}</p>
                                <div class="empresa-points">
                                    <i class="fas fa-coins"></i>
                                    <span>Você tem ${empresa.meus_pontos || 0} pontos</span>
                                </div>
                            </div>
                            <div class="empresa-actions">
                                <button class="btn btn-primary" onclick="spaRouter.navigate('/empresa/${empresa.id}')">
                                    Ver Detalhes
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <script>
                function filtrarEmpresas() {
                    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                    const categoryFilter = document.getElementById('categoryFilter').value;
                    const cards = document.querySelectorAll('.empresa-card');
                    
                    cards.forEach(card => {
                        const name = card.dataset.name;
                        const category = card.dataset.category;
                        
                        const matchesSearch = name.includes(searchTerm);
                        const matchesCategory = !categoryFilter || category === categoryFilter;
                        
                        card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
                    });
                }
            </script>
        `;
    } catch (error) {
        console.error('Erro ao carregar empresas:', error);
        return '<div class="error">Erro ao carregar empresas</div>';
    }
}

/**
 * Meu QR Code
 */
function clienteMeuQR() {
    const user = window.spaAuth.getUser();
    const qrData = `cliente:${user.id}:${Date.now()}`;
    
    return `
        <div class="qr-container">
            <h1 class="page-title">
                <i class="fas fa-qrcode"></i>
                Meu QR Code
            </h1>
            
            <div class="qr-card">
                <div class="qr-header">
                    <h2>Cartão de Fidelidade Digital</h2>
                    <p>Mostre este QR Code para a empresa escanear e ganhar pontos</p>
                </div>
                
                <div class="qr-display">
                    <div id="qrcode"></div>
                    <div class="qr-info">
                        <div class="user-name">${user.nome}</div>
                        <div class="user-id">ID: ${user.id}</div>
                    </div>
                </div>
                
                <div class="qr-instructions">
                    <h3>Como usar:</h3>
                    <ol>
                        <li>Mostre este QR Code para o atendente</li>
                        <li>Ele irá escanear com o sistema da empresa</li>
                        <li>Você ganhará pontos automaticamente</li>
                        <li>Acompanhe seu saldo no dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
        <script>
            // Gerar QR Code
            QRCode.toCanvas(document.getElementById('qrcode'), '${qrData}', {
                width: 200,
                height: 200,
                color: {
                    dark: '#6F1AB6',
                    light: '#FFFFFF'
                }
            });
        </script>
    `;
}

// ====== COMPONENTES EMPRESA ====== //

/**
 * Dashboard da Empresa
 */
async function empresaDashboard() {
    try {
        const response = await window.spaAuth.fetchAuthenticated('/api/empresa/dashboard');
        if (!response) return '<div class="error">Erro de autenticação</div>';
        
        const data = await response.json();
        
        return `
            <div class="dashboard-container">
                <h1 class="page-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard Empresa
                </h1>
                
                <!-- Cards de Estatísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.total_clientes || 0}</div>
                            <div class="stat-label">Clientes Cadastrados</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.pontos_hoje || 0}</div>
                            <div class="stat-label">Pontos Hoje</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.scans_hoje || 0}</div>
                            <div class="stat-label">Scans Hoje</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.promocoes_ativas || 0}</div>
                            <div class="stat-label">Promoções Ativas</div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Clientes -->
                ${data.top_clientes?.length ? `
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-trophy"></i>
                            Top 5 Clientes
                        </h2>
                        <div class="top-clients-list">
                            ${data.top_clientes.map((cliente, index) => `
                                <div class="client-item">
                                    <div class="client-rank">${index + 1}</div>
                                    <div class="client-avatar">${cliente.nome.charAt(0).toUpperCase()}</div>
                                    <div class="client-info">
                                        <div class="client-name">${cliente.nome}</div>
                                        <div class="client-points">${cliente.total_pontos} pontos</div>
                                    </div>
                                    <div class="client-visits">${cliente.visitas} visitas</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <!-- Últimas Transações -->
                ${data.ultimas_transacoes?.length ? `
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-history"></i>
                            Últimas Transações
                        </h2>
                        <div class="transactions-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Pontos</th>
                                        <th>Tipo</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.ultimas_transacoes.slice(0, 10).map(transacao => `
                                        <tr>
                                            <td>${transacao.cliente_nome}</td>
                                            <td class="${transacao.tipo}">${transacao.pontos}</td>
                                            <td>${transacao.tipo}</td>
                                            <td>${formatDate(transacao.data)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        return '<div class="error">Erro ao carregar dashboard da empresa</div>';
    }
}

// ====== COMPONENTES ADMIN ====== //

/**
 * Dashboard do Admin
 */
async function adminDashboard() {
    try {
        const response = await window.spaAuth.fetchAuthenticated('/api/admin/dashboard');
        if (!response) return '<div class="error">Erro de autenticação</div>';
        
        const data = await response.json();
        
        return `
            <div class="dashboard-container">
                <h1 class="page-title">
                    <i class="fas fa-chart-line"></i>
                    Dashboard Administrativo
                </h1>
                
                <!-- Cards de Estatísticas Gerais -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.total_usuarios || 0}</div>
                            <div class="stat-label">Usuários Totais</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.total_empresas || 0}</div>
                            <div class="stat-label">Empresas Ativas</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.total_transacoes || 0}</div>
                            <div class="stat-label">Transações Totais</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${data.pontos_circulacao || 0}</div>
                            <div class="stat-label">Pontos em Circulação</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos e Métricas -->
                <div class="charts-section">
                    <div class="chart-card">
                        <h3>Crescimento de Usuários (Últimos 7 dias)</h3>
                        <canvas id="userGrowthChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-card">
                        <h3>Transações por Empresa</h3>
                        <canvas id="transactionsChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Atividades Recentes -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i>
                        Atividades Recentes
                    </h2>
                    <div class="activities-list">
                        ${data.atividades_recentes?.map(atividade => `
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-${getActivityIcon(atividade.tipo)}"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">${atividade.descricao}</div>
                                    <div class="activity-time">${formatDateTime(atividade.data)}</div>
                                </div>
                            </div>
                        `).join('') || '<p>Nenhuma atividade recente</p>'}
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar dashboard admin:', error);
        return '<div class="error">Erro ao carregar dashboard administrativo</div>';
    }
}

// ====== FUNÇÕES AUXILIARES ====== //

/**
 * Obter ícone para empresa baseado no ramo
 */
function getEmpresaIcon(ramo) {
    const icons = {
        'restaurante': 'utensils',
        'academia': 'dumbbell',
        'beleza': 'cut',
        'saude': 'heartbeat',
        'varejo': 'shopping-bag',
        'servicos': 'tools',
        'educacao': 'graduation-cap',
        'tecnologia': 'laptop'
    };
    return icons[ramo] || 'store';
}

/**
 * Obter ícone para atividade
 */
function getActivityIcon(tipo) {
    const icons = {
        'login': 'sign-in-alt',
        'cadastro': 'user-plus',
        'transacao': 'exchange-alt',
        'promocao': 'tags',
        'avaliacao': 'star'
    };
    return icons[tipo] || 'info-circle';
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

/**
 * Formatar data e hora
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR');
}

/**
 * Resgatar promoção
 */
async function resgatarPromocao(promocaoId) {
    try {
        const response = await window.spaAuth.fetchAuthenticated(`/api/cliente/promocoes/${promocaoId}/resgatar`, {
            method: 'POST'
        });
        
        if (response.ok) {
            const result = await response.json();
            alert(`Promoção resgatada! Código: ${result.codigo_cupom}`);
            // Recarregar página
            spaRouter.handleRouteChange();
        } else {
            const error = await response.json();
            alert(error.message || 'Erro ao resgatar promoção');
        }
    } catch (error) {
        console.error('Erro ao resgatar promoção:', error);
        alert('Erro ao resgatar promoção');
    }
}

// ====== EXPORTAR COMPONENTES ====== //

window.spaComponents = {
    // Cliente
    clienteDashboard,
    clienteBuscarEmpresas,
    clienteMeuQR,
    
    // Empresa  
    empresaDashboard,
    
    // Admin
    adminDashboard,
    
    // Funções auxiliares
    getEmpresaIcon,
    getActivityIcon,
    formatDate,
    formatDateTime,
    resgatarPromocao
};