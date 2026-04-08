<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\BonusAdesaoController;
use App\Http\Controllers\CartaoFidelidadeController;
use App\Http\Controllers\PromocaoController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaPromocaoController;
use App\Http\Controllers\API\ClienteAPIController;
use App\Http\Controllers\API\EmpresaAPIController;
use App\Http\Controllers\SetupController;

// NOVOS CONTROLLERS - SISTEMA COMPLETO
use App\Http\Controllers\API\ProdutoController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\CheckInController as MainCheckInController;
use App\Http\Controllers\PontosController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminSettingsController;

// Debug route (remover em produÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o)
Route::get('/debug', function () {
    try {
        $dbConnection = DB::connection();
        $dbConnection->getPdo();

        return response()->json([
            'status' => 'OK',
            'message' => 'API funcionando',
            'database' => [
                'connection' => config('database.default'),
                'status' => 'connected'
            ],
            'timestamp' => now(),
            'environment' => app()->environment()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Erro na API',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Debug - teste de empresas (TEMPORÃƒÆ’Ã†â€™Ãƒâ€šÃ‚ÂRIO)
Route::get('/debug/empresas', function () {
    try {
        $count = \App\Models\Empresa::count();
        $empresas = \App\Models\Empresa::where('ativo', true)->take(3)->get(['id', 'nome', 'categoria']);
        
        return response()->json([
            'status' => 'OK',
            'total_empresas' => $count,
            'empresas_sample' => $empresas,
            'message' => 'Empresas carregadas com sucesso'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Erro ao carregar empresas: ' . $e->getMessage()
        ], 500);
    }
});

// Push Notifications
Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
    Route::post('/push/test', [PushSubscriptionController::class, 'test']);

    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // NotificaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes internas
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// Setup database manual (APENAS PRODUÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢O - RENDER)
Route::get('/setup-database', [SetupController::class, 'setupDatabase']);

// ============================================
// ROTAS PÃƒÆ’Ã†â€™Ãƒâ€¦Ã‚Â¡BLICAS (SEM AUTENTICAÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢O)
// ============================================

// AutenticaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Empresas (leitura pública) - COM CACHE
Route::get('/empresas', [EmpresaController::class, 'listEmpresas'])
    ->middleware('cache.response:300'); // 5 minutos
Route::get('/empresas/{id}', [EmpresaController::class, 'getEmpresa'])
    ->middleware('cache.response:600'); // 10 minutos
Route::get('/empresas/{id}/promocoes', [EmpresaController::class, 'getEmpresaPromocoes'])
    ->middleware('cache.response:600'); // 10 minutos

// Produtos das empresas (leitura pública) - COM CACHE
Route::get('/empresas/{empresaId}/produtos', [ProdutoController::class, 'index'])
    ->middleware('cache.response:600'); // 10 minutos
Route::get('/empresas/{empresaId}/produtos/{id}', [ProdutoController::class, 'show'])
    ->middleware('cache.response:600'); // 10 minutos

// Badges (informações públicas) - COM CACHE
Route::get('/badges', [BadgeController::class, 'index'])
    ->middleware('cache.response:1800'); // 30 minutos
Route::get('/badges/{id}', [BadgeController::class, 'show'])
    ->middleware('cache.response:1800'); // 30 minutos
Route::get('/badges/ranking', [BadgeController::class, 'ranking'])
    ->middleware('cache.response:1800'); // 30 minutos

// Webhook do Mercado Pago (pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºblico)
Route::post('/webhook/mercadopago', [PagamentoController::class, 'webhook'])->name('webhook.mercadopago');

// ============================================
// ROTAS PROTEGIDAS (REQUER AUTENTICAÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢O)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // AutenticaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o e Perfil
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
    Route::get('/auth/me', [AuthController::class, 'user']);
    Route::put('/perfil', [AuthController::class, 'updateProfile']);
    Route::put('/usuario/atualizar', [AuthController::class, 'updateProfile']);
    
    // ========== SISTEMA VIP E BADGES ==========
    Route::get('/badges/meus', [BadgeController::class, 'meusBadges']);
    Route::post('/badges/verificar-novos', [BadgeController::class, 'verificarNovos']);
    Route::get('/badges/progresso', [BadgeController::class, 'progresso']);
    
    // ========== PAGAMENTOS MERCADO PAGO ==========
    Route::post('/pagamentos/pix', [PagamentoController::class, 'criarPagamentoPix']);
    Route::get('/pagamentos/meus', [PagamentoController::class, 'meusPagamentos']);
    Route::get('/pagamentos/{id}/status', [PagamentoController::class, 'consultarStatus']);
    Route::post('/pagamentos/{id}/cancelar', [PagamentoController::class, 'cancelar']);
    
    // ========== SISTEMA CHECK-IN QR CODE ==========
    Route::post('/checkin/fazer', [MainCheckInController::class, 'fazerCheckIn']);
    Route::get('/checkin/historico', [MainCheckInController::class, 'meuHistorico']);
    Route::post('/checkin/validar-qr', [MainCheckInController::class, 'validarQRCode']);
    
    // ========== ROTAS PARA EMPRESAS ==========
    Route::post('/empresa/qrcode/gerar', [MainCheckInController::class, 'gerarQRCode']);
    Route::get('/empresa/checkins', [MainCheckInController::class, 'checkinsEmpresa']);
    Route::get('/empresa/pagamentos/estatisticas', [PagamentoController::class, 'estatisticasEmpresa']);

});

// Rotas pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºblicas de autenticaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o

// Middleware JavaScript para proteÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de rotas por perfil
Route::get('/auth/check-access', function () {
    return response()->json(['message' => 'Access check endpoint']);
});

// Rotas de admin com JWT
Route::prefix('admin')->group(function () {
    // Login de admin (pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºblico)
    Route::post('/login', [AuthController::class, 'adminLogin']);
    
    // Rotas protegidas por JWT
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'adminLogout']);
        Route::get('/me', [AuthController::class, 'adminProfile']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        
        // Rotas que requerem permissÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes especÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ficas
        Route::middleware(['admin.permission:create_users'])->group(function () {
            Route::post('/create-user', [AuthController::class, 'createUser']);
        });
        
        Route::middleware(['admin.permission:manage_users'])->group(function () {
            Route::get('/users', [AuthController::class, 'listUsers']);
            Route::put('/users/{id}/status', [AuthController::class, 'updateUserStatus']);
        });
        
        Route::middleware(['admin.permission:view_reports'])->group(function () {
            Route::get('/audit-logs', [AdminReportController::class, 'getAuditLogs']);
            Route::get('/login-stats', [AdminReportController::class, 'getLoginStats']);
            Route::get('/system-stats', [AdminReportController::class, 'getSystemStats']);
            Route::get('/security-events', [AdminReportController::class, 'getSecurityEvents']);
        });
        
        Route::middleware(['admin.permission:manage_system'])->group(function () {
            Route::post('/cleanup-logs', [AdminReportController::class, 'cleanupLogs']);
        });

        // NotificaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes administrativas
        Route::middleware(['admin.permission:manage_users'])->group(function () {
            Route::post('/notifications/broadcast', [NotificationController::class, 'sendBroadcast']);
            Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
            Route::get('/notifications/stats', [NotificationController::class, 'getStats']);
            Route::post('/notifications/process-queue', [NotificationController::class, 'processQueue']);
        });

        // NotificaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes do admin
        Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
        Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
        Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
    });
});

