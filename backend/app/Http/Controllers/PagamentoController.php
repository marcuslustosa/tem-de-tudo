<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use App\Models\Produto;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagamentoController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $mercadoPagoService)
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    /**
     * Criar um novo pagamento PIX
     */
    public function criarPagamentoPix(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id'
        ]);

        $user = $request->user();
        $produto = Produto::with('empresa')->find($request->produto_id);

        if (!$produto || !$produto->ativo) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado ou inativo'
            ], 404);
        }

        $resultado = $this->mercadoPagoService->criarPagamentoPix($user, $produto);

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Pagamento PIX criado com sucesso',
                'pagamento' => [
                    'id' => $resultado['pagamento']->id,
                    'valor_original' => $resultado['pagamento']->getValorFormatadoAttribute(),
                    'valor_final' => $resultado['pagamento']->getValorFinalFormatadoAttribute(),
                    'pontos_gerados' => $resultado['pagamento']->pontos_gerados,
                    'qr_code_base64' => $resultado['qr_code'],
                    'qr_code_text' => $resultado['qr_code_text'],
                    'link_pagamento' => $resultado['link_pagamento'],
                    'expiracao' => $resultado['pagamento']->data_expiracao
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['message'],
            'error' => $resultado['error'] ?? null
        ], 400);
    }

    /**
     * Listar pagamentos do usuário
     */
    public function meusPagamentos(Request $request)
    {
        $user = $request->user();
        
        $pagamentos = $user->pagamentos()
                           ->with(['produto', 'empresa'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(20);

        return response()->json([
            'success' => true,
            'pagamentos' => $pagamentos->items(),
            'pagination' => [
                'current_page' => $pagamentos->currentPage(),
                'total_pages' => $pagamentos->lastPage(),
                'total' => $pagamentos->total()
            ]
        ]);
    }

    /**
     * Consultar status de um pagamento
     */
    public function consultarStatus($id, Request $request)
    {
        $user = $request->user();
        $pagamento = Pagamento::where('id', $id)
                             ->where('user_id', $user->id)
                             ->first();

        if (!$pagamento) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não encontrado'
            ], 404);
        }

        // Se tem ID do MP, consultar status atualizado
        if ($pagamento->mercadopago_payment_id) {
            $mp_data = $this->mercadoPagoService->consultarPagamento($pagamento->mercadopago_payment_id);
            
            if ($mp_data && $mp_data['status'] !== $pagamento->status) {
                $pagamento->status = $mp_data['status'];
                $pagamento->save();
            }
        }

        return response()->json([
            'success' => true,
            'pagamento' => [
                'id' => $pagamento->id,
                'status' => $pagamento->status,
                'status_text' => $this->getStatusTexto($pagamento->status),
                'valor_final' => $pagamento->getValorFinalFormatadoAttribute(),
                'pontos_gerados' => $pagamento->pontos_gerados,
                'created_at' => $pagamento->created_at,
                'produto' => $pagamento->produto,
                'empresa' => $pagamento->empresa
            ]
        ]);
    }

    /**
     * Webhook do Mercado Pago
     */
    public function webhook(Request $request)
    {
        Log::info('Webhook MercadoPago recebido', $request->all());

        $resultado = $this->mercadoPagoService->processarWebhook($request->all());

        return response()->json($resultado);
    }

    /**
     * Cancelar um pagamento pendente
     */
    public function cancelar($id, Request $request)
    {
        $user = $request->user();
        $pagamento = Pagamento::where('id', $id)
                             ->where('user_id', $user->id)
                             ->where('status', 'pending')
                             ->first();

        if (!$pagamento) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não encontrado ou não pode ser cancelado'
            ], 404);
        }

        $pagamento->status = 'cancelled';
        $pagamento->save();

        return response()->json([
            'success' => true,
            'message' => 'Pagamento cancelado com sucesso'
        ]);
    }

    /**
     * Estatísticas de pagamentos para empresa
     */
    public function estatisticasEmpresa(Request $request)
    {
        $user = $request->user();
        
        if ($user->perfil !== 'empresa' || !$user->empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        $empresa = $user->empresa;
        
        $hoje = now()->startOfDay();
        $este_mes = now()->startOfMonth();
        
        $stats = [
            'hoje' => [
                'vendas' => $empresa->pagamentos()->whereDate('created_at', $hoje)->aprovados()->count(),
                'receita' => $empresa->pagamentos()->whereDate('created_at', $hoje)->aprovados()->sum('valor_final'),
                'pontos_distribuidos' => $empresa->pagamentos()->whereDate('created_at', $hoje)->aprovados()->sum('pontos_gerados')
            ],
            'este_mes' => [
                'vendas' => $empresa->pagamentos()->where('created_at', '>=', $este_mes)->aprovados()->count(),
                'receita' => $empresa->pagamentos()->where('created_at', '>=', $este_mes)->aprovados()->sum('valor_final'),
                'pontos_distribuidos' => $empresa->pagamentos()->where('created_at', '>=', $este_mes)->aprovados()->sum('pontos_gerados')
            ],
            'total' => [
                'vendas' => $empresa->pagamentos()->aprovados()->count(),
                'receita' => $empresa->pagamentos()->aprovados()->sum('valor_final'),
                'pontos_distribuidos' => $empresa->pagamentos()->aprovados()->sum('pontos_gerados')
            ]
        ];

        return response()->json([
            'success' => true,
            'estatisticas' => $stats
        ]);
    }

    /**
     * Converter status do MP para texto amigável
     */
    private function getStatusTexto($status)
    {
        $status_map = [
            'pending' => 'Aguardando Pagamento',
            'approved' => 'Aprovado',
            'authorized' => 'Autorizado',
            'in_process' => 'Em Processamento',
            'in_mediation' => 'Em Mediação',
            'rejected' => 'Rejeitado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Estornado',
            'charged_back' => 'Chargeback'
        ];

        return $status_map[$status] ?? 'Status Desconhecido';
    }
}