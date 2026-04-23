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
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\API\CampanhaMultiplicadorController;

// NOVOS CONTROLLERS - GAPS 1-10
use App\Http\Controllers\DesafioController;
use App\Http\Controllers\NpsController;
use App\Http\Controllers\SegmentoController;
use App\Http\Controllers\WebhookSaidaController;
use App\Http\Controllers\AjustePontosController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RedemptionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\API\LoyaltyPolicyController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ApiDocsController;

// Health checks e métricas (público)
Route::get('/ping', [HealthController::class, 'ping']);
Route::get('/health', [HealthController::class, 'health']);
Route::get('/metrics', [HealthController::class, 'metrics'])->middleware('throttle:60,1');
Route::get('/docs/openapi', [ApiDocsController::class, 'index'])->middleware('cache.response:600');
Route::get('/docs/openapi/{version}', [ApiDocsController::class, 'show'])->middleware('cache.response:600');

if (app()->environment(['local', 'testing'])) {
    // Debug route (somente ambiente local/teste)
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
                'message' => 'Erro na API'
            ], 500);
        }
    });

    // Debug - teste de empresas (somente ambiente local/teste)
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
                'message' => 'Erro ao carregar empresas'
            ], 500);
        }
    });
}

// Push Notifications
Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
    Route::post('/push/test', [PushSubscriptionController::class, 'test']);

    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/auth/delete-account', [AuthController::class, 'deletarConta']);

    Route::prefix('privacy')->group(function () {
        Route::get('/status', [PrivacyController::class, 'status']);
        Route::put('/consent', [PrivacyController::class, 'updateConsent']);
        Route::post('/export', [PrivacyController::class, 'requestExport'])->middleware('rate.limit:3:1');
        Route::get('/export/{privacyRequestId}/download', [PrivacyController::class, 'downloadExport']);
        Route::post('/delete-account', [PrivacyController::class, 'deleteAccount'])->middleware('rate.limit:2:1');
    });

    // NotificaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes internas
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// Setup database manual (somente ambiente local/teste)
if (app()->environment(['local', 'testing'])) {
    Route::post('/setup-database', [SetupController::class, 'setupDatabase'])
        ->middleware('throttle:2,1');
}

// ============================================
// ROTAS PÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡BLICAS (SEM AUTENTICAÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢O)
// ============================================

// AutenticaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

// Politica de fidelidade (publica)
Route::get('/fidelidade/programa', [PontosController::class, 'programa'])
    ->middleware('cache.response:300');

// Empresas (leitura pÃºblica) - COM CACHE
Route::get('/empresas', [EmpresaController::class, 'listEmpresas'])
    ->middleware('cache.response:300'); // 5 minutos
Route::get('/empresas/{id}', [EmpresaController::class, 'getEmpresa'])
    ->middleware('cache.response:600'); // 10 minutos
Route::get('/empresas/{id}/promocoes', [EmpresaController::class, 'getEmpresaPromocoes'])
    ->middleware('cache.response:600'); // 10 minutos

// Banners e Categorias (leitura pÃºblica) - COM CACHE
Route::get('/banners', [AdminContentController::class, 'publicBanners'])
    ->middleware('cache.response:1800'); // 30 minutos
Route::get('/categorias', [AdminContentController::class, 'publicCategorias'])
    ->middleware('cache.response:3600'); // 1 hora

// Produtos das empresas (leitura pÃºblica) - COM CACHE
Route::get('/empresas/{empresaId}/produtos', [ProdutoController::class, 'index'])
    ->middleware('cache.response:600'); // 10 minutos
Route::get('/empresas/{empresaId}/produtos/{id}', [ProdutoController::class, 'show'])
    ->middleware('cache.response:600'); // 10 minutos

// Badges (informaÃ§Ãµes pÃºblicas) - COM CACHE
Route::get('/badges', [BadgeController::class, 'index'])
    ->middleware('cache.response:1800'); // 30 minutos
