<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\BonusAdesaoController;
use App\Http\Controllers\CartaoFidelidadeController;


// Debug route (remover em produção)
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

// Rotas públicas de autenticação
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Middleware JavaScript para proteção de rotas por perfil
Route::get('/auth/check-access', function () {
    return response()->json(['message' => 'Access check endpoint']);
});

// Rota para listar empresas (pública para funcionários se cadastrarem)
Route::get('/empresas', [EmpresaController::class, 'listEmpresas']);

// Rotas de admin com JWT
Route::prefix('admin')->group(function () {
    // Login de admin (público)
    Route::post('/login', [AuthController::class, 'adminLogin']);
    
    // Rotas protegidas por JWT
    Route::middleware(['sanctum.auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'adminLogout']);
        Route::get('/me', [AuthController::class, 'adminProfile']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        
        // Rotas que requerem permissões específicas
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
            Route::get('/users-report', [AdminReportController::class, 'getUsersReport']);
        });
        
        Route::middleware(['admin.permission:manage_system'])->group(function () {
            Route::post('/cleanup-logs', [AdminReportController::class, 'cleanupLogs']);
        });

        // Notificações administrativas
        Route::middleware(['admin.permission:manage_users'])->group(function () {
            Route::post('/notifications/broadcast', [NotificationController::class, 'sendBroadcast']);
            Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
            Route::get('/notifications/stats', [NotificationController::class, 'getStats']);
            Route::post('/notifications/process-queue', [NotificationController::class, 'processQueue']);
        });

        // Notificações do admin
        Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
        Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
        Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
    });
});

// Rotas protegidas por Sanctum (usuários regulares)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/add-pontos', [AuthController::class, 'addPontos']);

    Route::get('/empresas', [EmpresaController::class, 'index']);
    Route::post('/empresas', [EmpresaController::class, 'store']);

    // Notificações de usuários
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
    Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
});

// Rotas específicas por perfil
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->prefix('cliente')->group(function () {
    // Rotas exclusivas para clientes
    Route::get('/dashboard-data', [AuthController::class, 'clienteDashboard']);
});

Route::middleware(['auth:sanctum', 'role.permission:empresa'])->prefix('empresa')->group(function () {
    // Rotas exclusivas para empresas
    Route::get('/dashboard-stats', [EmpresaController::class, 'dashboardStats']);
    Route::get('/recent-checkins', [EmpresaController::class, 'recentCheckins']);
    Route::get('/top-clients', [EmpresaController::class, 'topClients']);
});

Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin')->group(function () {
    // Rotas exclusivas para administradores
    Route::get('/dashboard-stats', [AdminReportController::class, 'dashboardStats']);
    Route::get('/recent-activity', [AdminReportController::class, 'recentActivity']);
});

// Importar o controller de pontos
use App\Http\Controllers\PontosController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\DiscountController;

// Rotas do sistema de pontos (protegidas por Sanctum)
Route::middleware('auth:sanctum')->prefix('pontos')->group(function () {
    // Check-in do usuário
    Route::post('/checkin', [PontosController::class, 'checkin']);
    
    // Resgatar pontos por recompensas
    Route::post('/resgatar', [PontosController::class, 'resgatarPontos']);
    
    // Dados do usuário (pontos, nível, etc.)
    Route::get('/meus-dados', [PontosController::class, 'meusDados']);
    
    // Histórico de pontos
    Route::get('/historico', [PontosController::class, 'historicoPontos']);
    
    // Cupons do usuário
    Route::get('/meus-cupons', [PontosController::class, 'meusCupons']);
    
    // Usar um cupom
    Route::post('/usar-cupom/{cupom}', [PontosController::class, 'usarCupom']);
});

// Rotas administrativas do sistema de pontos (protegidas por JWT e admin)
Route::middleware(['sanctum.auth'])->prefix('admin/pontos')->group(function () {
    // Aprovar/rejeitar check-ins
    Route::post('/checkin/{checkin}/aprovar', [PontosController::class, 'aprovarCheckin'])
        ->middleware(['admin.permission:manage_checkins']);
    
    // Check-ins pendentes
    Route::get('/checkins-pendentes', [PontosController::class, 'checkinsPendentes'])
        ->middleware(['admin.permission:view_checkins']);
    
    // Estatísticas do sistema
    Route::get('/estatisticas', [PontosController::class, 'estatisticas'])
        ->middleware(['admin.permission:view_reports']);
});

