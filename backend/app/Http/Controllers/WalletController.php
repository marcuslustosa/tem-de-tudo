<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\PontoTransacao;

/**
 * WalletController — Sistema de Fidelidade Completo
 * 
 * Gerencia cartão virtual, pontos, níveis e histórico
 */
class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Retorna dados do cartão de fidelidade do usuário
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'pontos' => $user->pontos ?? 0,
                'nivel' => $user->nivel ?? 'bronze',
                'nivel_formatado' => ucfirst($user->nivel ?? 'bronze'),
                'qr_code' => $this->gerarQRCode($user),
                'cor' => $this->corPorNivel($user->nivel),
                'proximos_pontos' => $this->pontosProximoNivel($user->pontos ?? 0),
            ],
        ]);
    }

    /**
     * Adiciona pontos ao usuário (estabelecimento)
     */
    public function adicionarPontos(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:users,id',
            'pontos' => 'required|integer|min:1|max:1000',
            'valor_compra' => 'required|numeric|min:0',
        ]);

        $cliente = User::findOrFail($request->cliente_id);
        $pontosAnteriores = $cliente->pontos ?? 0;
        $nivelAnterior = $cliente->nivel ?? 'bronze';

        // Adiciona pontos
        $cliente->pontos = $pontosAnteriores + $request->pontos;
        
        // Atualiza nível
        $cliente->nivel = $this->calcularNivel($cliente->pontos);
        $cliente->save();

        // Registra na tabela de transações
        PontoTransacao::create([
            'user_id' => $cliente->id,
            'pontos' => $request->pontos,
            'tipo' => 'adicao',
            'descricao' => "Compra no valor de R$ " . number_format($request->valor_compra, 2, ',', '.'),
            'valor_compra' => $request->valor_compra,
            'estabelecimento_id' => Auth::id(),
        ]);

        $nivelSubiu = $nivelAnterior !== $cliente->nivel;

        return response()->json([
            'success' => true,
            'message' => 'Pontos adicionados com sucesso!',
            'data' => [
                'pontos_anteriores' => $pontosAnteriores,
                'pontos_adicionados' => $request->pontos,
                'pontos_atuais' => $cliente->pontos,
                'nivel_anterior' => $nivelAnterior,
                'nivel_atual' => $cliente->nivel,
                'nivel_subiu' => $nivelSubiu,
            ],
        ]);
    }

    /**
     * Resgata pontos (troca por benefícios)
     */
    public function resgatarPontos(Request $request)
    {
        $request->validate([
            'pontos' => 'required|integer|min:1',
            'descricao' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        
        if (($user->pontos ?? 0) < $request->pontos) {
            return response()->json([
                'success' => false,
                'message' => 'Pontos insuficientes',
            ], 400);
        }

        $pontosAnteriores = $user->pontos;
        $nivelAnterior = $user->nivel;

        // Remove pontos
        $user->pontos = $pontosAnteriores - $request->pontos;
        
        // Recalcula nível
        $user->nivel = $this->calcularNivel($user->pontos);
        $user->save();

        // Registra resgate
        PontoTransacao::create([
            'user_id' => $user->id,
            'pontos' => -$request->pontos,
            'tipo' => 'resgate',
            'descricao' => $request->descricao,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pontos resgatados com sucesso!',
            'data' => [
                'pontos_anteriores' => $pontosAnteriores,
                'pontos_resgatados' => $request->pontos,
                'pontos_atuais' => $user->pontos,
                'nivel_anterior' => $nivelAnterior,
                'nivel_atual' => $user->nivel,
            ],
        ]);
    }

    /**
     * Histórico de transações de pontos
     */
    public function historico(Request $request)
    {
        $user = Auth::user();
        
        $transacoes = PontoTransacao::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transacoes,
        ]);
    }

    /**
     * Valida QR Code do cliente (para estabelecimento escanear)
     */
    public function validarQRCode(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        // Formato: CLIENT_123_hash
        $parts = explode('_', $request->qr_code);
        
        if (count($parts) !== 3 || $parts[0] !== 'CLIENT') {
            return response()->json([
                'success' => false,
                'message' => 'QR Code inválido',
            ], 400);
        }

        $userId = $parts[1];
        $cliente = User::find($userId);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado',
            ], 404);
        }

        // Valida hash
        $hashEsperado = md5($cliente->email);
        if ($parts[2] !== $hashEsperado) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code inválido',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'nome' => $cliente->name,
                'email' => $cliente->email,
                'pontos' => $cliente->pontos ?? 0,
                'nivel' => $cliente->nivel ?? 'bronze',
            ],
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function gerarQRCode($user): string
    {
        return 'CLIENT_' . $user->id . '_' . md5($user->email);
    }

    private function calcularNivel(int $pontos): string
    {
        if ($pontos >= 1000) return 'platina';
        if ($pontos >= 500) return 'ouro';
        if ($pontos >= 200) return 'prata';
        return 'bronze';
    }

    private function pontosProximoNivel(int $pontos): array
    {
        $niveis = [
            'bronze' => ['pontos' => 200, 'nome' => 'Prata'],
            'prata' => ['pontos' => 500, 'nome' => 'Ouro'],
            'ouro' => ['pontos' => 1000, 'nome' => 'Platina'],
            'platina' => ['pontos' => null, 'nome' => 'Máximo'],
        ];

        $nivelAtual = $this->calcularNivel($pontos);
        $proximoNivel = $niveis[$nivelAtual];

        if ($proximoNivel['pontos'] === null) {
            return [
                'nivel_atual' => 'platina',
                'proximo_nivel' => 'Platina (Máximo)',
                'pontos_faltando' => 0,
                'porcentagem' => 100,
            ];
        }

        $pontosFaltando = $proximoNivel['pontos'] - $pontos;
        $pontosMinimo = match($nivelAtual) {
            'bronze' => 0,
            'prata' => 200,
            'ouro' => 500,
            default => 0,
        };

        $porcentagem = (($pontos - $pontosMinimo) / ($proximoNivel['pontos'] - $pontosMinimo)) * 100;

        return [
            'nivel_atual' => $nivelAtual,
            'proximo_nivel' => $proximoNivel['nome'],
            'pontos_faltando' => $pontosFaltando,
            'porcentagem' => round($porcentagem, 2),
        ];
    }

    private function corPorNivel(?string $nivel): string
    {
        return match (strtolower($nivel ?? '')) {
            'prata' => '#93A3B8',
            'ouro' => '#D4A017',
            'platina' => '#A8B820',
            default => '#CD7F32', // bronze
        };
    }
}