Route::get('/badges/{id}', [BadgeController::class, 'show'])
    ->middleware('cache.response:1800'); // 30 minutos
Route::get('/badges/ranking', [BadgeController::class, 'ranking'])
    ->middleware('cache.response:1800'); // 30 minutos

// Webhook do Mercado Pago (pÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºblico)
Route::post('/webhook/mercadopago', [PagamentoController::class, 'webhook'])->name('webhook.mercadopago');

// ============================================
// ROTAS PROTEGIDAS (REQUER AUTENTICAÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢O)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // AutenticaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o e Perfil
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
    Route::post('/pagamentos/pix', [PagamentoController::class, 'criarPagamentoPix'])
        ->middleware(['rate.limit:10:1', 'idempotency:3600']);
    Route::get('/pagamentos/meus', [PagamentoController::class, 'meusPagamentos']);
    Route::get('/pagamentos/{id}/status', [PagamentoController::class, 'consultarStatus']);
    Route::post('/pagamentos/{id}/cancelar', [PagamentoController::class, 'cancelar']);
    
    // ========== SISTEMA CHECK-IN QR CODE ==========
    Route::post('/checkin/fazer', [MainCheckInController::class, 'fazerCheckIn']);
    Route::get('/checkin/historico', [MainCheckInController::class, 'meuHistorico']);
    Route::post('/checkin/validar-qr', [MainCheckInController::class, 'validarQRCode']);
    
    // ========== SISTEMA DE FIDELIDADE - WALLET ==========
    Route::prefix('fidelidade')->group(function () {
        Route::get('/cartao', [WalletController::class, 'show']);
        Route::get('/historico', [WalletController::class, 'historico'])->middleware('rate.limit:100:1');
        Route::post('/resgatar', [WalletController::class, 'resgatarPontos'])
            ->middleware(['rate.limit:5:1', 'idempotency:3600']);
        Route::post('/adicionar-pontos', [WalletController::class, 'adicionarPontos'])
            ->middleware(['rate.limit:10:1', 'idempotency:3600']);
        Route::post('/validar-qrcode', [WalletController::class, 'validarQRCode'])->middleware('rate.limit:20:1');
    });
    
    // ========== SISTEMA DE RESGATE PDV (RESERVA/CONFIRMA/ESTORNA) ==========
    Route::prefix('redemption')->group(function () {
        Route::post('/request', [RedemptionController::class, 'request'])
            ->middleware(['rate.limit:10:1', 'idempotency:3600']);
        Route::post('/confirm', [RedemptionController::class, 'confirm'])
            ->middleware(['rate.limit:10:1', 'idempotency:3600']);
        Route::post('/cancel', [RedemptionController::class, 'cancel'])
            ->middleware(['rate.limit:10:1', 'idempotency:3600']);
        Route::post('/reverse', [RedemptionController::class, 'reverse'])
            ->middleware(['rate.limit:10:1', 'idempotency:3600']); // Admin only
        Route::get('/{intentId}', [RedemptionController::class, 'show'])->middleware('rate.limit:60:1');
        Route::get('/user/{userId}', [RedemptionController::class, 'userHistory'])->middleware('rate.limit:60:1');
        Route::get('/company/{companyId}/pending', [RedemptionController::class, 'companyPending'])->middleware('rate.limit:60:1');
    });
    
    // ========== ROTAS PARA EMPRESAS ==========
    Route::middleware(['role.permission:empresa', 'subscription.check'])->group(function () {
        Route::post('/empresa/qrcode/gerar', [MainCheckInController::class, 'gerarQRCode']);
        Route::get('/empresa/checkins', [MainCheckInController::class, 'checkinsEmpresa']);
        Route::get('/empresa/pagamentos/estatisticas', [PagamentoController::class, 'estatisticasEmpresa']);
    });

});

// Rotas pÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºblicas de autenticaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o