// Rotas do sistema QR Code
Route::prefix('qrcode')->group(function () {
    // Escanear QR Code (público - usuários logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/scan', [QRCodeController::class, 'scanQR']);
        Route::post('/checkin', [QRCodeController::class, 'checkinViaQR']);
    });
    
    // Rotas administrativas de QR Code
    Route::middleware(['sanctum.auth'])->group(function () {
        // Gerar QR Code para estabelecimento
        Route::post('/generate', [QRCodeController::class, 'generateQR'])
            ->middleware(['admin.permission:manage_qrcodes']);
        
        // Atualizar ofertas do QR Code
        Route::put('/{qrcode}/offers', [QRCodeController::class, 'updateOffers'])
            ->middleware(['admin.permission:manage_qrcodes']);
        
        // Estatísticas de uso do QR Code
        Route::get('/{qrcode}/stats', [QRCodeController::class, 'getQRStats'])
            ->middleware(['admin.permission:view_reports']);
        
        // Listar QR Codes do estabelecimento
        Route::get('/list', [QRCodeController::class, 'listQRCodes'])
            ->middleware(['admin.permission:view_qrcodes']);
    });
});

// Rotas do sistema de descontos
Route::prefix('discounts')->group(function () {
    // Consultar descontos disponíveis (público - usuários logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/company/{empresa_id}', [DiscountController::class, 'getCompanyDiscountLevels']);
        Route::post('/calculate', [DiscountController::class, 'calculateUserDiscount']);
        Route::post('/apply', [DiscountController::class, 'applyDiscount']);
    });
    
    // Rotas administrativas de descontos
    Route::middleware(['sanctum.auth'])->group(function () {
        // Configurar níveis de desconto
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

// ============================================
// ROTAS DO SISTEMA i9Plus (QR Code + Fidelidade)
// ============================================

// Rotas do Cliente (autenticado)
Route::middleware('auth:sanctum')->prefix('cliente')->group(function () {
    // QR Code do cliente
    Route::get('/meu-qrcode', [QRCodeController::class, 'meuQRCode']);
    
    // Escanear QR Code da empresa (inscrição)
    Route::post('/escanear-empresa', [QRCodeController::class, 'escanearEmpresa']);
    
    // Bônus de Adesão (Cliente)
    Route::get('/bonus-disponivel/{empresa_id}', [BonusAdesaoController::class, 'bonusDisponivel']);
    Route::post('/resgatar-bonus/{empresa_id}', [BonusAdesaoController::class, 'resgatar']);
    
    // Cartão Fidelidade (Cliente)
    Route::get('/meu-progresso', [CartaoFidelidadeController::class, 'meuProgresso']);
    Route::get('/progresso-empresa/{empresa_id}', [CartaoFidelidadeController::class, 'progressoPorEmpresa']);
});

// Rotas da Empresa (autenticada)
Route::middleware('auth:sanctum')->prefix('empresa')->group(function () {
    // QR Code da empresa
    Route::get('/meu-qrcode', [QRCodeController::class, 'qrCodeEmpresa']);
    
    // Escanear QR Code do cliente (adicionar ponto)
    Route::post('/escanear-cliente', [QRCodeController::class, 'escanearCliente']);
    
    // Bônus de Adesão (CRUD - Empresa)
    Route::get('/bonus-adesao', [BonusAdesaoController::class, 'index']);
    Route::post('/bonus-adesao', [BonusAdesaoController::class, 'store']);
    Route::get('/bonus-adesao/{id}', [BonusAdesaoController::class, 'show']);
    Route::put('/bonus-adesao/{id}', [BonusAdesaoController::class, 'update']);
    Route::delete('/bonus-adesao/{id}', [BonusAdesaoController::class, 'destroy']);
    
    // Cartão Fidelidade (CRUD - Empresa)
    Route::get('/cartoes-fidelidade', [CartaoFidelidadeController::class, 'index']);
    Route::post('/cartoes-fidelidade', [CartaoFidelidadeController::class, 'store']);
    Route::get('/cartoes-fidelidade/{id}', [CartaoFidelidadeController::class, 'show']);
    Route::put('/cartoes-fidelidade/{id}', [CartaoFidelidadeController::class, 'update']);
    Route::delete('/cartoes-fidelidade/{id}', [CartaoFidelidadeController::class, 'destroy']);
    Route::post('/adicionar-ponto', [CartaoFidelidadeController::class, 'adicionarPonto']);
});
