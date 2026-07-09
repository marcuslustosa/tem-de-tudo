<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BonusAdesao;
use App\Models\BonusAniversario;
use App\Models\CartaoFidelidade;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\Empresa;
use App\Services\BonusAdesaoService;
use App\Services\BonusAniversarioService;
use App\Services\CartaoFidelidadeService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Resgate de beneficios iniciado pelo proprio cliente.
 *
 * Modelo de negocio: o cliente escaneia o QR da empresa (vinculo) e, ja dentro
 * do app, SELECIONA o beneficio que quer usar. A validacao acontece aqui, com o
 * cliente autenticado atuando como customer e validator. Reaproveita os mesmos
 * services usados no fluxo antigo (empresa lendo o cliente), preservando todas as
 * regras de negocio (unicidade, elegibilidade, janelas, constraints de banco).
 */
class ClienteBeneficioController extends Controller
{
    public function __construct(
        private readonly BonusAdesaoService $bonusAdesaoService,
        private readonly BonusAniversarioService $bonusAniversarioService,
        private readonly CartaoFidelidadeService $cartaoService,
    ) {
    }

    public function resgatarBonusAdesao(int $id): JsonResponse
    {
        $user = Auth::user();

        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->naoEncontrado('Bonus de adesao nao encontrado.');
        }

        $empresa = $this->empresaVisivel((int) $bonus->empresa_id);
        if (!$empresa) {
            return $this->empresaIndisponivel();
        }

        try {
            $lookup = $this->bonusAdesaoService->validateBonus($empresa, $bonus, $user, $user);
        } catch (DomainException $e) {
            return $this->conflito($e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonus de adesao resgatado com sucesso.',
            'data' => $lookup,
        ]);
    }

    public function resgatarBonusAniversario(int $id): JsonResponse
    {
        $user = Auth::user();

        $bonus = BonusAniversario::query()->find($id);
        if (!$bonus) {
            return $this->naoEncontrado('Bonus aniversario nao encontrado.');
        }

        $empresa = $this->empresaVisivel((int) $bonus->empresa_id);
        if (!$empresa) {
            return $this->empresaIndisponivel();
        }

        try {
            $snapshot = $this->bonusAniversarioService->validateBonus($empresa, $bonus, $user, $user);
        } catch (DomainException $e) {
            return $this->conflito($e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonus aniversario resgatado com sucesso.',
            'data' => $snapshot,
        ]);
    }

    public function registrarVisitaFidelidade(int $id): JsonResponse
    {
        $user = Auth::user();

        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->naoEncontrado('Cartao fidelidade nao encontrado.');
        }

        $empresa = $this->empresaVisivel((int) $card->empresa_id);
        if (!$empresa) {
            return $this->empresaIndisponivel();
        }

        // Anti-farm: o cliente escaneia o QR (potencialmente uma foto do adesivo),
        // entao limitamos o ponto de visita a 1x por dia por empresa.
        $jaGanhouHoje = CartaoFidelidadeMovimento::query()
            ->where('cartao_fidelidade_id', $card->id)
            ->where('user_id', $user->id)
            ->where('tipo', CartaoFidelidadeMovimento::TYPE_EARNED)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($jaGanhouHoje) {
            return response()->json([
                'success' => false,
                'message' => 'Voce ja registrou uma visita hoje nesta empresa. Volte amanha para somar mais um ponto.',
            ], 429);
        }

        try {
            $snapshot = $this->cartaoService->addVisitPoint($empresa, $card, $user, $user);
        } catch (DomainException $e) {
            return $this->conflito($e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Visita registrada! Ponto de fidelidade adicionado.',
            'data' => $snapshot,
        ]);
    }

    public function resgatarRecompensaFidelidade(int $id): JsonResponse
    {
        $user = Auth::user();

        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->naoEncontrado('Cartao fidelidade nao encontrado.');
        }

        $empresa = $this->empresaVisivel((int) $card->empresa_id);
        if (!$empresa) {
            return $this->empresaIndisponivel();
        }

        try {
            $snapshot = $this->cartaoService->redeemReward($empresa, $card, $user, $user);
        } catch (DomainException $e) {
            return $this->conflito($e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Recompensa de fidelidade resgatada com sucesso.',
            'data' => $snapshot,
        ]);
    }

    private function empresaVisivel(int $empresaId): ?Empresa
    {
        if ($empresaId <= 0) {
            return null;
        }

        return Empresa::query()->publiclyVisible()->find($empresaId);
    }

    private function naoEncontrado(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    private function empresaIndisponivel(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Empresa indisponivel para resgate no momento.',
        ], 409);
    }

    private function conflito(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 409);
    }
}