// Middleware JavaScript para proteÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o de rotas por perfil
Route::get('/auth/check-access', function () {
    return response()->json(['message' => 'Access check endpoint']);
});

// Rotas de admin com JWT
Route::prefix('admin')->group(function () {
    // Login de admin (pÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºblico)
    Route::post('/login', [AuthController::class, 'adminLogin']);
    
    // Rotas protegidas por JWT
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'adminLogout']);
        Route::get('/me', [AuthController::class, 'adminProfile']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        
        // Rotas que requerem permissÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes especÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ficas
        Route::middleware(['admin.permission:create_users'])->group(function () {
            Route::post('/create-user', [AuthController::class, 'createUser']);
        });
        
        Route::middleware(['admin.permission:manage_users'])->group(function () {
            Route::get('/users', [AuthController::class, 'listUsers']);
            Route::put('/users/{id}/status', [AuthController::class, 'updateUserStatus']);
            // CPF e data_nascimento: apenas admin pode alterar (anti-fraude)
            Route::put('/users/{id}/dados-sensiveis', [AuthController::class, 'updateDadosSensiveis']);
            // Ativar/desativar empresa
            Route::patch('/empresas/{id}/toggle-status', [EmpresaController::class, 'toggleStatus']);
        });
        
        Route::middleware(['admin.permission:view_reports'])->group(function () {
            Route::get('/audit-logs', [AdminReportController::class, 'getAuditLogs']);
            Route::get('/login-stats', [AdminReportController::class, 'getLoginStats'])
                ->middleware('cache.response:1800'); // 30 min
            Route::get('/system-stats', [AdminReportController::class, 'getSystemStats'])
                ->middleware('cache.response:3600'); // 1 hora
            Route::get('/security-events', [AdminReportController::class, 'getSecurityEvents']);
        });
        
        Route::middleware(['admin.permission:manage_system'])->group(function () {
            Route::post('/cleanup-logs', [AdminReportController::class, 'cleanupLogs']);
        });

        // NotificaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes administrativas
        Route::middleware(['admin.permission:manage_users'])->group(function () {
            Route::post('/notifications/broadcast', [NotificationController::class, 'sendBroadcast']);
            Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
            Route::get('/notifications/stats', [NotificationController::class, 'getStats']);
            Route::post('/notifications/process-queue', [NotificationController::class, 'processQueue']);
        });

        // NotificaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes do admin
        Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
        Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
        Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
    });
});

// Rotas protegidas por Sanctum (usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rios regulares)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/add-pontos', [AuthController::class, 'addPontos']);

    //Route::get('/empresas', [EmpresaController::class, 'index']);
    //Route::post('/empresas', [EmpresaController::class, 'store']);

    // NotificaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes de usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rios
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
    Route::get('/notifications/settings', [NotificationController::class, 'getNotificationSettings']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateNotificationSettings']);
});

// Rotas especÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ficas por perfil
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->prefix('cliente')->group(function () {
    // QR Code do Cliente
    Route::get('/meu-qrcode', [ClienteAPIController::class, 'meuQRCode']);
    
    // Dashboard do Cliente
    Route::get('/dashboard', [ClienteAPIController::class, 'dashboard']);
    
    // Empresas
    Route::get('/empresas', [ClienteAPIController::class, 'listarEmpresas']);
    Route::get('/empresas/{id}', [ClienteAPIController::class, 'empresaDetalhes']);
    
    // QR Code
    Route::post('/escanear-qrcode', [ClienteAPIController::class, 'escanearQRCode'])->middleware('rate.limit:20:1');
    
    // PromoÃƒÆ'Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ'Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ'Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ'Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes
    Route::get('/promocoes', [ClienteAPIController::class, 'listarPromocoes']);
    Route::post('/resgatar-promocao/{id}', [ClienteAPIController::class, 'resgatarPromocao'])->middleware('rate.limit:5:1');
    Route::post('/promocoes/{id}/resgatar', [ClienteAPIController::class, 'resgatarPromocao'])->middleware('rate.limit:5:1');
    
    // AvaliaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes
    Route::post('/avaliar', [ClienteAPIController::class, 'avaliar']);
    
    // HistÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³rico
    Route::get('/historico-pontos', [ClienteAPIController::class, 'historicoPontos']);
    // Ranking de pontos
    Route::get('/ranking-pontos', [ClienteAPIController::class, 'rankingPontos']);    
    // Legacy route (manter compatibilidade)
    Route::get('/dashboard-data', [AuthController::class, 'clienteDashboard']);
});

