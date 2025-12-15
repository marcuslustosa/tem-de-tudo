<?php

namespace App\Http\Controllers;

use App\Models\InscricaoEmpresa;
use App\Models\CartaoFidelidadeProgresso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InscricaoController extends Controller
{
    /**
     * Listar empresas em que o cliente está inscrito
     */
    public function minhasInscricoes()
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem acessar inscrições'
            ], 403);
        }

        $inscricoes = InscricaoEmpresa::with(['empresa'])
            ->where('user_id', $user->id)
            ->orderBy('data_inscricao', 'desc')
            ->get();

        // Enriquecer com dados adicionais
        $resultado = $inscricoes->map(function ($inscricao) use ($user) {
            // Buscar progresso em cartões de fidelidade
            $progressoCartoes = CartaoFidelidadeProgresso::with('cartaoFidelidade')
                ->where('user_id', $user->id)
                ->whereHas('cartaoFidelidade', function ($query) use ($inscricao) {
                    $query->where('empresa_id', $inscricao->empresa_id);
                })
                ->get();

            return [
                'inscricao' => $inscricao,
                'empresa' => $inscricao->empresa,
                'progresso_cartoes' => $progressoCartoes
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }

    /**
     * Detalhes de uma inscrição específica
     */
    public function detalhesInscricao($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem acessar inscrições'
            ], 403);
        }

        $inscricao = InscricaoEmpresa::with('empresa')
            ->where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->first();

        if (!$inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Inscrição não encontrada'
            ], 404);
        }

        // Progresso em cartões
        $progressoCartoes = CartaoFidelidadeProgresso::with('cartaoFidelidade')
            ->where('user_id', $user->id)
            ->whereHas('cartaoFidelidade', function ($query) use ($empresa_id) {
                $query->where('empresa_id', $empresa_id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'inscricao' => $inscricao,
                'empresa' => $inscricao->empresa,
                'progresso_cartoes' => $progressoCartoes
            ]
        ]);
    }
}
