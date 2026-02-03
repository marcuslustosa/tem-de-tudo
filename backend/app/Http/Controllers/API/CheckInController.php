<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Empresa;
use App\Models\Ponto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckInController extends Controller
{
    /**
     * Fazer check-in em uma empresa
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'valor_compra' => 'nullable|numeric|min:0',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $empresaId = $request->empresa_id;

        try {
            DB::beginTransaction();

            // Busca empresa
            $empresa = Empresa::findOrFail($empresaId);

            // Calcula pontos (10 pontos por check-in padrão + multiplicador da empresa)
            $pontosBase = 10;
            $valorCompra = $request->valor_compra ?? 0;
            $pontosValor = floor($valorCompra); // 1 ponto por real gasto
            $multiplicador = $empresa->points_multiplier ?? 1.0;
            
            $pontosCalculados = floor(($pontosBase + $pontosValor) * $multiplicador);

            // Cria check-in
            $checkIn = CheckIn::create([
                'user_id' => $user->id,
                'empresa_id' => $empresaId,
                'qr_code_id' => $request->qr_code_id,
                'valor_compra' => $valorCompra,
                'pontos_calculados' => $pontosCalculados,
                'status' => 'approved', // Aprova automaticamente por enquanto
                'aprovado_em' => now(),
                'codigo_validacao' => strtoupper(substr(md5(uniqid()), 0, 6)),
            ]);

            // Registra pontos
            Ponto::create([
                'user_id' => $user->id,
                'empresa_id' => $empresaId,
                'checkin_id' => $checkIn->id,
                'pontos' => $pontosCalculados,
                'descricao' => "Check-in em {$empresa->nome}",
                'tipo' => 'earn',
            ]);

            // Atualiza pontos do usuário
            $user->increment('pontos', $pontosCalculados);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Check-in realizado! Você ganhou {$pontosCalculados} pontos!",
                'data' => [
                    'check_in' => [
                        'id' => $checkIn->id,
                        'empresa' => $empresa->nome,
                        'pontos_ganhos' => $pontosCalculados,
                        'total_pontos' => $user->pontos,
                        'data' => $checkIn->created_at->format('d/m/Y H:i'),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Histórico de check-ins do usuário
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $checkIns = CheckIn::with('empresa')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'check_ins' => $checkIns->map(function($checkIn) {
                    return [
                        'id' => $checkIn->id,
                        'empresa' => $checkIn->empresa->nome ?? 'Empresa removida',
                        'pontos_ganhos' => $checkIn->pontos_calculados,
                        'valor_compra' => $checkIn->valor_compra,
                        'status' => $checkIn->status,
                        'data' => $checkIn->created_at->format('d/m/Y H:i'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $checkIns->currentPage(),
                    'per_page' => $checkIns->perPage(),
                    'total' => $checkIns->total(),
                    'last_page' => $checkIns->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Detalhes de um check-in
     */
    public function show($id)
    {
        $checkIn = CheckIn::with('empresa', 'user')->findOrFail($id);

        // Verifica se o usuário tem permissão para ver este check-in
        if (auth()->id() !== $checkIn->user_id && auth()->user()->perfil !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'check_in' => [
                    'id' => $checkIn->id,
                    'usuario' => $checkIn->user->name,
                    'empresa' => $checkIn->empresa->nome ?? 'Empresa removida',
                    'pontos_ganhos' => $checkIn->pontos_calculados,
                    'valor_compra' => $checkIn->valor_compra,
                    'status' => $checkIn->status,
                    'codigo_validacao' => $checkIn->codigo_validacao,
                    'data' => $checkIn->created_at->format('d/m/Y H:i'),
                ]
            ]
        ]);
    }
}
