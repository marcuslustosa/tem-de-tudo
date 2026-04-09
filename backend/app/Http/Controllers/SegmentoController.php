<?php

namespace App\Http\Controllers;

use App\Models\Segmento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SegmentoController extends Controller
{
    /** Lista todos os segmentos */
    public function index()
    {
        $segmentos = Segmento::withCount('usuarios')->orderBy('nome')->get();
        return response()->json(['success' => true, 'data' => $segmentos]);
    }

    /** Cria segmento */
    public function store(Request $request)
    {
        $request->validate([
            'nome'      => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'criterios' => 'required|array',
        ]);

        $segmento = Segmento::create($request->only(['nome', 'descricao', 'criterios']));

        // Popular imediatamente
        $this->sincronizar($segmento);

        return response()->json([
            'success' => true,
            'data'    => $segmento->loadCount('usuarios'),
        ], 201);
    }

    /** Atualiza segmento e re-sincroniza usuários */
    public function update(Request $request, $id)
    {
        $segmento = Segmento::findOrFail($id);

        $request->validate([
            'nome'      => 'sometimes|string|max:100',
            'criterios' => 'sometimes|array',
            'ativo'     => 'sometimes|boolean',
        ]);

        $segmento->update($request->only(['nome', 'descricao', 'criterios', 'ativo']));

        if ($request->has('criterios')) {
            $this->sincronizar($segmento);
        }

        return response()->json(['success' => true, 'data' => $segmento->loadCount('usuarios')]);
    }

    /** Remove segmento */
    public function destroy($id)
    {
        Segmento::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    /** Lista usuários do segmento */
    public function usuarios($id)
    {
        $segmento = Segmento::findOrFail($id);
        $usuarios = $segmento->usuarios()
            ->select('users.id', 'users.name', 'users.email', 'users.pontos', 'users.nivel')
            ->paginate(50);

        return response()->json(['success' => true, 'data' => $usuarios]);
    }

    /** Sincroniza usuários com os critérios do segmento */
    public function sincronizarManual($id)
    {
        $segmento = Segmento::findOrFail($id);
        $total    = $this->sincronizar($segmento);

        return response()->json([
            'success' => true,
            'message' => "{$total} usuários sincronizados",
        ]);
    }

    /**
     * Aplica os critérios do segmento e popula a pivot table.
     */
    private function sincronizar(Segmento $segmento): int
    {
        $criterios = $segmento->criterios;

        $query = User::where('perfil', 'cliente');

        if (!empty($criterios['nivel'])) {
            $niveis = (array) $criterios['nivel'];
            $query->whereIn('nivel', $niveis);
        }

        if (!empty($criterios['pontos_min'])) {
            $query->where('pontos', '>=', $criterios['pontos_min']);
        }

        if (!empty($criterios['pontos_max'])) {
            $query->where('pontos', '<=', $criterios['pontos_max']);
        }

        if (!empty($criterios['streak_min'])) {
            $query->where('streak_atual', '>=', $criterios['streak_min']);
        }

        if (!empty($criterios['dias_ausencia_max'])) {
            $diasAusencia = now()->subDays((int) $criterios['dias_ausencia_max']);
            $query->where('ultimo_checkin', '>=', $diasAusencia);
        }

        if (!empty($criterios['dias_ausencia_min'])) {
            $diasMin = now()->subDays((int) $criterios['dias_ausencia_min']);
            $query->where(fn ($q) => $q->where('ultimo_checkin', '<', $diasMin)->orWhereNull('ultimo_checkin'));
        }

        $userIds = $query->pluck('id');

        // Substitui os usuários do segmento
        DB::table('segmento_usuarios')->where('segmento_id', $segmento->id)->delete();

        $now  = now();
        $rows = $userIds->map(fn ($uid) => [
            'segmento_id'  => $segmento->id,
            'user_id'      => $uid,
            'adicionado_em' => $now,
        ])->toArray();

        DB::table('segmento_usuarios')->insert($rows);

        return count($rows);
    }
}