// Rotas protegidas por Sanctum (usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios regulares)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/add-pontos', [AuthController::class, 'addPontos']);

    //Route::get('/empresas', [EmpresaController::class, 'index']);
    //Route::post('/empresas', [EmpresaController::class, 'store']);

    // NotificaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
    Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
});

// Rotas especÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ficas por perfil
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->prefix('cliente')->group(function () {
    // QR Code do Cliente
    Route::get('/meu-qrcode', [ClienteAPIController::class, 'meuQRCode']);
    
    // Dashboard do Cliente
    Route::get('/dashboard', [ClienteAPIController::class, 'dashboard']);
    
    // Empresas
    Route::get('/empresas', [ClienteAPIController::class, 'listarEmpresas']);
    Route::get('/empresas/{id}', [ClienteAPIController::class, 'empresaDetalhes']);
    
    // QR Code
    Route::post('/escanear-qrcode', [ClienteAPIController::class, 'escanearQRCode']);
    
    // PromoÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes
    Route::get('/promocoes', [ClienteAPIController::class, 'listarPromocoes']);
    Route::post('/resgatar-promocao/{id}', [ClienteAPIController::class, 'resgatarPromocao']);
    Route::post('/promocoes/{id}/resgatar', [ClienteAPIController::class, 'resgatarPromocao']);
    
    // AvaliaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes
    Route::post('/avaliar', [ClienteAPIController::class, 'avaliar']);
    
    // HistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rico
    Route::get('/historico-pontos', [ClienteAPIController::class, 'historicoPontos']);
    
    // Legacy route (manter compatibilidade)
    Route::get('/dashboard-data', [AuthController::class, 'clienteDashboard']);
});

