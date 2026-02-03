<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmpresaController extends Controller
{
    /**
     * Listar todas empresas ativas
     */
    public function index(Request $request)
    {
        $query = Empresa::where('ativo', true);

        // Filtro por categoria
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Busca por nome
        if ($request->has('busca')) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                  ->orWhere('descricao', 'ILIKE', "%{$busca}%");
            });
        }

        $empresas = $query->orderBy('nome')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'empresas' => $empresas->map(function($empresa) {
                    return [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                        'descricao' => $empresa->descricao,
                        'categoria' => $empresa->categoria,
                        'endereco' => $empresa->endereco,
                        'telefone' => $empresa->telefone,
                        'logo' => $empresa->logo,
                        'pontos_checkin' => 10, // Valor padrão
                        'ativo' => $empresa->ativo,
                    ];
                })
            ]
        ]);
    }

    /**
     * Detalhes de uma empresa
     */
    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'descricao' => $empresa->descricao,
                    'categoria' => $empresa->categoria,
                    'endereco' => $empresa->endereco,
                    'telefone' => $empresa->telefone,
                    'cnpj' => $empresa->cnpj,
                    'logo' => $empresa->logo,
                    'pontos_checkin' => 10,
                    'multiplicador' => $empresa->points_multiplier,
                    'ativo' => $empresa->ativo,
                    'criado_em' => $empresa->created_at->format('d/m/Y'),
                ]
            ]
        ]);
    }

    /**
     * Criar empresa (apenas usuário tipo empresa ou admin)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->perfil, ['empresa', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem criar estabelecimentos'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cnpj' => 'required|string|unique:empresas',
            'endereco' => 'required|string',
            'telefone' => 'required|string',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa = Empresa::create([
                'nome' => $request->nome,
                'cnpj' => $request->cnpj,
                'endereco' => $request->endereco,
                'telefone' => $request->telefone,
                'descricao' => $request->descricao,
                'categoria' => $request->categoria,
                'owner_id' => $user->id,
                'ativo' => true,
                'points_multiplier' => 1.0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa criada com sucesso!',
                'data' => [
                    'empresa' => [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                        'cnpj' => $empresa->cnpj,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar empresa
     */
    public function update(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);
        $user = $request->user();

        // Verifica permissão
        if ($empresa->owner_id !== $user->id && $user->perfil !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:255',
            'endereco' => 'sometimes|string',
            'telefone' => 'sometimes|string',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa->update($request->only([
                'nome', 'endereco', 'telefone', 'descricao', 'categoria'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Empresa atualizada com sucesso!',
                'data' => [
                    'empresa' => [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar empresa: ' . $e->getMessage()
            ], 500);
        }
    }
}
