<?php

namespace App\Http\Controllers;

use App\Models\DiscountLevel;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Ponto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    /**
     * Listar níveis de desconto de uma empresa
     */
    public function getCompanyDiscountLevels(Request $request): JsonResponse
    {
        try {
            $empresaId = $request->input('empresa_id');
            
            if (!$empresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID da empresa é obrigatório'
                ], 400);
            }

            $levels = DiscountLevel::getActiveLevelsForCompany($empresaId);

            return response()->json([
                'success' => true,
                'data' => [
                    'discount_levels' => $levels->map(function($level) {
                        return [
                            'id' => $level->id,
                            'points_required' => $level->points_required,
                            'discount_percentage' => $level->discount_percentage,
                            'title' => $level->title,
                            'description' => $level->description,
                            'formatted_points' => $level->formatted_points,
                            'formatted_discount' => $level->formatted_discount
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar níveis de desconto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular desconto disponível para um cliente
     */
    public function calculateUserDiscount(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'empresa_id' => 'required|exists:empresas,id',
                'purchase_amount' => 'required|numeric|min:0.01'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $empresaId = $request->input('empresa_id');
            $purchaseAmount = $request->input('purchase_amount');

            // Obter pontos do usuário
            $userPoints = Ponto::where('user_id', $userId)->sum('pontos');

            // Encontrar melhor desconto disponível
            $bestDiscount = DiscountLevel::getBestDiscountForPoints($empresaId, $userPoints);
            
            // Próximo nível
            $nextLevel = DiscountLevel::getNextLevel($empresaId, $userPoints);

            $response = [
                'success' => true,
                'data' => [
                    'user_points' => $userPoints,
                    'purchase_amount' => $purchaseAmount,
                    'available_discount' => null,
                    'discount_amount' => 0,
                    'final_amount' => $purchaseAmount,
                    'next_level' => null,
                    'points_to_next_level' => null
                ]
            ];

            if ($bestDiscount) {
                $discountAmount = $bestDiscount->calculateDiscount($purchaseAmount);
                
                $response['data']['available_discount'] = [
                    'level_id' => $bestDiscount->id,
                    'title' => $bestDiscount->title,
                    'percentage' => $bestDiscount->discount_percentage,
                    'points_required' => $bestDiscount->points_required,
                    'description' => $bestDiscount->description
                ];
                $response['data']['discount_amount'] = $discountAmount;
                $response['data']['final_amount'] = $purchaseAmount - $discountAmount;
            }

            if ($nextLevel) {
                $pointsToNext = $nextLevel->points_required - $userPoints;
                
                $response['data']['next_level'] = [
                    'title' => $nextLevel->title,
                    'percentage' => $nextLevel->discount_percentage,
                    'points_required' => $nextLevel->points_required
                ];
                $response['data']['points_to_next_level'] = $pointsToNext;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular desconto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplicar desconto em uma compra
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'empresa_id' => 'required|exists:empresas,id',
                'discount_level_id' => 'required|exists:discount_levels,id',
                'purchase_amount' => 'required|numeric|min:0.01',
                'points_to_spend' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $empresaId = $request->input('empresa_id');
            $discountLevelId = $request->input('discount_level_id');
            $purchaseAmount = $request->input('purchase_amount');
            $pointsToSpend = $request->input('points_to_spend');

            // Verificar se usuário tem pontos suficientes
            $userPoints = Ponto::where('user_id', $userId)->sum('pontos');
            
            if ($userPoints < $pointsToSpend) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pontos insuficientes'
                ], 400);
            }

            // Verificar se o nível de desconto existe e pertence à empresa
            $discountLevel = DiscountLevel::where('id', $discountLevelId)
                                        ->where('empresa_id', $empresaId)
                                        ->where('is_active', true)
                                        ->first();

            if (!$discountLevel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nível de desconto inválido'
                ], 400);
            }

            // Verificar se usuário tem pontos para esse nível
            if ($userPoints < $discountLevel->points_required) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pontos insuficientes para este nível de desconto'
                ], 400);
            }

            // Calcular desconto
            $discountAmount = $discountLevel->calculateDiscount($purchaseAmount);
            $finalAmount = $purchaseAmount - $discountAmount;

            // Debitar pontos do usuário
            Ponto::create([
                'user_id' => $userId,
                'empresa_id' => $empresaId,
                'pontos' => -$pointsToSpend, // Negativo = gasto
                'tipo' => 'desconto',
                'descricao' => "Desconto {$discountLevel->title} aplicado - {$discountLevel->formatted_discount}",
                'valor_compra' => $purchaseAmount
            ]);

            // Registrar a aplicação do desconto
            $this->logDiscountUsage($userId, $empresaId, $discountLevelId, $purchaseAmount, $discountAmount, $pointsToSpend);

            return response()->json([
                'success' => true,
                'data' => [
                    'discount_applied' => [
                        'level' => $discountLevel->title,
                        'percentage' => $discountLevel->discount_percentage,
                        'amount' => $discountAmount
                    ],
                    'purchase_amount' => $purchaseAmount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'points_spent' => $pointsToSpend,
                    'remaining_points' => $userPoints - $pointsToSpend
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar desconto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configurar níveis de desconto para empresa (Admin)
     */
    public function configureCompanyDiscounts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'empresa_id' => 'required|exists:empresas,id',
                'discount_levels' => 'required|array|min:1',
                'discount_levels.*.points_required' => 'required|integer|min:1',
                'discount_levels.*.discount_percentage' => 'required|numeric|min:0.01|max:50',
                'discount_levels.*.title' => 'required|string|max:50',
                'discount_levels.*.description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $empresaId = $request->input('empresa_id');
            $discountLevels = $request->input('discount_levels');

            // Remover níveis existentes da empresa
            DiscountLevel::where('empresa_id', $empresaId)->delete();

            // Criar novos níveis
            $createdLevels = [];
            
            foreach ($discountLevels as $levelData) {
                $level = DiscountLevel::create([
                    'empresa_id' => $empresaId,
                    'points_required' => $levelData['points_required'],
                    'discount_percentage' => $levelData['discount_percentage'],
                    'title' => $levelData['title'],
                    'description' => $levelData['description'] ?? '',
                    'is_active' => true,
                    'applies_to_all_products' => true,
                    'applies_to_all_services' => true
                ]);

                $createdLevels[] = $level;
            }

            return response()->json([
                'success' => true,
                'message' => 'Níveis de desconto configurados com sucesso',
                'data' => [
                    'discount_levels' => $createdLevels
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao configurar descontos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar cliente por CPF/telefone para aplicar desconto
     */
    public function findCustomerForDiscount(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'required|string|min:3',
                'empresa_id' => 'required|exists:empresas,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de busca inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $search = $request->input('search');
            $empresaId = $request->input('empresa_id');

            // Buscar usuário por telefone, email ou nome
            $user = User::where(function($query) use ($search) {
                $query->where('telefone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
            })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ], 404);
            }

            // Obter pontos do cliente
            $userPoints = Ponto::where('user_id', $user->id)->sum('pontos');

            // Obter melhor desconto disponível
            $bestDiscount = DiscountLevel::getBestDiscountForPoints($empresaId, $userPoints);
            $nextLevel = DiscountLevel::getNextLevel($empresaId, $userPoints);

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'telefone' => $user->telefone
                    ],
                    'points' => $userPoints,
                    'available_discount' => $bestDiscount ? [
                        'id' => $bestDiscount->id,
                        'title' => $bestDiscount->title,
                        'percentage' => $bestDiscount->discount_percentage,
                        'points_required' => $bestDiscount->points_required
                    ] : null,
                    'next_level' => $nextLevel ? [
                        'title' => $nextLevel->title,
                        'percentage' => $nextLevel->discount_percentage,
                        'points_needed' => $nextLevel->points_required - $userPoints
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar uso do desconto para auditoria
     */
    private function logDiscountUsage($userId, $empresaId, $discountLevelId, $purchaseAmount, $discountAmount, $pointsSpent)
    {
        // Aqui você pode criar uma tabela de auditoria específica se necessário
        // Por enquanto, já está registrado na tabela de pontos
    }
}