Route::middleware(['auth:sanctum', 'role.permission:empresa', 'subscription.check'])->prefix('empresa')->group(function () {
    // Escanear QR do Cliente
    Route::post('/escanear-cliente', [EmpresaAPIController::class, 'escanearCliente'])->middleware('rate.limit:20:1');
    
    // Dashboard da Empresa
    Route::get('/dashboard', [EmpresaAPIController::class, 'dashboard']);

    // Perfil da empresa
    Route::get('/perfil', [EmpresaAPIController::class, 'meuPerfil']);
    Route::put('/perfil', [EmpresaAPIController::class, 'atualizarPerfil']);
    Route::get('/fidelidade/config', [LoyaltyPolicyController::class, 'companyConfig']);
    Route::put('/fidelidade/config', [LoyaltyPolicyController::class, 'companyUpdateConfig']);
    Route::get('/fidelidade/onboarding', [LoyaltyPolicyController::class, 'companyOnboardingStatus']);
    
    // Clientes
    Route::get('/clientes', [EmpresaAPIController::class, 'clientes']);
    
    // Produtos
    Route::post('/produtos', [ProdutoController::class, 'store']);
    Route::put('/produtos/{id}', [ProdutoController::class, 'update']);
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy']);
    
    // PromoÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes
    Route::get('/promocoes', [EmpresaAPIController::class, 'promocoes']);
    Route::post('/promocoes', [EmpresaAPIController::class, 'criarPromocao']);
    Route::put('/promocoes/{id}', [EmpresaAPIController::class, 'atualizarPromocao']);
    Route::delete('/promocoes/{id}', [EmpresaAPIController::class, 'deletarPromocao']);
    Route::patch('/promocoes/{id}/ativar', [EmpresaAPIController::class, 'ativarPromocao']);
    Route::patch('/promocoes/{id}/pausar', [EmpresaAPIController::class, 'pausarPromocao']);
    Route::get('/resgates', [EmpresaAPIController::class, 'resgates']);
    
    // QR Codes
    Route::get('/qrcodes', [EmpresaAPIController::class, 'qrCodes']);
    
    // AvaliaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âµes
    Route::get('/avaliacoes', [EmpresaAPIController::class, 'avaliacoes']);
    
    // RelatÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³rios
    Route::get('/relatorio-pontos', [EmpresaAPIController::class, 'relatorioPontos']);
    
    // Campanhas de multiplicador temporÃ¡rio
    Route::get('/campanhas', [CampanhaMultiplicadorController::class, 'index']);
    Route::post('/campanhas', [CampanhaMultiplicadorController::class, 'store']);
    Route::put('/campanhas/{id}', [CampanhaMultiplicadorController::class, 'update']);
    Route::delete('/campanhas/{id}', [CampanhaMultiplicadorController::class, 'destroy']);

    // Legacy routes (manter compatibilidade) - COM CACHE
    Route::get('/dashboard-stats', [EmpresaController::class, 'dashboardStats'])
        ->middleware('cache.response:300'); // 5 min
    Route::get('/recent-checkins', [EmpresaController::class, 'recentCheckins'])
        ->middleware('cache.response:180'); // 3 min
    Route::get('/top-clients', [EmpresaController::class, 'topClients'])
        ->middleware('cache.response:600'); // 10 min
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

    // Campanhas: visÃ£o geral admin
    Route::get('/campanhas', [CampanhaMultiplicadorController::class, 'adminIndex']);
    Route::get('/empresas/{companyId}/fidelidade/config', [LoyaltyPolicyController::class, 'adminCompanyConfig']);
    Route::put('/empresas/{companyId}/fidelidade/config', [LoyaltyPolicyController::class, 'adminUpdateCompanyConfig']);
    Route::get('/empresas/{companyId}/fidelidade/onboarding', [LoyaltyPolicyController::class, 'adminCompanyOnboardingStatus']);

    // Tickets de suporte (painel admin)
    Route::get('/tickets', [NotificationController::class, 'listTickets']);
    Route::get('/tickets/stats', [NotificationController::class, 'ticketStats']);
    Route::post('/tickets', [NotificationController::class, 'createTicket']);
    Route::post('/tickets/{id}/resolve', [NotificationController::class, 'resolveTicket']);
    Route::post('/tickets/{id}/reopen', [NotificationController::class, 'reopenTicket']);
    Route::delete('/tickets/{id}', [NotificationController::class, 'closeTicket']);

    // Banners - CRUD completo
    Route::get('/banners', [BannerController::class, 'index']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::get('/banners/{id}', [BannerController::class, 'show']);
    Route::post('/banners/{id}', [BannerController::class, 'update']); // POST para suportar upload de imagem
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
    Route::post('/banners/{id}/toggle', [BannerController::class, 'toggleStatus']);
    Route::post('/banners/reorder', [BannerController::class, 'reorder']);
});

// Rotas do sistema de pontos (protegidas por Sanctum)
Route::middleware('auth:sanctum')->prefix('pontos')->group(function () {
    // Check-in do usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rio
    Route::post('/checkin', [PontosController::class, 'checkin']);
    
    // Resgatar pontos por recompensas
    Route::post('/resgatar', [PontosController::class, 'resgatarPontos']);
    
    // Dados do usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rio (pontos, nÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­vel, etc.)
    Route::get('/meus-dados', [PontosController::class, 'meusDados']);
    
    // HistÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³rico de pontos
    Route::get('/historico', [PontosController::class, 'historicoPontos']);
    
    // Cupons do usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rio
    Route::get('/meus-cupons', [PontosController::class, 'meusCupons']);
    
    // Usar um cupom
    Route::post('/usar-cupom/{cupom}', [PontosController::class, 'usarCupom']);
});

// Rotas administrativas do sistema de pontos (protegidas por JWT e admin)
Route::middleware(['auth:sanctum'])->prefix('admin/pontos')->group(function () {
    // Aprovar/rejeitar check-ins
    Route::post('/checkin/{checkin}/aprovar', [PontosController::class, 'aprovarCheckin'])
        ->middleware(['admin.permission:manage_checkins']);

    Route::post('/checkin/{checkin}/rejeitar', [PontosController::class, 'rejeitarCheckin'])
        ->middleware(['admin.permission:manage_checkins']);
    
    // Check-ins pendentes
    Route::get('/checkins-pendentes', [PontosController::class, 'checkinsPendentes'])
        ->middleware(['admin.permission:view_checkins']);
    
    // EstatÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­sticas do sistema
    Route::get('/estatisticas', [PontosController::class, 'estatisticas'])
        ->middleware(['admin.permission:view_reports']);
});

// Rotas do sistema QR Code
Route::prefix('qrcode')->group(function () {
    // Escanear QR Code (pÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºblico - usuÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡rios logados)
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
        
        // EstatÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­sticas de uso do QR Code
        Route::get('/{qrcode}/stats', [QRCodeController::class, 'getQRStats'])
            ->middleware(['admin.permission:view_reports']);
        
        // Listar QR Codes do estabelecimento
        Route::get('/list', [QRCodeController::class, 'listQRCodes'])
            ->middleware(['admin.permission:view_qrcodes']);
    });
});

