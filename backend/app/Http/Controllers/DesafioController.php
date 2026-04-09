<?php

namespace App\Http\Controllers;

use App\Models\Desafio;
use App\Models\DesafioProgresso;
use App\Models\Ponto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesafioController extends Controller
{
    // ============ PÚBLICO / CLIENTE ============

    /** Lista desafios ativos (com progresso do usuário logado) */
    public function index(Request $request)
    {
        $user = Auth::user();

        $desafios = Desafio::ativos()
            ->where(fn ($q) => $q->whereNull('empresa_id')
                ->orWhere('empresa_id', $request->query('empresa_id')))
            ->orderBy('data_fim')
            ->get();

        $ids = $desafios->pluck('id');
        $progressos = DesafioProgresso::where('user_id', $user->id)
            ->whereIn('desafio_id', $ids)
            ->get()
            ->keyBy('desafio_id');

        $desafios->transform(function ($d) use ($progressos) {
            $prog = $progressos->get($d->id);
            $d->progresso_atual = $prog ? $prog->progresso_atual : 0;
            $d->concluido       = $prog ? (bool) $prog->concluido : false;
            $d->percentual      = $d->meta > 0 ? min(100, round(($d->progresso_atual / $d->meta) * 100)) : 0;
            return $d;
        });

        return response()->json(['success' => true, 'data' => $desafios]);
    }

    /** Detalhes de um desafio */
    public function show($id)
    {
        $user    = Auth::user();
        $desafio = Desafio::findOrFail($id);

        $prog = DesafioProgresso::where('user_id', $user->id)
            ->where('desafio_id', $id)
            ->first();

        $desafio->progresso_atual = $prog ? $prog->progresso_atual : 0;
        $desafio->concluido       = $prog ? (bool) $prog->concluido : false;
        $desafio->percentual      = $desafio->meta > 0
            ? min(100, round(($desafio->progresso_atual / $desafio->meta) * 100))
            : 0;

        return response()->json(['success' => true, 'data' => $desafio]);
    }

    // ============ EMPRESA ============

    /** Cria um desafio para a empresa autenticada */
    public function store(Request $request)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 403);
        }

        $request->validate([
            'nome'                  => 'required|string|max:120',
            'descricao'             => 'nullable|string',
            'tipo'                  => 'required|in:checkins,pontos,resgates,streak,indicacoes',
            'meta'                  => 'required|integer|min:1',
            'recompensa_pontos'     => 'integer|min:0',
            'recompensa_descricao'  => 'nullable|string|max:255',
            'data_inicio'           => 'required|date',
            'data_fim'              => 'required|date|after:data_inicio',
        ]);

        $desafio = Desafio::create(array_merge(
            $request->only(['nome', 'descricao', 'tipo', 'meta', 'recompensa_pontos', 'recompensa_descricao', 'data_inicio', 'data_fim']),
            ['empresa_id' => $empresa->id, 'ativo' => true]
        ));

        return response()->json(['success' => true, 'data' => $desafio], 201);
    }

    /** Atualiza desafio da empresa */
    public function update(Request $request, $id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;
        $desafio = Desafio::where('id', $id)->where('empresa_id', $empresa?->id)->firstOrFail();

        $request->validate([
            'nome'      => 'sometimes|string|max:120',
            'descricao' => 'nullable|string',
            'data_fim'  => 'sometimes|date',
            'ativo'     => 'sometimes|boolean',
        ]);

        $desafio->update($request->only(['nome', 'descricao', 'data_fim', 'ativo']));

        return response()->json(['success' => true, 'data' => $desafio]);
    }

    /** Desativa desafio */
    public function destroy($id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;
        $desafio = Desafio::where('id', $id)->where('empresa_id', $empresa?->id)->firstOrFail();

        $desafio->update(['ativo' => false]);

        return response()->json(['success' => true]);
    }

    // ============ ADMIN ============

    /** Lista todos os desafios (admin) */
    public function adminIndex()
    {
        $desafios = Desafio::with('empresa:id,nome')
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $desafios]);
    }

    /** Cria desafio global (sem empresa) */
    public function adminStore(Request $request)
    {
        $request->validate([
            'nome'              => 'required|string|max:120',
            'tipo'              => 'required|in:checkins,pontos,resgates,streak,indicacoes',
            'meta'              => 'required|integer|min:1',
            'recompensa_pontos' => 'integer|min:0',
            'data_inicio'       => 'required|date',
            'data_fim'          => 'required|date|after:data_inicio',
        ]);

        $desafio = Desafio::create(array_merge(
            $request->only(['nome', 'descricao', 'tipo', 'meta', 'recompensa_pontos', 'recompensa_descricao', 'data_inicio', 'data_fim']),
            ['empresa_id' => null, 'ativo' => true]
        ));

        return response()->json(['success' => true, 'data' => $desafio], 201);
    }
}
