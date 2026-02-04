<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Ponto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseCompleteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desabilitar foreign keys (SQLite)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Limpar tabelas (usar nomes corretos do banco atual)
        DB::table('coupons')->delete();
        DB::table('promocoes')->delete();
        DB::table('check_ins')->delete();
        DB::table('pontos')->delete();
        DB::table('empresas')->delete();
        DB::table('users')->delete();
        
        // Reabilitar foreign keys
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        
        $this->command->info('🔄 Populando banco de dados...');
        
        // ============================================
        // USUÁRIOS (6 usuários de teste)
        // ============================================
        $senha = Hash::make('senha123');
        
        $maria = User::create([
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'password' => $senha,
            'tipo' => 'cliente',
            'cpf' => '123.456.789-01',
            'telefone' => '(11) 98765-4321',
            'pontos' => 195.00
        ]);
        
        $joao = User::create([
            'nome' => 'João Santos',
            'email' => 'joao@email.com',
            'password' => $senha,
            'tipo' => 'cliente',
            'cpf' => '987.654.321-09',
            'telefone' => '(11) 91234-5678',
            'pontos' => 120.00
        ]);
        
        $saborArte = User::create([
            'nome' => 'Restaurante Sabor & Arte',
            'email' => 'saborearte@email.com',
            'password' => $senha,
            'tipo' => 'empresa',
            'cnpj' => '12.345.678/0001-90',
            'telefone' => '(11) 3456-7890',
            'pontos' => 0
        ]);
        
        $bellaNapoli = User::create([
            'nome' => 'Pizzaria Bella Napoli',
            'email' => 'bellanapoli@email.com',
            'password' => $senha,
            'tipo' => 'empresa',
            'cnpj' => '98.765.432/0001-10',
            'telefone' => '(11) 3789-0123',
            'pontos' => 0
        ]);
        
        $admin = User::create([
            'nome' => 'Admin Sistema',
            'email' => 'admin@temdetudo.com',
            'password' => $senha,
            'tipo' => 'admin',
            'telefone' => '(11) 99999-9999',
            'pontos' => 0
        ]);
        
        $gerente = User::create([
            'nome' => 'Gerente Operacional',
            'email' => 'gerente@temdetudo.com',
            'password' => $senha,
            'tipo' => 'admin',
            'cpf' => '111.222.333-44',
            'telefone' => '(11) 98888-8888',
            'pontos' => 0
        ]);
        
        $this->command->info('✅ 6 usuários criados');
        
        // ============================================
        // EMPRESAS (6 estabelecimentos)
        // ============================================
        $empresa1 = Empresa::create([
            'user_id' => $saborArte->id,
            'nome' => 'Restaurante Sabor & Arte',
            'descricao' => 'Culinária brasileira com toque contemporâneo. Ambiente aconchegante e pratos deliciosos.',
            'categoria' => 'alimentacao',
            'endereco' => 'Rua das Flores, 123 - Centro, São Paulo - SP',
            'latitude' => -23.550520,
            'longitude' => -46.633308,
            'telefone' => '(11) 3456-7890',
            'horario_funcionamento' => 'Seg-Sex: 11h-23h | Sáb-Dom: 12h-00h',
            'ativo' => true
        ]);
        
        $empresa2 = Empresa::create([
            'user_id' => $bellaNapoli->id,
            'nome' => 'Pizzaria Bella Napoli',
            'descricao' => 'As melhores pizzas artesanais da região. Massa fina e ingredientes selecionados.',
            'categoria' => 'alimentacao',
            'endereco' => 'Av. Paulista, 456 - Bela Vista, São Paulo - SP',
            'latitude' => -23.561414,
            'longitude' => -46.656178,
            'telefone' => '(11) 3789-0123',
            'horario_funcionamento' => 'Ter-Dom: 18h-23h',
            'ativo' => true
        ]);
        
        $empresa3 = Empresa::create([
            'user_id' => $maria->id,
            'nome' => 'Salão Beleza Pura',
            'descricao' => 'Serviços de cabelo, maquiagem e estética com profissionais qualificados.',
            'categoria' => 'beleza',
            'endereco' => 'Rua Augusta, 789 - Consolação, São Paulo - SP',
            'latitude' => -23.554820,
            'longitude' => -46.662520,
            'telefone' => '(11) 3333-4444',
            'horario_funcionamento' => 'Seg-Sáb: 9h-19h',
            'ativo' => true
        ]);
        
        $empresa4 = Empresa::create([
            'user_id' => $joao->id,
            'nome' => 'Academia FitPower',
            'descricao' => 'Academia completa com musculação, aeróbica e aulas coletivas.',
            'categoria' => 'bemestar',
            'endereco' => 'Rua dos Esportes, 321 - Mooca, São Paulo - SP',
            'latitude' => -23.549300,
            'longitude' => -46.599200,
            'telefone' => '(11) 2222-3333',
            'horario_funcionamento' => 'Seg-Sex: 6h-22h | Sáb: 8h-14h',
            'ativo' => true
        ]);
        
        $empresa5 = Empresa::create([
            'user_id' => $saborArte->id,
            'nome' => 'Auto Center Speed',
            'descricao' => 'Manutenção automotiva, troca de óleo, alinhamento e balanceamento.',
            'categoria' => 'automotivo',
            'endereco' => 'Av. do Estado, 654 - Ipiranga, São Paulo - SP',
            'latitude' => -23.587900,
            'longitude' => -46.610100,
            'telefone' => '(11) 4444-5555',
            'horario_funcionamento' => 'Seg-Sex: 8h-18h | Sáb: 8h-12h',
            'ativo' => true
        ]);
        
        $empresa6 = Empresa::create([
            'user_id' => $bellaNapoli->id,
            'nome' => 'Farmácia Saúde Total',
            'descricao' => 'Medicamentos, perfumaria e produtos de higiene com ótimos preços.',
            'categoria' => 'saude',
            'endereco' => 'Rua da Consolação, 987 - República, São Paulo - SP',
            'latitude' => -23.543300,
            'longitude' => -46.645400,
            'telefone' => '(11) 5555-6666',
            'horario_funcionamento' => '24 horas',
            'ativo' => true
        ]);
        
        $this->command->info('✅ 6 empresas criadas');
        
        // ============================================
        // PROMOÇÕES (10 promoções)
        // ============================================
        DB::table('promocoes')->insert([
            [
                'empresa_id' => $empresa1->id,
                'titulo' => '20% OFF no rodízio',
                'descricao' => 'Ganhe 20% de desconto no rodízio completo de carnes nobres.',
                'pontos_necessarios' => 50,
                'desconto_percentual' => 20.00,
                'desconto_valor' => null,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-08-31',
                'quantidade_disponivel' => 100,
                'quantidade_resgatada' => 1,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa1->id,
                'titulo' => 'Sobremesa Grátis',
                'descricao' => 'Sobremesa grátis na compra de qualquer prato executivo.',
                'pontos_necessarios' => 30,
                'desconto_percentual' => null,
                'desconto_valor' => 15.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-06-30',
                'quantidade_disponivel' => 200,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa2->id,
                'titulo' => 'Pizza Grande por R$ 39,90',
                'descricao' => 'Qualquer pizza grande de até 3 sabores por apenas R$ 39,90.',
                'pontos_necessarios' => 80,
                'desconto_percentual' => null,
                'desconto_valor' => 20.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-12-31',
                'quantidade_disponivel' => 150,
                'quantidade_resgatada' => 1,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa2->id,
                'titulo' => '2ª Pizza 50% OFF',
                'descricao' => 'Na compra de uma pizza grande, leve a segunda com 50% de desconto.',
                'pontos_necessarios' => 60,
                'desconto_percentual' => 50.00,
                'desconto_valor' => null,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-07-31',
                'quantidade_disponivel' => 100,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1571997478779-2adcbbe9ab2f',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa3->id,
                'titulo' => 'Corte + Barba R$ 60',
                'descricao' => 'Pacote completo de corte masculino + barba por apenas R$ 60.',
                'pontos_necessarios' => 40,
                'desconto_percentual' => null,
                'desconto_valor' => 25.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-05-31',
                'quantidade_disponivel' => 80,
                'quantidade_resgatada' => 1,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa3->id,
                'titulo' => '15% OFF em Coloração',
                'descricao' => 'Desconto especial em todos os serviços de coloração.',
                'pontos_necessarios' => 70,
                'desconto_percentual' => 15.00,
                'desconto_valor' => null,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-09-30',
                'quantidade_disponivel' => 60,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa4->id,
                'titulo' => '1 Mês Grátis de Musculação',
                'descricao' => 'Ganhe 1 mês grátis de musculação na matrícula anual.',
                'pontos_necessarios' => 100,
                'desconto_percentual' => null,
                'desconto_valor' => 150.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-04-30',
                'quantidade_disponivel' => 50,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa5->id,
                'titulo' => '10% OFF na Revisão Completa',
                'descricao' => 'Desconto na revisão completa do seu veículo.',
                'pontos_necessarios' => 50,
                'desconto_percentual' => 10.00,
                'desconto_valor' => null,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-10-31',
                'quantidade_disponivel' => 120,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa6->id,
                'titulo' => 'R$ 20 OFF acima de R$ 100',
                'descricao' => 'Compre R$ 100 ou mais e ganhe R$ 20 de desconto.',
                'pontos_necessarios' => 35,
                'desconto_percentual' => null,
                'desconto_valor' => 20.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-12-31',
                'quantidade_disponivel' => 300,
                'quantidade_resgatada' => 1,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'empresa_id' => $empresa6->id,
                'titulo' => 'Frete Grátis',
                'descricao' => 'Frete grátis em compras acima de R$ 50.',
                'pontos_necessarios' => 25,
                'desconto_percentual' => null,
                'desconto_valor' => 10.00,
                'validade_inicio' => '2026-02-01',
                'validade_fim' => '2026-11-30',
                'quantidade_disponivel' => 500,
                'quantidade_resgatada' => 0,
                'ativo' => true,
                'imagem_url' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        
        $this->command->info('✅ 10 promoções criadas');
        
        // ============================================
        // CHECK-INS (10 registros) - Usar tabela check_ins
        // ============================================
        DB::table('check_ins')->insert([
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa1->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.550520,
                'longitude' => -46.633308,
                'metodo' => 'qrcode',
                'created_at' => '2026-02-01 12:30:00',
                'updated_at' => '2026-02-01 12:30:00'
            ],
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa2->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.561414,
                'longitude' => -46.656178,
                'metodo' => 'manual',
                'created_at' => '2026-02-02 19:15:00',
                'updated_at' => '2026-02-02 19:15:00'
            ],
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa3->id,
                'pontos_ganhos' => 15.00,
                'latitude' => -23.554820,
                'longitude' => -46.662520,
                'metodo' => 'qrcode',
                'created_at' => '2026-02-03 14:00:00',
                'updated_at' => '2026-02-03 14:00:00'
            ],
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa5->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.587900,
                'longitude' => -46.610100,
                'metodo' => 'manual',
                'created_at' => '2026-02-03 16:45:00',
                'updated_at' => '2026-02-03 16:45:00'
            ],
            [
                'user_id' => $joao->id,
                'empresa_id' => $empresa1->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.550520,
                'longitude' => -46.633308,
                'metodo' => 'qrcode',
                'created_at' => '2026-02-01 13:00:00',
                'updated_at' => '2026-02-01 13:00:00'
            ],
            [
                'user_id' => $joao->id,
                'empresa_id' => $empresa4->id,
                'pontos_ganhos' => 20.00,
                'latitude' => -23.549300,
                'longitude' => -46.599200,
                'metodo' => 'automatico',
                'created_at' => '2026-02-02 07:30:00',
                'updated_at' => '2026-02-02 07:30:00'
            ],
            [
                'user_id' => $joao->id,
                'empresa_id' => $empresa6->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.543300,
                'longitude' => -46.645400,
                'metodo' => 'manual',
                'created_at' => '2026-02-03 10:20:00',
                'updated_at' => '2026-02-03 10:20:00'
            ],
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa4->id,
                'pontos_ganhos' => 15.00,
                'latitude' => -23.549300,
                'longitude' => -46.599200,
                'metodo' => 'qrcode',
                'created_at' => '2026-02-04 18:00:00',
                'updated_at' => '2026-02-04 18:00:00'
            ],
            [
                'user_id' => $joao->id,
                'empresa_id' => $empresa3->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.554820,
                'longitude' => -46.662520,
                'metodo' => 'manual',
                'created_at' => '2026-02-05 11:30:00',
                'updated_at' => '2026-02-05 11:30:00'
            ],
            [
                'user_id' => $maria->id,
                'empresa_id' => $empresa6->id,
                'pontos_ganhos' => 10.00,
                'latitude' => -23.543300,
                'longitude' => -46.645400,
                'metodo' => 'qrcode',
                'created_at' => '2026-02-06 15:45:00',
                'updated_at' => '2026-02-06 15:45:00'
            ]
        ]);
        
        $this->command->info('✅ 10 check-ins criados');
        
        // ============================================
        // CUPONS (4 cupons resgatados) - Usar tabela coupons
        // ============================================
        DB::table('coupons')->insert([
            [
                'user_id' => $maria->id,
                'promocao_id' => 1,
                'codigo' => 'CUPOM-20OFF-001',
                'usado' => false,
                'data_uso' => null,
                'validade' => '2026-08-31',
                'created_at' => '2026-02-01 14:00:00',
                'updated_at' => '2026-02-01 14:00:00'
            ],
            [
                'user_id' => $maria->id,
                'promocao_id' => 3,
                'codigo' => 'CUPOM-PIZZA-002',
                'usado' => false,
                'data_uso' => null,
                'validade' => '2026-12-31',
                'created_at' => '2026-02-02 20:00:00',
                'updated_at' => '2026-02-02 20:00:00'
            ],
            [
                'user_id' => $joao->id,
                'promocao_id' => 5,
                'codigo' => 'CUPOM-CORTE-003',
                'usado' => true,
                'data_uso' => '2026-02-05 10:00:00',
                'validade' => '2026-05-31',
                'created_at' => '2026-02-04 15:30:00',
                'updated_at' => '2026-02-05 10:00:00'
            ],
            [
                'user_id' => $joao->id,
                'promocao_id' => 9,
                'codigo' => 'CUPOM-FARMACIA-004',
                'usado' => false,
                'data_uso' => null,
                'validade' => '2026-12-31',
                'created_at' => '2026-02-06 16:00:00',
                'updated_at' => '2026-02-06 16:00:00'
            ]
        ]);
        
        $this->command->info('✅ 4 cupons criados');
        
        // ============================================
        // RESUMO FINAL
        // ============================================
        $this->command->info('');
        $this->command->info('==================================');
        $this->command->info('✅ BANCO POPULADO COM SUCESSO!');
        $this->command->info('==================================');
        $this->command->info('📊 Totais:');
        $this->command->info('   - 6 usuários (2 clientes, 2 empresas, 2 admins)');
        $this->command->info('   - 6 empresas');
        $this->command->info('   - 10 promoções');
        $this->command->info('   - 10 check-ins');
        $this->command->info('   - 4 cupons');
        $this->command->info('');
        $this->command->info('🔑 Logins de Teste (senha: senha123):');
        $this->command->info('   - maria@email.com (Cliente - 0 pontos)');
        $this->command->info('   - joao@email.com (Cliente - 120 pontos)');
        $this->command->info('   - saborearte@email.com (Empresa)');
        $this->command->info('   - bellanapoli@email.com (Empresa)');
        $this->command->info('   - admin@temdetudo.com (Admin)');
        $this->command->info('   - gerente@temdetudo.com (Admin)');
        $this->command->info('==================================');
    }
}
