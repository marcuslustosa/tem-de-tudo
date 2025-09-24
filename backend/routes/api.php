<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\NotificationController;

// Rotas públicas de autenticação
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Rotas de admin com JWT
Route::prefix('admin')->group(function () {
    // Login de admin (público)
    Route::post('/login', [AuthController::class, 'adminLogin']);
    
    // Rotas protegidas por JWT
    Route::middleware(['jwt.auth'])->group(function () {
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
