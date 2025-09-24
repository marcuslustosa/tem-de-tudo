<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Admin;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    /**
     * Obter estatísticas gerais do sistema
     */
    public function getSystemStats(Request $request)
    {
        $days = $request->get('days', 30);
        
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'new_this_month' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            ],
            'admins' => [
                'total' => Admin::count(),
                'active' => Admin::where('is_active', true)->count(),
            ],
            'security' => AuditLog::getLoginStats($days),
            'recent_activity' => AuditLog::recent(7)->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obter logs de auditoria com filtros
     */
    public function getAuditLogs(Request $request)
    {
        $query = AuditLog::query();

        // Filtros
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('days')) {
            $query->recent($request->days);
        }

        if ($request->has('security_only') && $request->security_only) {
            $query->securityEvents();
        }

        $logs = $query->with(['user', 'admin'])
                     ->orderBy('created_at', 'desc')
                     ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Obter eventos de segurança recentes
     */
    public function getSecurityEvents(Request $request)
    {
        $days = $request->get('days', 7);
        $events = AuditLog::getSecurityEvents($days);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Obter estatísticas de login detalhadas
     */
    public function getLoginStats(Request $request)
    {
        $days = $request->get('days', 30);
        $stats = AuditLog::getLoginStats($days);

        // Adicionar gráfico de logins por dia
        $loginsByDay = AuditLog::where('action', 'login_success')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $stats['daily_logins'] = $loginsByDay;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obter relatório de usuários
     */
    public function getUsersReport(Request $request)
    {
        $query = User::query();

        // Filtros
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->has('created_after')) {
            $query->where('created_at', '>=', $request->created_after);
        }

        $users = $query->select(['id', 'name', 'email', 'pontos', 'created_at', 'is_active'])
                      ->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 100));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Limpar logs antigos
     */
    public function cleanupLogs(Request $request)
    {
        $user = $request->get('authenticated_user');
        
        // Verificar se é super admin
        if ($user->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas super admins podem limpar logs'
            ], 403);
        }

        $keepDays = $request->get('keep_days', 90);
        $deletedCount = AuditLog::cleanup($keepDays);

        // Log da ação
        AuditLog::logEvent('logs_cleanup', $user->id, $request, [
            'keep_days' => $keepDays,
            'deleted_count' => $deletedCount
        ]);

        return response()->json([
            'success' => true,
            'message' => "Foram removidos {$deletedCount} logs antigos",
            'deleted_count' => $deletedCount
        ]);
    }
}