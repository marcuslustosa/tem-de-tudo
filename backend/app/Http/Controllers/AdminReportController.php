<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminReportController extends Controller
{
    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function empresaAtivaQuery()
    {
        $query = Empresa::query();
        if ($this->hasColumn('empresas', 'ativo')) {
            $query->where('ativo', true);
        } elseif ($this->hasColumn('empresas', 'status')) {
            $query->where('status', 'ativo');
        }
        return $query;
    }

    /**
     * Obter estatisticas gerais do sistema
     */
    public function getSystemStats(Request $request)
    {
        $days = (int) $request->get('days', 30);

        $userQuery = User::query();
        $activeUsers = $this->hasColumn('users', 'is_active')
            ? (clone $userQuery)->where('is_active', true)->count()
            : (clone $userQuery)->where('status', 'ativo')->count();

        $adminsTotal = class_exists(Admin::class) && $this->hasTable('admins') ? Admin::count() : 0;
        $adminsActive = class_exists(Admin::class) && $this->hasColumn('admins', 'is_active')
            ? Admin::where('is_active', true)->count()
            : 0;

        $stats = [
            'users' => [
                'total' => $userQuery->count(),
                'active' => $activeUsers,
                'new_this_month' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            ],
            'admins' => [
                'total' => $adminsTotal,
                'active' => $adminsActive,
            ],
            'security' => $this->hasTable('audit_logs') ? AuditLog::getLoginStats($days) : [],
            'recent_activity' => $this->hasTable('audit_logs') ? AuditLog::recent(7)->count() : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obter logs de auditoria com filtros
     */
    public function getAuditLogs(Request $request)
    {
        if (!$this->hasTable('audit_logs')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => 1,
                    'data' => [],
                    'total' => 0,
                ],
            ]);
        }

        $query = AuditLog::query();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('days')) {
            $query->recent($request->days);
        }

        if ($request->boolean('security_only')) {
            $query->securityEvents();
        }

        $logs = $query->with(['user', 'admin'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Obter eventos de seguranca recentes
     */
    public function getSecurityEvents(Request $request)
    {
        if (!$this->hasTable('audit_logs')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $days = (int) $request->get('days', 7);
        $events = AuditLog::getSecurityEvents($days);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Obter estatisticas de login detalhadas
     */
    public function getLoginStats(Request $request)
    {
        if (!$this->hasTable('audit_logs')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $days = (int) $request->get('days', 30);
        $stats = AuditLog::getLoginStats($days);

        $loginsByDay = AuditLog::where('action', 'login_success')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $stats['daily_logins'] = $loginsByDay;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obter relatorio de usuarios
     */
    public function getUsersReport(Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 100), 500));
        $query = User::query();

        if ($request->boolean('active_only')) {
            if ($this->hasColumn('users', 'is_active')) {
                $query->where('is_active', true);
            } elseif ($this->hasColumn('users', 'status')) {
                $query->where('status', 'ativo');
            }
        }

        if ($request->filled('created_after')) {
            $query->where('created_at', '>=', $request->created_after);
        }

        $columns = ['id', 'name', 'email', 'created_at'];
        foreach (['perfil', 'status', 'is_active', 'pontos', 'updated_at', 'last_login'] as $col) {
            if ($this->hasColumn('users', $col)) {
                $columns[] = $col;
            }
        }

        $users = $query->select($columns)->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Limpar logs antigos
     */
    public function cleanupLogs(Request $request)
    {
        $user = $request->get('authenticated_user') ?? $request->user();

        if (($user->role ?? null) !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas super admins podem limpar logs',
            ], 403);
        }

        if (!$this->hasTable('audit_logs')) {
            return response()->json([
                'success' => true,
                'message' => 'Tabela de logs nao existe neste ambiente.',
                'deleted_count' => 0,
            ]);
        }

        $keepDays = (int) $request->get('keep_days', 90);
        $deletedCount = AuditLog::cleanup($keepDays);

        AuditLog::logEvent('logs_cleanup', $user->id, $request, [
            'keep_days' => $keepDays,
            'deleted_count' => $deletedCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Foram removidos {$deletedCount} logs antigos",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Dashboard stats para admin
     */
    public function dashboardStats(Request $request)
    {
        try {
            $hasCheckins = $this->hasTable('checkins');
            $hasPontos = $this->hasTable('pontos');
            $hasPromocoes = $this->hasTable('promocoes');
            $hasCoupons = $this->hasTable('coupons');
            $hasValorCol = $hasCoupons && $this->hasColumn('coupons', 'valor_desconto');
            $hasCustoCol = $hasCoupons && $this->hasColumn('coupons', 'custo_pontos');

            $usuarios = User::count();
            $empresas = Empresa::count();
            $promocoes = $hasPromocoes ? DB::table('promocoes')->count() : 0;
            $resgates = $hasCoupons ? DB::table('coupons')->whereIn('status', ['used', 'utilizado'])->count() : 0;
            $volume = $hasCoupons
                ? DB::table('coupons')->sum($hasValorCol ? 'valor_desconto' : ($hasCustoCol ? 'custo_pontos' : DB::raw('0')))
                : 0;

            $stats = [
                'total_users' => $usuarios,
                'total_empresas' => $empresas,
                'total_pontos_distribuidos' => $hasPontos ? DB::table('pontos')->sum('pontos') : 0,
                'total_checkins' => $hasCheckins ? DB::table('checkins')->count() : 0,
                'users_ativos_hoje' => User::where('updated_at', '>=', today())->count(),
                'empresas_ativas' => $this->empresaAtivaQuery()->count(),
                // Estrutura esperada pelo frontend
                'totais' => [
                    'usuarios' => $usuarios,
                    'empresas' => $empresas,
                    'campanhas' => $promocoes,
                    'resgates' => $resgates,
                    'volume' => (float) $volume,
                ],
                // Chaves de fallback no frontend
                'usuarios' => $usuarios,
                'empresas' => $empresas,
                'promocoes' => $promocoes,
                'resgates' => $resgates,
                'volume' => (float) $volume,
                'crescimento_texto' => 'Dados consolidados',
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard admin', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados do dashboard',
            ], 500);
        }
    }

    /**
     * Atividade recente para admin
     */
    public function recentActivity(Request $request)
    {
        try {
            if (!$this->hasTable('audit_logs')) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $query = DB::table('audit_logs')->orderByDesc('audit_logs.created_at')->limit(20);

            if ($this->hasTable('users')) {
                $query->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
                    ->addSelect('users.name as user_name');
                if ($this->hasColumn('users', 'perfil')) {
                    $query->addSelect('users.perfil');
                } else {
                    $query->addSelect(DB::raw("'usuario' as perfil"));
                }
            } else {
                $query->addSelect(DB::raw("'Sistema' as user_name"), DB::raw("'sistema' as perfil"));
            }

            $query->addSelect(
                'audit_logs.id',
                'audit_logs.action',
                'audit_logs.details',
                'audit_logs.created_at'
            );

            $activities = $query->get()->map(function ($activity) {
                $titulo = $this->formatAction($activity->action ?? 'atividade');
                $detalhe = is_array($activity->details) ? json_encode($activity->details) : (string) ($activity->details ?? '');

                return [
                    'id' => $activity->id,
                    'acao' => $titulo,
                    'usuario' => $activity->user_name ?? 'Sistema',
                    'perfil' => $activity->perfil ?? 'sistema',
                    'detalhes' => $detalhe,
                    'created_at' => $activity->created_at,
                    // chaves esperadas pelo frontend
                    'titulo' => $titulo,
                    'descricao' => $detalhe,
                    'message' => $titulo,
                    'detalhe' => $detalhe,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar atividade recente', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar atividade recente',
            ], 500);
        }
    }

    /**
     * Formatar acao para exibicao
     */
    private function formatAction($action)
    {
        $actions = [
            'user_registered' => 'Novo usuario registrado',
            'user_login' => 'Login realizado',
            'admin_login' => 'Login administrativo',
            'user_logout' => 'Logout realizado',
            'pontos_ganhos' => 'Pontos ganhos',
            'cupom_resgatado' => 'Cupom resgatado',
            'qr_code_criado' => 'QR Code criado',
            'empresa_criada' => 'Empresa criada',
        ];

        return $actions[$action] ?? ucfirst(str_replace('_', ' ', (string) $action));
    }
}