// Rotas do sistema de descontos
Route::prefix('discounts')->group(function () {
    // Consultar descontos disponiveis (publico - usuarios logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/company/{empresa_id}', [DiscountController::class, 'getCompanyDiscountLevels']);
        Route::post('/calculate', [DiscountController::class, 'calculateUserDiscount']);
        Route::post('/apply', [DiscountController::class, 'applyDiscount']);
    });

    // Rotas administrativas de descontos
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/configure', [DiscountController::class, 'configureCompanyDiscounts'])
            ->middleware(['admin.permission:manage_discounts']);
        Route::post('/find-customer', [DiscountController::class, 'findCustomerForDiscount'])
            ->middleware(['admin.permission:manage_discounts']);
    });
});

// Rotas OpenAI (admin apenas) - separadas do prefix discounts
Route::prefix('openai')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/status', [OpenAIController::class, 'status']);
    Route::get('/test', [OpenAIController::class, 'test'])
        ->middleware(['admin.permission:manage_system']);
    Route::post('/chat', [OpenAIController::class, 'chat'])
        ->middleware(['admin.permission:manage_system']);
    Route::post('/suggest', [OpenAIController::class, 'suggest'])
        ->middleware(['admin.permission:manage_system']);
});

// Programa de IndicaÃ§Ã£o (referral) - cliente autenticado
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->prefix('referral')->group(function () {
    Route::get('/meu-codigo', [ReferralController::class, 'meuCodigo']);
    Route::get('/estatisticas', [ReferralController::class, 'estatisticas']);
});