Route::middleware(['auth:sanctum', 'role.permission:empresa'])->prefix('empresa')->group(function () {
    // Escanear QR do Cliente
    Route::post('/escanear-cliente', [EmpresaAPIController::class, 'escanearCliente']);
    
    // Dashboard da Empresa
    Route::get('/dashboard', [EmpresaAPIController::class, 'dashboard']);

    // Perfil da empresa
    Route::get('/perfil', [EmpresaAPIController::class, 'meuPerfil']);
    Route::put('/perfil', [EmpresaAPIController::class, 'atualizarPerfil']);
    
    // Clientes
    Route::get('/clientes', [EmpresaAPIController::class, 'clientes']);
    
    // Produtos
    Route::post('/produtos', [ProdutoController::class, 'store']);
    Route::put('/produtos/{id}', [ProdutoController::class, 'update']);
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy']);
    
    // PromoÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes
    Route::get('/promocoes', [EmpresaAPIController::class, 'promocoes']);
    Route::post('/promocoes', [EmpresaAPIController::class, 'criarPromocao']);
    Route::put('/promocoes/{id}', [EmpresaAPIController::class, 'atualizarPromocao']);
    Route::delete('/promocoes/{id}', [EmpresaAPIController::class, 'deletarPromocao']);
    Route::patch('/promocoes/{id}/ativar', [EmpresaAPIController::class, 'ativarPromocao']);
    Route::patch('/promocoes/{id}/pausar', [EmpresaAPIController::class, 'pausarPromocao']);
    Route::get('/resgates', [EmpresaAPIController::class, 'resgates']);
    
    // QR Codes
    Route::get('/qrcodes', [EmpresaAPIController::class, 'qrCodes']);
    
    // AvaliaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes
    Route::get('/avaliacoes', [EmpresaAPIController::class, 'avaliacoes']);
    
    // RelatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rios
    Route::get('/relatorio-pontos', [EmpresaAPIController::class, 'relatorioPontos']);
    
    // Legacy routes (manter compatibilidade)
    Route::get('/dashboard-stats', [EmpresaController::class, 'dashboardStats']);
    Route::get('/recent-checkins', [EmpresaController::class, 'recentCheckins']);
    Route::get('/top-clients', [EmpresaController::class, 'topClients']);
});

Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin')->group(function () {
    // Rotas exclusivas para administradores
    Route::get('/dashboard-stats', [AdminReportController::class, 'dashboardStats']);
    Route::get('/recent-activity', [AdminReportController::class, 'recentActivity']);
    Route::get('/users-report', [AdminReportController::class, 'getUsersReport']);
    Route::get('/reports/export', [AdminReportController::class, 'exportResumoCsv']);
    Route::get('/pontos/estatisticas', [PontosController::class, 'estatisticas']);
    Route::get('/content', [AdminContentController::class, 'index']);
    Route::post('/content/banners', [AdminContentController::class, 'storeBanner']);
    Route::put('/content/banners/{banner}', [AdminContentController::class, 'updateBanner']);
    Route::delete('/content/banners/{banner}', [AdminContentController::class, 'destroyBanner']);
    Route::post('/content/categorias', [AdminContentController::class, 'storeCategoria']);
    Route::put('/content/categorias/{categoria}', [AdminContentController::class, 'updateCategoria']);
    Route::delete('/content/categorias/{categoria}', [AdminContentController::class, 'destroyCategoria']);
    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);
});

