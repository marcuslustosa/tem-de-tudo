<?php

namespace App\Http\Controllers;

use App\Models\NpsResposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NpsController extends Controller
{
    /** Registra resposta NPS do cliente */
    public function responder(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nota'        => 'required|integer|min:0|max:10',
            'empresa_id'  => 'nullable|integer|exists:empresas,id',
            'promocao_id' => 'nullable|integer',
            'comentario'  => 'nullable|string|max:1000',
            'contexto'    => 'nullable|in:resgate,checkin,geral',
        ]);

        // Limitar: 1 NPS por empresa por dia
        $jaRespondeu = NpsResposta::where('user_id', $user->id)
            ->where('empresa_id', $request->empresa_id)
            ->whereDate('created_at', today())
            ->exists();

        if ($jaRespondeu) {
            return response()->json([
                'success' => false,
                'message' => 'Você já avaliou essa empresa hoje.',
            ], 429);
        }

        $nps = NpsResposta::create([
            'user_id'     => $user->id,
            'empresa_id'  => $request->empresa_id,
            'promocao_id' => $request->promocao_id,
            'nota'        => $request->nota,
            'comentario'  => $request->comentario,
            'contexto'    => $request->contexto ?? 'geral',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Obrigado pela sua avaliação!',
            'data'    => ['classificacao' => $nps->classificacao],
        ], 201);
    }

    /** Estatísticas NPS da empresa (para empresa autenticada) */
    public function estatisticasEmpresa()
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 403);
        }

        $respostas = NpsResposta::where('empresa_id', $empresa->id)->get();

        $total      = $respostas->count();
        $promotores = $respostas->where('nota', '>=', 9)->count();
        $detratores = $respostas->where('nota', '<=', 6)->count();
        $npsScore   = $total > 0 ? round((($promotores - $detratores) / $total) * 100, 1) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'total'      => $total,
                'promotores' => $promotores,
                'neutros'    => $respostas->whereBetween('nota', [7, 8])->count(),
                'detratores' => $detratores,
                'nps_score'  => $npsScore,
                'media_nota' => $total > 0 ? round($respostas->avg('nota'), 1) : null,
                'ultimas'    => $respostas->sortByDesc('created_at')
                                          ->take(10)
                                          ->values()
                                          ->map(fn ($r) => [
                                              'nota'       => $r->nota,
                                              'comentario' => $r->comentario,
                                              'data'       => $r->created_at?->format('d/m/Y'),
                                          ]),
            ],
        ]);
    }

    /** Estatísticas NPS globais (admin) */
    public function estatisticasAdmin()
    {
        $total      = NpsResposta::count();
        $promotores = NpsResposta::where('nota', '>=', 9)->count();
        $detratores = NpsResposta::where('nota', '<=', 6)->count();
        $npsScore   = $total > 0 ? round((($promotores - $detratores) / $total) * 100, 1) : null;

        $porEmpresa = NpsResposta::selectRaw('empresa_id, COUNT(*) as total, AVG(nota) as media')
            ->with('empresa:id,nome')
            ->groupBy('empresa_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => compact('total', 'promotores', 'detratores', 'npsScore', 'porEmpresa'),
        ]);
    }
}
