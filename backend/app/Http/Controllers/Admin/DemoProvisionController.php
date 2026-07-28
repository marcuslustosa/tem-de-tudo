<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Provisionamento da demo sob demanda, disparado por um admin autenticado.
 *
 * Existe porque o provisionamento do boot (SEED_ON_START / app:ensure-demo-access
 * dentro do start-railway.sh) nao roda de forma confiavel no ambiente atual: as
 * contas demo ficaram sem pontos e nao ha como corrigir sem acesso ao servidor.
 * Este endpoint reaproveita exatamente os mesmos comandos, sem duplicar regra.
 */
class DemoProvisionController extends Controller
{
    public function seed(Request $request): JsonResponse
    {
        if (!$this->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Provisionamento demo desativado (DEMO_PROVISION_ENDPOINT=false).',
            ], 403);
        }

        $request->validate([
            'email' => ['sometimes', 'email', 'max:255'],
            'nome' => ['sometimes', 'string', 'max:120'],
            'acessos' => ['sometimes', 'boolean'],
        ]);

        // O ambiente de producao nao entrega DEMO_CLIENTE_EMAIL para o app, entao
        // a conta alvo pode vir explicita na chamada. Sem isto o demo rico cai
        // sempre no default (joao@demo.local) em vez da conta que voce usa.
        if ($request->filled('email')) {
            config(['demo.cliente_email' => strtolower(trim((string) $request->input('email')))]);
        }
        if ($request->filled('nome')) {
            config(['demo.cliente_nome' => trim((string) $request->input('nome'))]);
        }

        $steps = [
            'seed' => fn () => Artisan::call('db:seed', [
                '--class' => \Database\Seeders\I9PlusDemoSeeder::class,
                '--force' => true,
            ]),
        ];

        // Opcional e desligado por padrao: com o env fora do ar, este comando cai
        // nos defaults e reescreveria senhas das contas de handoff.
        if ($request->boolean('acessos')) {
            $steps['acessos'] = fn () => Artisan::call('app:ensure-demo-access', [
                '--sync-passwords' => true,
            ]);
        }

        $output = [];
        $errors = [];

        foreach ($steps as $step => $run) {
            try {
                $run();
                $output[$step] = trim(Artisan::output());
            } catch (\Throwable $e) {
                $errors[$step] = $e->getMessage();
                Log::warning('Provisionamento demo falhou', ['etapa' => $step, 'erro' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => $errors === [],
            'message' => $errors === []
                ? 'Demo provisionada.'
                : 'Provisionamento concluido com falhas. Veja "erros".',
            'saida' => $output,
            'erros' => $errors,
            'cliente_demo' => $this->demoClientState(),
        ], $errors === [] ? 200 : 500);
    }

    /**
     * Diagnostico: mostra o estado atual da conta demo sem alterar nada.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'habilitado' => $this->isEnabled(),
            'cliente_demo' => $this->demoClientState(),
        ]);
    }

    private function isEnabled(): bool
    {
        $value = env('DEMO_PROVISION_ENDPOINT', true);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function demoClientState(): array
    {
        $email = strtolower(trim((string) config('demo.cliente_email', 'joao@demo.local')));
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return ['email' => $email, 'existe' => false];
        }

        $lancamentos = 0;
        $ganhos = 0;
        $gastos = 0;

        if (Schema::hasTable('pontos')) {
            $base = DB::table('pontos')->where('user_id', $user->id);
            $lancamentos = (clone $base)->count();
            $ganhos = (int) (clone $base)->whereNotIn('tipo', ['resgate', 'redeem'])->sum('pontos');
            $gastos = (int) (clone $base)->whereIn('tipo', ['resgate', 'redeem'])->sum('pontos');
        }

        return [
            'email' => $user->email,
            'existe' => true,
            'nome' => $user->name,
            'pontos_no_perfil' => (int) ($user->pontos ?? 0),
            'nivel' => $user->nivel,
            'extrato_lancamentos' => $lancamentos,
            'extrato_ganhos' => $ganhos,
            'extrato_resgates' => $gastos,
            'saldo_calculado' => max(0, $ganhos - $gastos),
            'atualizado_em' => optional($user->updated_at)->toIso8601String(),
        ];
    }
}