// Bonus de Adesao (cliente) - pontos ao se cadastrar
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->prefix('cliente/bonus-adesao')->group(function () {
    Route::get('/disponivel/{empresa_id}', [BonusAdesaoController::class, 'bonusDisponivel']);
    Route::post('/resgatar/{empresa_id}', [BonusAdesaoController::class, 'resgatar']);
});

// Bonus de Adesao (admin) - gerenciar configuracoes
Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin/bonus-adesao')->group(function () {
    Route::get('/', [BonusAdesaoController::class, 'index']);
    Route::post('/', [BonusAdesaoController::class, 'store']);
    Route::get('/{id}', [BonusAdesaoController::class, 'show']);
    Route::put('/{id}', [BonusAdesaoController::class, 'update']);
    Route::delete('/{id}', [BonusAdesaoController::class, 'destroy']);
});

// ============================================================
// NOVOS ENDPOINTS  GAPS 1-10 IMPLEMENTADOS
// ============================================================

// STREAK: jï¿½ integrado no check-in (retorno do fazerCheckIn inclui streak)

// DESAFIOS / MISSï¿½ES
Route::middleware('auth:sanctum')->prefix('desafios')->group(function () {
    Route::get('/', [DesafioController::class, 'index']);
    Route::get('/{id}', [DesafioController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role.permission:empresa', 'subscription.check'])->prefix('empresa/desafios')->group(function () {
    Route::post('/', [DesafioController::class, 'store']);
    Route::put('/{id}', [DesafioController::class, 'update']);
    Route::delete('/{id}', [DesafioController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin/desafios')->group(function () {
    Route::get('/', [DesafioController::class, 'adminIndex']);
    Route::post('/', [DesafioController::class, 'adminStore']);
});

// NPS
Route::middleware('auth:sanctum')->prefix('nps')->group(function () {
    Route::post('/responder', [NpsController::class, 'responder']);
});

Route::middleware(['auth:sanctum', 'role.permission:empresa', 'subscription.check'])->prefix('empresa/nps')->group(function () {
    Route::get('/estatisticas', [NpsController::class, 'estatisticasEmpresa']);
});

Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin/nps')->group(function () {
    Route::get('/estatisticas', [NpsController::class, 'estatisticasAdmin']);
});

// SEGMENTAï¿½ï¿½O DE CLIENTES (admin)
Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin/segmentos')->group(function () {
    Route::get('/', [SegmentoController::class, 'index']);
    Route::post('/', [SegmentoController::class, 'store']);
    Route::put('/{id}', [SegmentoController::class, 'update']);
    Route::delete('/{id}', [SegmentoController::class, 'destroy']);
    Route::get('/{id}/usuarios', [SegmentoController::class, 'usuarios']);
    Route::post('/{id}/sincronizar', [SegmentoController::class, 'sincronizarManual']);
});

