<?php

namespace App\Http\Controllers;

use App\Models\AjustePontos;
use App\Models\Ponto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AjustePontosController extends Controller
{
    /** Aplica ajuste manual de pontos a um usuário (admin) */
    public function ajustar(Request $request, $userId)
    {
        $request->validate([
            'pontos' => 'required|integer|not_in:0',
            'motivo' => 'required|string|max:255',
        ]);

        $admin  = Auth::user();
        $target = User::where('perfil', 'cliente')->findOrFail($userId);

        DB::transaction(function () use ($request, $admin, $target) {
            // Garante que não fique negativo
            if ($request->pontos < 0 && $target->pontos + $request->pontos < 0) {
                abort(422, 'O usuário não possui pontos suficientes para esse débito.');
            }

            $target->increment('pontos', $request->pontos);

            if ($request->pontos > 0) {
                $target->increment('pontos_lifetime', $request->pontos);
            }

            Ponto::create([
                'user_id'  => $target->id,
                'pontos'   => $request->pontos,
                'tipo'     => $request->pontos > 0 ? 'ajuste_credito' : 'ajuste_debito',
                'descricao' => "Ajuste manual: {$request->motivo}",
                'data'     => now(),
            ]);

            AjustePontos::create([
                'user_id'  => $target->id,
                'admin_id' => $admin->id,
                'pontos'   => $request->pontos,
                'motivo'   => $request->motivo,
            ]);
        });

        $target->refresh();

        return response()->json([
            'success'        => true,
            'message'        => 'Pontos ajustados com sucesso.',
            'pontos_atuais'  => $target->pontos,
        ]);
    }

    /** Histórico de ajustes de um usuário */
    public function historico($userId)
    {
        $ajustes = AjustePontos::where('user_id', $userId)
            ->with('admin:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $ajustes]);
    }

    /** Histórico global de ajustes (admin) */
    public function historicoGlobal()
    {
        $ajustes = AjustePontos::with(['user:id,name,email', 'admin:id,name'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $ajustes]);
    }
}
