<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promocao;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromocaoController extends Controller
{
    /**
     * Listar promoções ativas
     */
    public function index(Request $request)
    {
        $query = Promocao::with('empresa')
            ->where('ativo', true)
            ->where(function($q) {
                $q->whereNull('data_fim')
                  ->orWhere('data_fim', '>=', now());
            });

        // Filtro por empresa
        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $promocoes = $query->orderBy('pontos_necessarios', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'promocoes' => $promocoes->map(function($promo) {
                    return [
                        'id' => $promo->id,
                        'titulo' => $promo->titulo,
                        'descricao' => $promo->descricao,
                        'pontos_necessarios' => $promo->pontos_necessarios,
                        'percentual_desconto' => $promo->percentual_desconto,
                        'valor_desconto' => $promo->valor_desconto,
                        'tipo_recompensa' => $promo->tipo_recompensa,
                        'data_inicio' => $promo->data_inicio ? $promo->data_inicio->format('d/m/Y') : null,
                        'data_fim' => $promo->data_fim ? $promo->data_fim->format('d/m/Y') : null,
                        'ativo' => $promo->ativo,
                        'empresa' => [
                            'id' => $promo->empresa->id,
                            'nome' => $promo->empresa->nome,
                            'logo' => $promo->empresa->logo,
                        ]
                    ];
                })
            ]
        ]);
    }

    /**
     * Detalhes de uma promoção
     */
    public function show($id)
    {
        $promo = Promocao::with('empresa')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'promocao' => [
                    'id' => $promo->id,
                    'titulo' => $promo->titulo,
                    'descricao' => $promo->descricao,
                    'pontos_necessarios' => $promo->pontos_necessarios,
                    'percentual_desconto' => $promo->percentual_desconto,
                    'valor_desconto' => $promo->valor_desconto,
                    'tipo_recompensa' => $promo->tipo_recompensa,
                    'data_inicio' => $promo->data_inicio ? $promo->data_inicio->format('d/m/Y') : null,
                    'data_fim' => $promo->data_fim ? $promo->data_fim->format('d/m/Y') : null,
                    'ativo' => $promo->ativo,
                    'empresa' => [
                        'id' => $promo->empresa->id,
                        'nome' => $promo->empresa->nome,
                        'logo' => $promo->empresa->logo,
                        'categoria' => $promo->empresa->categoria,
                    ]
                ]
            ]
        ]);
    }

    /**
     * Resgatar promoção (trocar pontos por cupom)
     */
    public function resgatar(Request $request, $id)
    {
        $user = $request->user();
        $promocao = Promocao::with('empresa')->findOrFail($id);

        // Verifica se promoção está ativa
        if (!$promocao->ativo) {
            return response()->json([
                'success' => false,
                'message' => 'Esta promoção não está mais ativa'
            ], 400);
        }

        // Verifica se não expirou
        if ($promocao->data_fim && $promocao->data_fim < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta promoção já expirou'
            ], 400);
        }

        // Verifica se usuário tem pontos suficientes
        if ($user->pontos < $promocao->pontos_necessarios) {
            return response()->json([
                'success' => false,
                'message' => "Você precisa de {$promocao->pontos_necessarios} pontos. Você tem apenas {$user->pontos}."
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduz pontos do usuário
            $user->pontos -= $promocao->pontos_necessarios;
            $user->save();

            // Gera código único do cupom
            $codigo = 'CUP' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Cria cupom
            $cupom = Coupon::create([
                'codigo' => $codigo,
                'user_id' => $user->id,
                'empresa_id' => $promocao->empresa_id,
                'promocao_id' => $promocao->id,
                'percentual_desconto' => $promocao->percentual_desconto,
                'valor_desconto' => $promocao->valor_desconto,
                'tipo_recompensa' => $promocao->tipo_recompensa,
                'data_validade' => now()->addDays(30), // Válido por 30 dias
                'usado' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Promoção resgatada com sucesso!',
                'data' => [
                    'cupom' => [
                        'id' => $cupom->id,
                        'codigo' => $cupom->codigo,
                        'descricao' => $promocao->descricao,
                        'percentual_desconto' => $cupom->percentual_desconto,
                        'valor_desconto' => $cupom->valor_desconto,
                        'tipo_recompensa' => $cupom->tipo_recompensa,
                        'data_validade' => $cupom->data_validade->format('d/m/Y'),
                        'empresa' => [
                            'nome' => $promocao->empresa->nome,
                            'logo' => $promocao->empresa->logo,
                        ]
                    ],
                    'pontos_restantes' => $user->pontos
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao resgatar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar cupons do usuário
     */
    public function meusCupons(Request $request)
    {
        $user = $request->user();

        $cupons = Coupon::with('empresa')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'cupons' => $cupons->map(function($cupom) {
                    return [
                        'id' => $cupom->id,
                        'codigo' => $cupom->codigo,
                        'percentual_desconto' => $cupom->percentual_desconto,
                        'valor_desconto' => $cupom->valor_desconto,
                        'tipo_recompensa' => $cupom->tipo_recompensa,
                        'data_validade' => $cupom->data_validade->format('d/m/Y'),
                        'usado' => $cupom->usado,
                        'data_uso' => $cupom->data_uso ? $cupom->data_uso->format('d/m/Y H:i') : null,
                        'empresa' => [
                            'id' => $cupom->empresa->id,
                            'nome' => $cupom->empresa->nome,
                            'logo' => $cupom->empresa->logo,
                        ]
                    ];
                })
            ]
        ]);
    }

    /**
     * Usar cupom
     */
    public function usarCupom(Request $request, $id)
    {
        $user = $request->user();
        $cupom = Coupon::with('empresa')->findOrFail($id);

        // Verifica se cupom pertence ao usuário
        if ($cupom->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupom não pertence a você'
            ], 403);
        }

        // Verifica se já foi usado
        if ($cupom->usado) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupom já foi utilizado'
            ], 400);
        }

        // Verifica validade
        if ($cupom->data_validade < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupom está expirado'
            ], 400);
        }

        try {
            $cupom->usado = true;
            $cupom->data_uso = now();
            $cupom->save();

            return response()->json([
                'success' => true,
                'message' => 'Cupom utilizado com sucesso!',
                'data' => [
                    'cupom' => [
                        'codigo' => $cupom->codigo,
                        'usado' => $cupom->usado,
                        'data_uso' => $cupom->data_uso->format('d/m/Y H:i'),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao usar cupom: ' . $e->getMessage()
            ], 500);
        }
    }
}