// WEBHOOKS DE SAï¿½DA
Route::middleware('auth:sanctum')->prefix('webhooks')->group(function () {
    Route::get('/', [WebhookSaidaController::class, 'index']);
    Route::post('/', [WebhookSaidaController::class, 'store']);
    Route::put('/{id}', [WebhookSaidaController::class, 'update']);
    Route::delete('/{id}', [WebhookSaidaController::class, 'destroy']);
    Route::get('/{id}/logs', [WebhookSaidaController::class, 'logs']);
    Route::post('/{id}/rotacionar-segredo', [WebhookSaidaController::class, 'rotacionarSegredo']);
});

// AJUSTE MANUAL DE PONTOS (admin)
Route::middleware(['auth:sanctum', 'role.permission:admin'])->prefix('admin/ajuste-pontos')->group(function () {
    Route::post('/usuarios/{id}', [AjustePontosController::class, 'ajustar']);
    Route::get('/usuarios/{id}/historico', [AjustePontosController::class, 'historico']);
    Route::get('/historico', [AjustePontosController::class, 'historicoGlobal']);
});

// ============================================================
// CAMPANHAS DE MULTIPLICADOR (empresa/admin)
// ============================================================
Route::middleware('auth:sanctum')->prefix('campanhas')->group(function () {
    // Consultar multiplicador ativo (qualquer autenticado)
    Route::get('/multiplicador-ativo', [CampanhaController::class, 'multiplicadorAtivo'])->middleware('throttle:60,1');
    
    // Empresa: gerenciar suas próprias campanhas
    Route::middleware(['role.permission:empresa', 'subscription.check'])->group(function () {
        Route::get('/', [CampanhaController::class, 'index']);
        Route::get('/ativas', [CampanhaController::class, 'ativas']);
        Route::post('/', [CampanhaController::class, 'store'])->middleware('rate.limit:10:1');
        Route::put('/{id}', [CampanhaController::class, 'update'])->middleware('rate.limit:10:1');
        Route::delete('/{id}', [CampanhaController::class, 'destroy'])->middleware('rate.limit:5:1');
    });
    
    // Admin: gerenciar campanhas de todas as empresas
    Route::middleware('role.permission:admin')->prefix('admin')->group(function () {
        Route::get('/todas', [CampanhaController::class, 'index']);
        Route::post('/criar', [CampanhaController::class, 'store']);
    });
});

// ============================================================
// ANALYTICS / MÉTRICAS DE NEGÓCIO (admin/empresa)
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('analytics')->group(function () {
    // Dashboard completo
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard'])->middleware('throttle:30,1');
    
    // Métricas específicas
    Route::get('/cltv', [AnalyticsController::class, 'cltv'])->middleware('throttle:30,1');
    Route::get('/retention', [AnalyticsController::class, 'retention'])->middleware('throttle:30,1');
    Route::get('/churn', [AnalyticsController::class, 'churn'])->middleware('throttle:30,1');
    Route::get('/cohort', [AnalyticsController::class, 'cohort'])->middleware('throttle:20,1');
    Route::get('/transactions', [AnalyticsController::class, 'transactions'])->middleware('throttle:30,1');
    Route::get('/level-distribution', [AnalyticsController::class, 'levelDistribution'])->middleware('throttle:30,1');
    Route::get('/top-users', [AnalyticsController::class, 'topUsers'])->middleware('throttle:30,1');
});

