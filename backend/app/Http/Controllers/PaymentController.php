<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PaymentController extends Controller
{
    /**
     * Processar compra de pontos
     */
    public function buyPoints(Request $request)
    {
        $request->validate([
            'package' => 'required|string|in:100,500,1000,2500',
            'payment_method' => 'required|string|in:mercadopago,pix',
            'user_id' => 'required|exists:users,id'
        ]);

        $packages = [
            '100' => ['points' => 100, 'price' => 10.00],
            '500' => ['points' => 500, 'price' => 45.00],
            '1000' => ['points' => 1000, 'price' => 85.00],
            '2500' => ['points' => 2500, 'price' => 200.00]
        ];

        $package = $packages[$request->package];
        $user = User::find($request->user_id);

        try {
            DB::beginTransaction();

            // Criar transação
            $transaction = [
                'user_id' => $user->id,
                'type' => 'purchase',
                'points' => $package['points'],
                'amount' => $package['price'],
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'created_at' => now()
            ];

            // Simular pagamento (em produção, integrar com gateway real)
            if ($request->payment_method === 'pix') {
                // Gerar QR Code PIX (simulado)
                $pixCode = $this->generatePixCode($package['price'], $user->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'PIX gerado com sucesso!',
                    'data' => [
                        'payment_type' => 'pix',
                        'qr_code' => $pixCode,
                        'amount' => $package['price'],
                        'points' => $package['points'],
                        'expires_in' => 900 // 15 minutos
                    ]
                ]);
            }

            if ($request->payment_method === 'mercadopago') {
                // Integração Mercado Pago (simulado)
                $mpResponse = $this->processMercadoPago($package, $user);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Redirecionando para pagamento...',
                    'data' => $mpResponse
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pagamento PIX
     */
    public function confirmPixPayment(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = User::find($request->user_id);
            
            // Em produção, verificar com o banco se o PIX foi pago
            // Por enquanto, vamos simular aprovação automática
            
            // Adicionar pontos ao usuário
            $points = 500; // Simular 500 pontos
            $user->pontos += $points;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => "Pagamento confirmado! {$points} pontos adicionados à sua conta.",
                'data' => [
                    'new_balance' => $user->pontos,
                    'points_added' => $points,
                    'new_level' => $this->calculateLevel($user->pontos)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao confirmar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar código PIX simulado
     */
    private function generatePixCode($amount, $userId)
    {
        return [
            'qr_code_text' => "00020126580014br.gov.bcb.pix0136{$userId}-{$amount}-" . time(),
            'qr_code_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
            'amount' => $amount,
            'recipient' => 'Tem de Tudo',
            'expires_at' => now()->addMinutes(15)->toISOString()
        ];
    }

    /**
     * Processar Mercado Pago simulado
     */
    private function processMercadoPago($package, $user)
    {
        return [
            'payment_url' => 'https://mercadopago.com.br/checkout/v1/redirect?pref_id=simulated',
            'preference_id' => 'MP-PREF-' . time(),
            'amount' => $package['price'],
            'points' => $package['points'],
            'status' => 'pending'
        ];
    }

    /**
     * Calcular nível baseado nos pontos
     */
    private function calculateLevel($points)
    {
        if ($points >= 10000) return 'Diamante';
        if ($points >= 5000) return 'Platina';
        if ($points >= 2500) return 'Ouro';
        if ($points >= 1000) return 'Prata';
        return 'Bronze';
    }
}