// Rotas do sistema de pontos (protegidas por Sanctum)
Route::middleware('auth:sanctum')->prefix('pontos')->group(function () {
    // Check-in do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio
    Route::post('/checkin', [PontosController::class, 'checkin']);
    
    // Resgatar pontos por recompensas
    Route::post('/resgatar', [PontosController::class, 'resgatarPontos']);
    
    // Dados do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio (pontos, nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­vel, etc.)
    Route::get('/meus-dados', [PontosController::class, 'meusDados']);
    
    // HistÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rico de pontos
    Route::get('/historico', [PontosController::class, 'historicoPontos']);
    
    // Cupons do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio
    Route::get('/meus-cupons', [PontosController::class, 'meusCupons']);
    
    // Usar um cupom
    Route::post('/usar-cupom/{cupom}', [PontosController::class, 'usarCupom']);
});

// Rotas administrativas do sistema de pontos (protegidas por JWT e admin)
Route::middleware(['auth:sanctum'])->prefix('admin/pontos')->group(function () {
    // Aprovar/rejeitar check-ins
    Route::post('/checkin/{checkin}/aprovar', [PontosController::class, 'aprovarCheckin'])
        ->middleware(['admin.permission:manage_checkins']);
    
    // Check-ins pendentes
    Route::get('/checkins-pendentes', [PontosController::class, 'checkinsPendentes'])
        ->middleware(['admin.permission:view_checkins']);
    
    // EstatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­sticas do sistema
    Route::get('/estatisticas', [PontosController::class, 'estatisticas'])
        ->middleware(['admin.permission:view_reports']);
});

// Rotas do sistema QR Code
Route::prefix('qrcode')->group(function () {
    // Escanear QR Code (pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºblico - usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/scan', [QRCodeController::class, 'scanQR']);
        Route::post('/checkin', [QRCodeController::class, 'checkinViaQR']);
    });
    
    // Rotas administrativas de QR Code
    Route::middleware(['auth:sanctum'])->group(function () {
        // Gerar QR Code para estabelecimento
        Route::post('/generate', [QRCodeController::class, 'generateQR'])
            ->middleware(['admin.permission:manage_qrcodes']);
        
        // Atualizar ofertas do QR Code
        Route::put('/{qrcode}/offers', [QRCodeController::class, 'updateOffers'])
            ->middleware(['admin.permission:manage_qrcodes']);
        
        // EstatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­sticas de uso do QR Code
        Route::get('/{qrcode}/stats', [QRCodeController::class, 'getQRStats'])
            ->middleware(['admin.permission:view_reports']);
        
        // Listar QR Codes do estabelecimento
        Route::get('/list', [QRCodeController::class, 'listQRCodes'])
            ->middleware(['admin.permission:view_qrcodes']);
    });
});

// Rotas do sistema de descontos
Route::prefix('discounts')->group(function () {
    // Consultar descontos disponÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­veis (pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºblico - usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/company/{empresa_id}', [DiscountController::class, 'getCompanyDiscountLevels']);
        Route::post('/calculate', [DiscountController::class, 'calculateUserDiscount']);
        Route::post('/apply', [DiscountController::class, 'applyDiscount']);
    });
    
    // Rotas administrativas de descontos
    Route::middleware(['auth:sanctum'])->group(function () {
        // Configurar nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­veis de desconto
        Route::post('/configure', [DiscountController::class, 'configureCompanyDiscounts'])
            ->middleware(['admin.permission:manage_discounts']);
        
        // Buscar cliente para aplicar desconto
        Route::post('/find-customer', [DiscountController::class, 'findCustomerForDiscount'])
            ->middleware(['admin.permission:manage_discounts']);
    });

    // Rotas OpenAI (admin apenas)
    Route::prefix('openai')->group(function () {
        Route::get('/status', [OpenAIController::class, 'status']);
        Route::get('/test', [OpenAIController::class, 'test'])
            ->middleware(['admin.permission:manage_system']);
        Route::post('/chat', [OpenAIController::class, 'chat'])
            ->middleware(['admin.permission:manage_system']);
        Route::post('/suggest', [OpenAIController::class, 'suggest'])
            ->middleware(['admin.permission:manage_system']);
    });
});
