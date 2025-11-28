<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Ponto;
use App\Models\CupomModel as Cupom;
use App\Models\HistoricoModel as Historico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando população de dados fictícios para usuários e perfis...');

        // Vamos garantir que o seeding seja idempotente
        DB::table('pontos')->delete();
        DB::table('coupons')->delete();
        DB::table('historicos')->delete();

        // Buscar usuários já existentes (seeders anteriores já criaram usuários)
        $clientes = User::where('perfil', 'cliente')->get();
        $empresas = Empresa::all();

        // Create a default empresa if none exists
        if ($empresas->isEmpty()) {
            // Create a default empresa owner user first
            $ownerUser = User::firstOrCreate(
                ['email' => 'empresa_owner@default.com'],
                [
                    'name' => 'Empresa Owner Default',
                    'password' => Hash::make('password'),
                    'perfil' => 'empresa',
                    'telefone' => '(00) 00000-0000',
                    'status' => 'ativo',
                ]
            );

            $empresa = Empresa::create([
                'nome' => 'Empresa Default',
                'endereco' => 'Endereço padrão',
                'telefone' => '(00) 00000-0000',
                'cnpj' => '00.000.000/0000-00',
                'ativo' => true,
                'owner_id' => $ownerUser->id,
            ]);
            $empresas = Empresa::all();
        }

        // Criar pontos fictícios para clientes
        foreach ($clientes as $cliente) {
            // Assign a random empresa_id to the cliente pontos, assuming empresas is not empty
            $empresaId = $empresas->isNotEmpty() ? $empresas->random()->id : null;

            Ponto::updateOrCreate(
                ['user_id' => $cliente->id],
                [
                    'pontos' => rand(100, 1500),
                    'empresa_id' => $empresaId,
                    'descricao' => 'Pontos iniciais para o usuário',
                    'tipo' => 'earn',
                ]
            );

            // Criar cupons fictícios vinculados ao cliente
            foreach (range(1, 3) as $i) {
                Cupom::create([
                    'user_id' => $cliente->id,
                    'codigo' => 'CUPOM' . $cliente->id . $i,
                    'descricao' => 'Desconto de ' . (10 * $i) . '% em compras',
                    'status' => 'ativo',
                    'custo_pontos' => 100 * $i,
                    'expira_em' => now()->addDays(30 + $i * 5),
                ]);
            }

            // Criar histórico fictício
            foreach (range(1, 5) as $i) {
                Historico::create([
                    'user_id' => $cliente->id,
                    'acao' => 'Compra realizada #' . $i,
                    'data' => now()->subDays($i * 3),
                    'detalhes' => 'Compra no valor de R$ ' . number_format(rand(50, 300), 2, ',', '.'),
                ]);
            }
        }

        $this->command->info('Dados fictícios gerados com sucesso para clientes!');

        // Criar dados fictícios para empresas (exemplo: histórico de transações)
        foreach ($empresas as $empresa) {
            foreach (range(1, 3) as $i) {
                Historico::create([
                    'user_id' => $empresa->id,
                    'acao' => 'Campanha lançada #' . $i,
                    'data' => now()->subDays($i * 7),
                    'detalhes' => 'Campanha promocional para os clientes com desconto',
                ]);
            }
        }

        $this->command->info('Dados fictícios gerados com sucesso para empresas!');
    }

    private function calcularNivel(int $pontos): string
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Platina';
        if ($pontos >= 2500) return 'Ouro';
        if ($pontos >= 1000) return 'Prata';
        return 'Bronze';
    }
}
