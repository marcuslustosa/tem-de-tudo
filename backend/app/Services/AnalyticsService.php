<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ledger;
use App\Models\CheckIn;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Calcula CLTV (Customer Lifetime Value) médio.
     * CLTV = valor médio gasto * frequência de compra * tempo de vida médio do cliente
     */
    public function calculateCLTV(?int $empresaId = null): array
    {
        $query = User::query();

        if ($empresaId) {
            // Filtrar por usuários que já interagiram com a empresa
            $query->whereHas('checkins', fn($q) => $q->where('empresa_id', $empresaId));
        }

        $users = $query->where('created_at', '>', now()->subYear())->get();

        if ($users->isEmpty()) {
            return [
                'cltv_medio' => 0,
                'valor_medio_gasto' => 0,
                'frequencia_media' => 0,
                'tempo_vida_medio_dias' => 0,
                'total_usuarios' => 0,
            ];
        }

        $totalValorGasto = $users->sum('valor_gasto_total');
        $valorMedioGasto = $totalValorGasto / $users->count();

        // Frequência média de interações (checkins)
        $totalCheckins = CheckIn::whereIn('user_id', $users->pluck('id'))
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->count();
        $frequenciaMedia = $totalCheckins / $users->count();

        // Tempo de vida médio (em dias desde o cadastro)
        $tempoVidaMedio = $users->avg(fn($u) => $u->created_at->diffInDays(now()));

        // CLTV = valor médio * frequência (simplificado)
        $cltvMedio = $valorMedioGasto * $frequenciaMedia;

        return [
            'cltv_medio' => round($cltvMedio, 2),
            'valor_medio_gasto' => round($valorMedioGasto, 2),
            'frequencia_media' => round($frequenciaMedia, 2),
            'tempo_vida_medio_dias' => round($tempoVidaMedio, 0),
            'total_usuarios' => $users->count(),
        ];
    }

    /**
     * Calcula taxa de retenção.
     * Retenção = % de usuários que retornaram em um período.
     */
    public function calculateRetention(?int $empresaId = null, int $periodoDias = 30): array
    {
        $dataInicio = now()->subDays($periodoDias);
        $dataFim = now();

        // Usuários que fizeram checkin no período anterior
        $usuariosPeriodoAnterior = CheckIn::whereBetween('created_at', [
            $dataInicio->copy()->subDays($periodoDias),
            $dataInicio,
        ])
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->distinct('user_id')
            ->pluck('user_id');

        if ($usuariosPeriodoAnterior->isEmpty()) {
            return [
                'taxa_retencao' => 0,
                'usuarios_retornaram' => 0,
                'usuarios_periodo_anterior' => 0,
                'periodo_dias' => $periodoDias,
            ];
        }

        // Usuários que retornaram no período atual
        $usuariosRetornaram = CheckIn::whereBetween('created_at', [$dataInicio, $dataFim])
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->whereIn('user_id', $usuariosPeriodoAnterior)
            ->distinct('user_id')
            ->count();

        $taxaRetencao = ($usuariosRetornaram / $usuariosPeriodoAnterior->count()) * 100;

        return [
            'taxa_retencao' => round($taxaRetencao, 2),
            'usuarios_retornaram' => $usuariosRetornaram,
            'usuarios_periodo_anterior' => $usuariosPeriodoAnterior->count(),
            'periodo_dias' => $periodoDias,
        ];
    }

    /**
     * Calcula taxa de churn.
     * Churn = % de usuários que não retornaram em X dias.
     */
    public function calculateChurn(?int $empresaId = null, int $diasInatividade = 90): array
    {
        $dataLimite = now()->subDays($diasInatividade);

        // Total de usuários
        $totalUsuarios = User::when($empresaId, function ($query) use ($empresaId) {
            return $query->whereHas('checkins', fn($q) => $q->where('empresa_id', $empresaId));
        })->count();

        // Usuários inativos (sem checkin há X dias)
        $usuariosInativos = User::when($empresaId, function ($query) use ($empresaId) {
            return $query->whereHas('checkins', fn($q) => $q->where('empresa_id', $empresaId));
        })
            ->whereDoesntHave('checkins', function ($query) use ($dataLimite, $empresaId) {
                $query->where('created_at', '>', $dataLimite)
                    ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId));
            })
            ->count();

        $taxaChurn = $totalUsuarios > 0 ? ($usuariosInativos / $totalUsuarios) * 100 : 0;

        return [
            'taxa_churn' => round($taxaChurn, 2),
            'usuarios_inativos' => $usuariosInativos,
            'total_usuarios' => $totalUsuarios,
            'dias_inatividade' => $diasInatividade,
        ];
    }

    /**
     * Análise de cohort (coorte de usuários por mês de cadastro).
     */
    public function cohortAnalysis(?int $empresaId = null, int $meses = 6): array
    {
        $cohorts = [];
        $dataInicio = now()->subMonths($meses)->startOfMonth();

        for ($i = 0; $i < $meses; $i++) {
            $mesInicio = $dataInicio->copy()->addMonths($i);
            $mesFim = $mesInicio->copy()->endOfMonth();

            // Usuários que se cadastraram neste mês
            $usuariosCohort = User::whereBetween('created_at', [$mesInicio, $mesFim])
                ->when($empresaId, fn($q) => $q->whereHas('checkins', fn($c) => $c->where('empresa_id', $empresaId)))
                ->pluck('id');

            if ($usuariosCohort->isEmpty()) {
                continue;
            }

            $retencoesSubsequentes = [];
            for ($mes = 0; $mes <= $meses - $i; $mes++) {
                $periodoInicio = $mesInicio->copy()->addMonths($mes);
                $periodoFim = $periodoInicio->copy()->endOfMonth();

                $usuariosAtivos = CheckIn::whereBetween('created_at', [$periodoInicio, $periodoFim])
                    ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
                    ->whereIn('user_id', $usuariosCohort)
                    ->distinct('user_id')
                    ->count();

                $retencoesSubsequentes["mes_$mes"] = round(($usuariosAtivos / $usuariosCohort->count()) * 100, 2);
            }

            $cohorts[] = [
                'cohort' => $mesInicio->format('Y-m'),
                'usuarios' => $usuariosCohort->count(),
                'retencoes' => $retencoesSubsequentes,
            ];
        }

        return $cohorts;
    }

    /**
     * Métricas de transações (pontos).
     */
    public function transactionMetrics(?int $empresaId = null, int $dias = 30): array
    {
        $dataInicio = now()->subDays($dias);

        $transacoes = Ledger::where('created_at', '>', $dataInicio)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->get();

        $totalTransacoes = $transacoes->count();
        $transacoesEarn = $transacoes->whereIn('transaction_type', ['earn', 'earn_bonus']);
        $pontosEmitidos = $transacoesEarn->sum('points');
        $pontosResgatados = abs($transacoes->whereIn('transaction_type', ['redeem', 'reserved'])->sum('points'));
        $pontosExpirados = abs($transacoes->where('transaction_type', 'expiration')->sum('points'));

        $ticketMedio = $transacoesEarn->count() > 0 ? $pontosEmitidos / $transacoesEarn->count() : 0;

        return [
            'total_transacoes' => $totalTransacoes,
            'pontos_emitidos' => $pontosEmitidos,
            'pontos_resgatados' => $pontosResgatados,
            'pontos_expirados' => $pontosExpirados,
            'ticket_medio_pontos' => round($ticketMedio, 2),
            'taxa_resgate' => $pontosEmitidos > 0 ? round(($pontosResgatados / $pontosEmitidos) * 100, 2) : 0,
            'periodo_dias' => $dias,
        ];
    }

    /**
     * Distribuição de usuários por nível VIP.
     */
    public function userDistributionByLevel(?int $empresaId = null): array
    {
        $query = User::query();

        if ($empresaId) {
            $query->whereHas('checkins', fn($q) => $q->where('empresa_id', $empresaId));
        }

        $distribuicao = $query->select('nivel', DB::raw('count(*) as total'))
            ->groupBy('nivel')
            ->orderBy('nivel')
            ->get()
            ->mapWithKeys(fn($item) => [$item->nivel ?? 'Sem nível' => $item->total])
            ->toArray();

        return $distribuicao;
    }

    /**
     * Top usuários por pontos lifetime.
     */
    public function topUsers(?int $empresaId = null, int $limit = 10): array
    {
        $query = User::query();

        if ($empresaId) {
            $query->whereHas('checkins', fn($q) => $q->where('empresa_id', $empresaId));
        }

        $topUsers = $query->orderBy('pontos_lifetime', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'pontos', 'pontos_lifetime', 'nivel'])
            ->toArray();

        return $topUsers;
    }

    /**
     * Dashboard completo de métricas.
     */
    public function dashboard(?int $empresaId = null): array
    {
        return [
            'cltv' => $this->calculateCLTV($empresaId),
            'retencao_30d' => $this->calculateRetention($empresaId, 30),
            'churn_90d' => $this->calculateChurn($empresaId, 90),
            'transacoes_30d' => $this->transactionMetrics($empresaId, 30),
            'distribuicao_niveis' => $this->userDistributionByLevel($empresaId),
            'top_usuarios' => $this->topUsers($empresaId, 10),
        ];
    }
}
