<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
        DB::table('cupons')->delete();
        DB::table('historicos')->delete();

        // Buscar usuários já existentes (seeders anteriores já criaram usuários)
        $clientes = User::where('perfil', 'cliente')->get();
        $empresas = User::where('perfil', 'empresa')->get();

        // Criar pontos fictícios para clientes
        foreach ($clientes as $cliente) {
            Ponto::updateOrCreate(
                ['user_id' => $cliente->id],
                [
                    'pontos' => rand(100, 1500),
                    'nivel' => $this->calcularNivel(rand(100, 1500)),
                ]
            );

            // Criar cupons fictícios vinculados ao cliente
            foreach (range(1, 3) as $i) {
                Cupom::create([
                    'user_id' => $cliente->id,
                    'codigo' => 'CUPOM' . $cliente->id . $i,
                    'descricao' => 'Desconto de ' . (10 * $i) . '% em compras',
                    'status' => 'ativo',
                    'validade' => now()->addDays(30 + $i * 5),
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
