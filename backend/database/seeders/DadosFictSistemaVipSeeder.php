<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\CheckIn;
use App\Models\Ponto;
use App\Models\Pagamento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DadosFictSistemaVipSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // 1. Criar badges padrão
        $this->criarBadges();
        
        // 2. Atualizar usuários de teste com dados VIP
        $this->atualizarUsuariosTeste();
        
        // 3. Criar dados de teste para check-ins
        $this->criarCheckInsHistorico();
        
        // 4. Criar pagamentos de teste
        $this->criarPagamentosHistorico();
        
        // 5. Processar badges para usuários
        $this->processarBadgesUsuarios();
    }

    private function criarBadges()
    {
        $badges = Badge::getDefaultBadges();
        
        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['nome' => $badge['nome']],
                $badge + ['ativo' => true]
            );
        }
        
        $this->command->info('✅ Badges criados');
    }

    private function atualizarUsuariosTeste()
    {
        // Admin
        $admin = User::where('email', 'admin@temdetudo.com')->first();
        if ($admin) {
            $admin->update([
                'pontos_lifetime' => 25000,
                'valor_gasto_total' => 120000, // R$ 1200,00
                'dias_consecutivos' => 30,
                'ultimo_checkin' => now(),
                'empresas_visitadas' => 15,
                'multiplicador_pontos' => 3.0,
                'nivel' => 4 // Diamante
            ]);
        }

        // Cliente teste
        $cliente = User::where('email', 'cliente@teste.com')->first();
        if ($cliente) {
            $cliente->update([
                'pontos_lifetime' => 3500,
                'valor_gasto_total' => 25000, // R$ 250,00
                'dias_consecutivos' => 5,
                'ultimo_checkin' => now()->subDay(),
                'empresas_visitadas' => 8,
                'multiplicador_pontos' => 2.0,
                'nivel' => 3 // Ouro
            ]);
        }

        // Empresa teste
        $empresa_user = User::where('email', 'empresa@teste.com')->first();
        if ($empresa_user) {
            $empresa_user->update([
                'pontos_lifetime' => 800,
                'valor_gasto_total' => 5000,
                'dias_consecutivos' => 2,
                'ultimo_checkin' => now()->subDays(3),
                'empresas_visitadas' => 3,
                'multiplicador_pontos' => 1.5,
                'nivel' => 2 // Prata
            ]);
        }

        // Criar alguns usuários fictícios adicionais
        $usuarios_ficticios = [
            [
                'name' => 'Maria Silva',
                'email' => 'maria.silva@teste.com',
                'password' => Hash::make('123456'),
                'perfil' => 'cliente',
                'pontos' => 1800,
                'pontos_lifetime' => 1800,
                'valor_gasto_total' => 15000,
                'dias_consecutivos' => 12,
                'ultimo_checkin' => now(),
                'empresas_visitadas' => 6,
                'multiplicador_pontos' => 1.5,
                'nivel' => 2
            ],
            [
                'name' => 'João Santos',
                'email' => 'joao.santos@teste.com',
                'password' => Hash::make('123456'),
                'perfil' => 'cliente',
                'pontos' => 450,
                'pontos_lifetime' => 450,
                'valor_gasto_total' => 3200,
                'dias_consecutivos' => 1,
                'ultimo_checkin' => now()->subDay(),
                'empresas_visitadas' => 2,
                'multiplicador_pontos' => 1.0,
                'nivel' => 1
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana.costa@teste.com',
                'password' => Hash::make('123456'),
                'perfil' => 'cliente',
                'pontos' => 6200,
                'pontos_lifetime' => 8500,
                'valor_gasto_total' => 45000,
                'dias_consecutivos' => 25,
                'ultimo_checkin' => now(),
                'empresas_visitadas' => 12,
                'multiplicador_pontos' => 2.0,
                'nivel' => 3
            ]
        ];

        foreach ($usuarios_ficticios as $usuario) {
            User::updateOrCreate(
                ['email' => $usuario['email']],
                $usuario
            );
        }

        $this->command->info('✅ Usuários de teste atualizados');
    }

    private function criarCheckInsHistorico()
    {
        $usuarios = User::where('perfil', 'cliente')->get();
        $empresas = Empresa::where('ativo', true)->get();

        if ($empresas->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma empresa encontrada para criar check-ins');
            return;
        }

        foreach ($usuarios as $usuario) {
            $num_checkins = rand(5, 20);
            
            for ($i = 0; $i < $num_checkins; $i++) {
                $empresa = $empresas->random();
                $data_checkin = now()->subDays(rand(1, 60));
                
                $pontos = rand(10, 50);
                $valor_compra = rand(0, 5000); // 0 a R$ 50,00

                CheckIn::create([
                    'user_id' => $usuario->id,
                    'empresa_id' => $empresa->id,
                    'pontos_ganhos' => $pontos,
                    'pontos_base' => intval($pontos / $usuario->multiplicador_pontos),
                    'multiplicador' => $usuario->multiplicador_pontos,
                    'valor_compra' => $valor_compra,
                    'detalhes_calculo' => [
                        'base' => intval($pontos / $usuario->multiplicador_pontos),
                        'multiplicador' => $usuario->multiplicador_pontos,
                        'total' => $pontos
                    ],
                    'created_at' => $data_checkin,
                    'updated_at' => $data_checkin
                ]);

                // Histórico de pontos
                Ponto::create([
                    'user_id' => $usuario->id,
                    'pontos' => $pontos,
                    'tipo' => 'checkin',
                    'descricao' => "Check-in em {$empresa->nome}",
                    'empresa_id' => $empresa->id,
                    'data_expiracao' => $data_checkin->copy()->addYear(),
                    'created_at' => $data_checkin,
                    'updated_at' => $data_checkin
                ]);
            }
        }

        $this->command->info('✅ Histórico de check-ins criado');
    }

    private function criarPagamentosHistorico()
    {
        $usuarios = User::where('perfil', 'cliente')->get();
        $produtos = Produto::where('ativo', true)->get();

        if ($produtos->isEmpty()) {
            $this->command->warn('⚠️ Nenhum produto encontrado para criar pagamentos');
            return;
        }

        foreach ($usuarios as $usuario) {
            $num_pagamentos = rand(2, 8);
            
            for ($i = 0; $i < $num_pagamentos; $i++) {
                $produto = $produtos->random();
                $data_pagamento = now()->subDays(rand(1, 90));
                
                $valor = $produto->preco * 100; // em centavos
                $desconto = rand(0, 1500); // até R$ 15,00 de desconto
                $valor_final = $valor - $desconto;
                $pontos = intval($valor_final / 100);

                $status = rand(1, 10) > 2 ? 'approved' : 'pending'; // 80% aprovados

                $pagamento = Pagamento::create([
                    'user_id' => $usuario->id,
                    'empresa_id' => $produto->empresa_id,
                    'produto_id' => $produto->id,
                    'mercadopago_payment_id' => 'TEST_' . uniqid(),
                    'status' => $status,
                    'valor' => $valor,
                    'valor_desconto' => $desconto,
                    'valor_final' => $valor_final,
                    'pontos_gerados' => $pontos,
                    'metodo_pagamento' => 'pix',
                    'detalhes_pagamento' => [
                        'payment_method' => 'pix',
                        'status' => $status,
                        'external_reference' => 'TDT-TEST-' . rand(1000, 9999)
                    ],
                    'created_at' => $data_pagamento,
                    'updated_at' => $data_pagamento
                ]);

                // Se aprovado, criar histórico de pontos
                if ($status === 'approved') {
                    Ponto::create([
                        'user_id' => $usuario->id,
                        'pontos' => $pontos,
                        'tipo' => 'compra',
                        'descricao' => "Compra: {$produto->nome}",
                        'empresa_id' => $produto->empresa_id,
                        'data_expiracao' => $data_pagamento->copy()->addYear(),
                        'created_at' => $data_pagamento,
                        'updated_at' => $data_pagamento
                    ]);
                }
            }
        }

        $this->command->info('✅ Histórico de pagamentos criado');
    }

    private function processarBadgesUsuarios()
    {
        $usuarios = User::where('perfil', 'cliente')->get();
        $badges = Badge::where('ativo', true)->get();

        foreach ($usuarios as $usuario) {
            $badges_conquistados = [];
            
            foreach ($badges as $badge) {
                if ($badge->verificarConquista($usuario)) {
                    // Verificar se já tem o badge
                    if (!$usuario->badges()->where('badge_id', $badge->id)->exists()) {
                        $data_conquista = now()->subDays(rand(1, 30));
                        
                        $usuario->badges()->attach($badge->id, [
                            'conquistado_em' => $data_conquista,
                            'created_at' => $data_conquista,
                            'updated_at' => $data_conquista
                        ]);
                        
                        $badges_conquistados[] = $badge->nome;
                    }
                }
            }
            
            if (!empty($badges_conquistados)) {
                $this->command->info("👤 {$usuario->name}: " . implode(', ', $badges_conquistados));
            }
        }

        $this->command->info('✅ Badges processados para usuários');
    }
}