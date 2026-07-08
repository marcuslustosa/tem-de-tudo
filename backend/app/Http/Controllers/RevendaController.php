<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Painel master: gestao de REVENDAS (perfil "revenda" / submaster).
 * O master cria revendas com saldo em R$ (creditos) e vencimento, e pode
 * renovar/creditar. Endpoints protegidos por admin.permission:manage_users
 * (somente master). Nao altera o fluxo de autenticacao existente.
 */
class RevendaController extends Controller
{
    private function pgBool(bool $value): bool|string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? ($value ? 'true' : 'false') : $value;
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => (string) ($user->name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'telefone' => (string) ($user->telefone ?? ''),
            'whatsapp' => Schema::hasColumn('users', 'whatsapp') ? (string) ($user->whatsapp ?? '') : '',
            'status' => (string) ($user->status ?? 'ativo'),
            'creditos' => Schema::hasColumn('users', 'creditos') ? (float) ($user->creditos ?? 0) : 0.0,
            'inicio' => optional($user->created_at)->toDateString(),
            'vencimento' => Schema::hasColumn('users', 'expires_at') ? optional($user->expires_at)->toDateString() : null,
            'dias_restantes' => $this->diasRestantes($user),
        ];
    }

    private function diasRestantes(User $user): ?int
    {
        if (!Schema::hasColumn('users', 'expires_at') || !$user->expires_at) {
            return null;
        }

        return (int) round(now()->startOfDay()->diffInDays($user->expires_at->copy()->startOfDay(), false));
    }

    public function index()
    {
        try {
            $revendas = User::query()
                ->where('perfil', 'revenda')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (User $u) => $this->serialize($u))
                ->all();

            return response()->json(['success' => true, 'data' => $revendas]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar revendas', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Nao foi possivel carregar as revendas.'], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'email' => 'required|email|max:190',
            'senha' => 'required|string|min:6|max:100',
            'telefone' => 'nullable|string|max:40',
            'whatsapp' => 'nullable|string|max:40',
            'creditos' => 'nullable|numeric|min:0',
            'dias' => 'nullable|integer|in:30,90,180,365',
        ]);

        $email = strtolower(trim($validated['email']));
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json(['success' => false, 'message' => 'Este email ja esta cadastrado.'], 422);
        }

        try {
            $data = [
                'name' => trim($validated['nome']),
                'email' => $email,
                'password' => Hash::make($validated['senha']),
                'perfil' => 'revenda',
            ];
            if (Schema::hasColumn('users', 'role')) $data['role'] = 'revenda';
            if (Schema::hasColumn('users', 'telefone')) $data['telefone'] = $validated['telefone'] ?? null;
            if (Schema::hasColumn('users', 'whatsapp')) $data['whatsapp'] = $validated['whatsapp'] ?? null;
            if (Schema::hasColumn('users', 'creditos')) $data['creditos'] = (float) ($validated['creditos'] ?? 0);
            if (Schema::hasColumn('users', 'status')) $data['status'] = 'ativo';
            if (Schema::hasColumn('users', 'is_active')) $data['is_active'] = $this->pgBool(true);
            if (Schema::hasColumn('users', 'expires_at')) {
                $data['expires_at'] = now()->addDays((int) ($validated['dias'] ?? 30));
            }

            $user = User::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Revenda criada com sucesso.',
                'data' => $this->serialize($user),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar revenda', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Nao foi possivel criar a revenda agora.'], 500);
        }
    }

    /**
     * Renova o vencimento em N dias e (opcional) soma creditos ao saldo.
     */
    public function renovar(Request $request, int $id)
    {
        $validated = $request->validate([
            'dias' => 'nullable|integer|in:30,90,180,365',
            'creditos' => 'nullable|numeric',
        ]);

        try {
            $user = User::query()->where('perfil', 'revenda')->findOrFail($id);
            $update = [];

            if (isset($validated['dias']) && Schema::hasColumn('users', 'expires_at')) {
                $base = $user->expires_at && $user->expires_at->isFuture() ? $user->expires_at->copy() : now();
                $update['expires_at'] = $base->addDays((int) $validated['dias']);
            }
            if (array_key_exists('creditos', $validated) && $validated['creditos'] !== null && Schema::hasColumn('users', 'creditos')) {
                $update['creditos'] = round(((float) $user->creditos) + (float) $validated['creditos'], 2);
            }

            if ($update) {
                DB::table('users')->where('id', $user->id)->update(array_merge($update, ['updated_at' => now()]));
                $user->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Revenda atualizada com sucesso.',
                'data' => $this->serialize($user),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao renovar revenda', ['revenda_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Nao foi possivel atualizar a revenda agora.'], 500);
        }
    }
}
