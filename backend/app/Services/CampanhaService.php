<?php

namespace App\Services;

use App\Models\CampanhaMultiplicador;
use App\Models\Empresa;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CampanhaService
{
    /**
     * Obtem o multiplicador ativo para uma empresa no momento atual.
     * Retorna 1.0 se não houver campanha ativa.
     * Usa cache de 5 minutos para performance.
     */
    public function getMultiplicadorAtivo(?int $empresaId = null): float
    {
        if (!$empresaId) {
            return 1.0;
        }

        $cacheKey = "campanha_multiplicador_{$empresaId}";

        return Cache::remember($cacheKey, 300, function () use ($empresaId) {
            $campanha = CampanhaMultiplicador::where('empresa_id', $empresaId)
                ->where('ativo', true)
                ->where('data_inicio', '<=', now())
                ->where('data_fim', '>=', now())
                ->orderBy('multiplicador', 'desc') // maior multiplicador
                ->first();

            return $campanha ? (float) $campanha->multiplicador : 1.0;
        });
    }

    /**
     * Cria uma nova campanha de multiplicador.
     */
    public function criarCampanha(array $data): CampanhaMultiplicador
    {
        $campanha = CampanhaMultiplicador::create([
            'empresa_id' => $data['empresa_id'],
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'multiplicador' => max(1.0, (float) $data['multiplicador']),
            'data_inicio' => Carbon::parse($data['data_inicio']),
            'data_fim' => Carbon::parse($data['data_fim']),
            'ativo' => $data['ativo'] ?? true,
        ]);

        // Limpa cache
        Cache::forget("campanha_multiplicador_{$campanha->empresa_id}");

        return $campanha;
    }

    /**
     * Atualiza uma campanha existente.
     */
    public function atualizarCampanha(int $campanhaId, array $data): CampanhaMultiplicador
    {
        $campanha = CampanhaMultiplicador::findOrFail($campanhaId);

        $campanha->update([
            'nome' => $data['nome'] ?? $campanha->nome,
            'descricao' => $data['descricao'] ?? $campanha->descricao,
            'multiplicador' => isset($data['multiplicador']) ? max(1.0, (float) $data['multiplicador']) : $campanha->multiplicador,
            'data_inicio' => isset($data['data_inicio']) ? Carbon::parse($data['data_inicio']) : $campanha->data_inicio,
            'data_fim' => isset($data['data_fim']) ? Carbon::parse($data['data_fim']) : $campanha->data_fim,
            'ativo' => $data['ativo'] ?? $campanha->ativo,
        ]);

        // Limpa cache
        Cache::forget("campanha_multiplicador_{$campanha->empresa_id}");

        return $campanha->fresh();
    }

    /**
     * Desativa uma campanha.
     */
    public function desativarCampanha(int $campanhaId): void
    {
        $campanha = CampanhaMultiplicador::findOrFail($campanhaId);
        $campanha->update(['ativo' => false]);
        Cache::forget("campanha_multiplicador_{$campanha->empresa_id}");
    }

    /**
     * Lista campanhas ativas de uma empresa.
     */
    public function listarCampanhasAtivas(int $empresaId): \Illuminate\Database\Eloquent\Collection
    {
        return CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('data_fim', '>=', now())
            ->orderBy('data_inicio', 'desc')
            ->get();
    }

    /**
     * Lista todas as campanhas de uma empresa (ativas e inativas).
     */
    public function listarTodasCampanhas(int $empresaId): \Illuminate\Database\Eloquent\Collection
    {
        return CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Aplica multiplicador da campanha ao valor base de pontos.
     */
    public function aplicarMultiplicador(int $pontosBase, ?int $empresaId = null): int
    {
        $multiplicador = $this->getMultiplicadorAtivo($empresaId);
        return (int) floor($pontosBase * $multiplicador);
    }

    /**
     * Obtem estatísticas de campanhas de uma empresa.
     */
    public function getEstatisticas(int $empresaId): array
    {
        $total = CampanhaMultiplicador::where('empresa_id', $empresaId)->count();
        $ativas = CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->count();
        $agendadas = CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('data_inicio', '>', now())
            ->count();
        $expiradas = CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->where('data_fim', '<', now())
            ->count();

        return [
            'total' => $total,
            'ativas_agora' => $ativas,
            'agendadas' => $agendadas,
            'expiradas' => $expiradas,
        ];
    }
}