// ============================================================
// LEADERBOARDS / RANKING AO VIVO (gamificação)
// ============================================================
Route::prefix('leaderboard')->group(function () {
    // Rankings públicos (sem autenticação) - COM CACHE
    Route::get('/global', [LeaderboardController::class, 'global'])
        ->middleware(['throttle:60,1', 'cache.response:300']); // 5 min
    Route::get('/monthly', [LeaderboardController::class, 'monthly'])
        ->middleware(['throttle:60,1', 'cache.response:300']); // 5 min
    Route::get('/badges', [LeaderboardController::class, 'badges'])
        ->middleware(['throttle:60,1', 'cache.response:300']); // 5 min
    Route::get('/company/{empresaId}', [LeaderboardController::class, 'company'])
        ->middleware(['throttle:60,1', 'cache.response:300']); // 5 min
    Route::get('/stats', [LeaderboardController::class, 'stats'])
        ->middleware(['throttle:30,1', 'cache.response:600']); // 10 min
    Route::get('/user/{userId}', [LeaderboardController::class, 'userPosition'])
        ->middleware(['throttle:60,1', 'cache.response:180']); // 3 min
    
    // Rankings autenticados
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-position', [LeaderboardController::class, 'myPosition'])->middleware('throttle:60,1');
    });
    
    // Admin: limpar cache
    Route::middleware(['auth:sanctum', 'role.permission:admin'])->group(function () {
        Route::post('/clear-cache', [LeaderboardController::class, 'clearCache'])->middleware('rate.limit:5:1');
    });
});

// GOOGLE WALLET / APPLE WALLET
Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/google', [WalletController::class, 'googleWalletPass']);
    Route::get('/apple', [WalletController::class, 'appleWalletPass']);
});

// MULTI-EMPRESA: saldo de pontos da rede compartilhada
Route::middleware('auth:sanctum')->prefix('rede')->group(function () {
    Route::get('/empresas', function () {
        $empresas = \App\Models\Empresa::where('ativo', true)
                      ->where('rede_compartilhada', true)
                      ->get(['id', 'nome', 'logo', 'categoria', 'rede_nome']);
        return response()->json(['success' => true, 'data' => $empresas]);
    });

    Route::get('/meus-pontos', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $empresasRede = \App\Models\Empresa::where('rede_compartilhada', true)->pluck('id');
        $saldo = \Illuminate\Support\Facades\DB::table('pontos')
            ->where('user_id', $user->id)
            ->whereIn('empresa_id', $empresasRede)
            ->selectRaw("
                SUM(CASE WHEN tipo NOT IN ('resgate','redeem') THEN pontos ELSE 0 END) as ganhos,
                SUM(CASE WHEN tipo IN ('resgate','redeem') THEN pontos ELSE 0 END) as gastos
            ")
            ->first();
        return response()->json([
            'success' => true,
            'data' => [
                'saldo_rede'  => max(0, ($saldo->ganhos ?? 0) - ($saldo->gastos ?? 0)),
                'total_ganho' => $saldo->ganhos ?? 0,
                'total_gasto' => $saldo->gastos ?? 0,
                'empresas_rede' => $empresasRede->count(),
            ],
        ]);
    });
});

// DISCOUNT LEVEL  rota jï¿½ existe em /discounts 
// frontend pode consumir diretamente. Dashboard expï¿½e via /cliente/dashboard abaixo:
Route::middleware(['auth:sanctum', 'role.permission:cliente'])->group(function () {
    Route::get('/cliente/desconto', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $nivel = strtolower($user->nivel ?? 'bronze');
        $descontos = [
            'bronze'  => 0,
            'prata'   => 5,
            'ouro'    => 10,
            'platina' => 15,
        ];
        return response()->json([
            'success' => true,
            'data' => [
                'nivel'             => $nivel,
                'desconto_pct'      => $descontos[$nivel] ?? 0,
                'pontos_atuais'     => $user->pontos,
                'streak_atual'      => $user->streak_atual ?? 0,
                'streak_maximo'     => $user->streak_maximo ?? 0,
            ],
        ]);
    });
});
