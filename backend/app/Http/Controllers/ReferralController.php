<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /**
     * Retorna o código de indicação do usuário autenticado.
     * Se ainda não tiver, gera automaticamente.
     */
    public function meuCodigo(Request $request): JsonResponse
    {
        $user = $request->user();

        if (empty($user->referral_code)) {
            $user->referral_code = $this->gerarCodigoUnico();
            $user->save();
        }

        $stats = $this->calcularEstatisticas($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code'       => $user->referral_code,
                'link_indicacao'      => url('/') . '?ref=' . $user->referral_code,
                'total_indicados'     => $stats['total_indicados'],
                'pontos_ganhos'       => $stats['pontos_ganhos'],
                'indicados_recentes'  => $stats['indicados_recentes'],
            ],
        ]);
    }

    /**
     * Estatísticas completas das indicações.
     */
    public function estatisticas(Request $request): JsonResponse
    {
        $user = $request->user();

        if (empty($user->referral_code)) {
            $user->referral_code = $this->gerarCodigoUnico();
            $user->save();
        }

        $stats = $this->calcularEstatisticas($user->id);

        $indicados = DB::table('users')
            ->where('referred_by', $user->id)
            ->select('id', 'name', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($u) => [
                'nome'       => $u->name,
                'membro_em'  => $u->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code'   => $user->referral_code,
                'link_indicacao'  => url('/') . '?ref=' . $user->referral_code,
                'total_indicados' => $stats['total_indicados'],
                'pontos_ganhos'   => $stats['pontos_ganhos'],
                'indicados'       => $indicados,
            ],
        ]);
    }

    // ─── Internal helpers ───────────────────────────────────────────────────

    private function calcularEstatisticas(int $userId): array
    {
        $totalIndicados = DB::table('users')->where('referred_by', $userId)->count();

        $pontosGanhos = DB::table('pontos')
            ->where('user_id', $userId)
            ->where('tipo', 'bonus_indicacao')
            ->sum('pontos');

        $indicadosRecentes = DB::table('users')
            ->where('referred_by', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('name');

        return [
            'total_indicados'   => $totalIndicados,
            'pontos_ganhos'     => (int) $pontosGanhos,
            'indicados_recentes'=> $indicadosRecentes,
        ];
    }

    private function gerarCodigoUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(8));
        } while (DB::table('users')->where('referral_code', $codigo)->exists());

        return $codigo;
    }
}
