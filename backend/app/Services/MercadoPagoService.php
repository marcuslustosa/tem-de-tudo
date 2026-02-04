<?php

namespace App\Services;

use App\Models\Pagamento;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private $accessToken;
    private $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->baseUrl = config('services.mercadopago.sandbox') 
            ? 'https://api.mercadopago.com/sandbox' 
            : 'https://api.mercadopago.com';
    }

    /**
     * Criar um pagamento PIX
     */
    public function criarPagamentoPix(User $user, Produto $produto, array $dados = [])
    {
        try {
            // Calcular valor final com desconto do nível
            $nivel_info = $user->calcularNivel();
            $desconto_percentual = $this->getDescontoNivel($nivel_info['id']);
            
            $valor_original = $produto->preco * 100; // em centavos
            $valor_desconto = ($valor_original * $desconto_percentual) / 100;
            $valor_final = $valor_original - $valor_desconto;

            // Criar registro no banco ANTES da requisição MP
            $pagamento = Pagamento::create([
                'user_id' => $user->id,
                'empresa_id' => $produto->empresa_id,
                'produto_id' => $produto->id,
                'valor' => $valor_original,
                'valor_desconto' => $valor_desconto,
                'valor_final' => $valor_final,
                'pontos_gerados' => $this->calcularPontosGeracao($user, $valor_final),
                'status' => 'pending',
                'metodo_pagamento' => 'pix'
            ]);

            // Dados para o Mercado Pago
            $payment_data = [
                'transaction_amount' => $valor_final / 100, // MP espera em reais
                'description' => "Compra: {$produto->nome} - {$produto->empresa->nome}",
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $user->email,
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                    'identification' => [
                        'type' => 'CPF',
                        'number' => '11122233344' // Usar CPF real em produção
                    ]
                ],
                'external_reference' => "TDT-{$pagamento->id}",
                'notification_url' => route('webhook.mercadopago')
            ];

            // Requisição para o Mercado Pago
            $response = Http::withToken($this->accessToken)
                          ->post("{$this->baseUrl}/v1/payments", $payment_data);

            if ($response->successful()) {
                $mp_data = $response->json();
                
                // Atualizar pagamento com dados do MP
                $pagamento->update([
                    'mercadopago_payment_id' => $mp_data['id'],
                    'qr_code_data' => $mp_data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                    'link_pagamento' => $mp_data['point_of_interaction']['transaction_data']['ticket_url'] ?? null,
                    'data_expiracao' => now()->addMinutes(30), // PIX expira em 30min
                    'detalhes_pagamento' => $mp_data
                ]);

                return [
                    'success' => true,
                    'pagamento' => $pagamento,
                    'qr_code' => $mp_data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                    'qr_code_text' => $mp_data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                    'link_pagamento' => $mp_data['point_of_interaction']['transaction_data']['ticket_url'] ?? null
                ];
            } else {
                Log::error('Erro MercadoPago', [
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'message' => 'Erro ao processar pagamento. Tente novamente.',
                    'error' => $response->json()['message'] ?? 'Erro desconhecido'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro interno. Tente novamente.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar webhook do Mercado Pago
     */
    public function processarWebhook($data)
    {
        try {
            if (!isset($data['data']['id']) || $data['type'] !== 'payment') {
                return ['success' => false, 'message' => 'Webhook inválido'];
            }

            $mp_payment_id = $data['data']['id'];
            
            // Buscar detalhes do pagamento no MP
            $response = Http::withToken($this->accessToken)
                          ->get("{$this->baseUrl}/v1/payments/{$mp_payment_id}");

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Erro ao consultar pagamento'];
            }

            $mp_payment = $response->json();
            
            // Encontrar pagamento no banco
            $pagamento = Pagamento::where('mercadopago_payment_id', $mp_payment_id)->first();
            
            if (!$pagamento) {
                return ['success' => false, 'message' => 'Pagamento não encontrado'];
            }

            // Atualizar status
            $old_status = $pagamento->status;
            $pagamento->status = $mp_payment['status'];
            $pagamento->webhook_events = array_merge(
                $pagamento->webhook_events ?? [], 
                [now()->toISOString() => $mp_payment]
            );
            $pagamento->save();

            // Se foi aprovado, processar pontos
            if ($mp_payment['status'] === 'approved' && $old_status !== 'approved') {
                $this->processarPagamentoAprovado($pagamento);
            }

            return ['success' => true, 'message' => 'Webhook processado'];

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return ['success' => false, 'message' => 'Erro interno'];
        }
    }

    /**
     * Processar pagamento aprovado
     */
    private function processarPagamentoAprovado(Pagamento $pagamento)
    {
        $user = $pagamento->user;
        
        // Adicionar pontos ao usuário
        $user->pontos += $pagamento->pontos_gerados;
        $user->save();

        // Registrar no histórico de pontos
        $user->pontos_historico()->create([
            'pontos' => $pagamento->pontos_gerados,
            'tipo' => 'compra',
            'descricao' => "Compra: {$pagamento->produto->nome}",
            'empresa_id' => $pagamento->empresa_id,
            'data_expiracao' => now()->addYear()
        ]);

        // Processar para sistema VIP (valor gasto)
        $badges_novos = $user->processarCompra($pagamento->valor_final);

        // Log para auditoria
        Log::info('Pagamento aprovado processado', [
            'pagamento_id' => $pagamento->id,
            'user_id' => $user->id,
            'pontos_adicionados' => $pagamento->pontos_gerados,
            'badges_novos' => count($badges_novos)
        ]);
    }

    /**
     * Calcular desconto baseado no nível
     */
    private function getDescontoNivel($nivel_id)
    {
        switch ($nivel_id) {
            case 4: return 15; // Diamante - 15%
            case 3: return 10; // Ouro - 10%
            case 2: return 5;  // Prata - 5%
            default: return 0;  // Bronze - 0%
        }
    }

    /**
     * Calcular pontos que serão gerados
     */
    private function calcularPontosGeracao(User $user, $valor_centavos)
    {
        $nivel_info = $user->calcularNivel();
        $pontos_base = intval($valor_centavos / 100); // 1 ponto por real
        
        return intval($pontos_base * $nivel_info['multiplicador']);
    }

    /**
     * Consultar status de um pagamento
     */
    public function consultarPagamento($mercadopago_payment_id)
    {
        try {
            $response = Http::withToken($this->accessToken)
                          ->get("{$this->baseUrl}/v1/payments/{$mercadopago_payment_id}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Erro ao consultar pagamento', ['error' => $e->getMessage()]);
            return null;
        }
    }
}