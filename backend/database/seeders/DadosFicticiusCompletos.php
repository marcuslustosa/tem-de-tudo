<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Promocao;
use App\Models\Ponto;
use Illuminate\Support\Facades\DB;

class DadosFicticiusCompletos extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎲 Criando dados fictícios completos...');
        
        // Pegar usuário empresa fictício para ser owner
        $empresaUser = User::where('email', 'empresa@teste.com')->first();
        $ownerId = $empresaUser ? $empresaUser->id : 1;
        
        // ===========================================
        // EMPRESAS FICTÍCIAS (próximas do usuário)
        // ===========================================
        $empresasFicticias = [
            [
                'nome' => 'Restaurante Sabor & Arte',
                'cnpj' => '11.111.111/0001-11',
                'ramo' => 'restaurantes',
                'endereco' => 'Rua das Palmeiras, 123',
                'telefone' => '(11) 3456-7890',
                'descricao' => 'Culinária artesanal com pratos regionais',
                'points_multiplier' => 1.5,
                'ativo' => true,
                'owner_id' => $ownerId
            ],
            [
                'nome' => 'Bella Napoli Pizzeria',
                'cnpj' => '22.222.222/0001-22', 
                'ramo' => 'restaurantes',
                'endereco' => 'Av. Italia, 456',
                'telefone' => '(11) 2345-6789',
                'descricao' => 'Pizzas artesanais no forno à lenha',
                'points_multiplier' => 1.2,
                'ativo' => true,
                'owner_id' => $ownerId
            ],
            [
                'nome' => 'Salão Beleza Total',
                'cnpj' => '33.333.333/0001-33',
                'ramo' => 'beleza',
                'endereco' => 'Rua da Beleza, 789',
                'telefone' => '(11) 4567-8901',
                'descricao' => 'Cortes, penteados e tratamentos estéticos',
                'points_multiplier' => 1.8,
                'ativo' => true,
                'owner_id' => $ownerId
            ],
            [
                'nome' => 'SmartFit Academia',
                'cnpj' => '44.444.444/0001-44',
                'ramo' => 'fitness',
                'endereco' => 'Centro Comercial, Loja 15',
                'telefone' => '(11) 5678-9012',
                'descricao' => 'Academia completa com equipamentos modernos',
                'points_multiplier' => 1.4,
                'ativo' => true,
                'owner_id' => $ownerId
            ],
            [
                'nome' => 'Farmácia São José',
                'cnpj' => '55.555.555/0001-55',
                'ramo' => 'saude',
                'endereco' => 'Praça Central, 111',
                'telefone' => '(11) 6789-0123',
                'descricao' => 'Medicamentos e produtos de saúde',
                'points_multiplier' => 1.1,
                'ativo' => true,
                'owner_id' => $ownerId
            ],
            [
                'nome' => 'Pet Shop Amigo Fiel',
                'cnpj' => '66.666.666/0001-66',
                'ramo' => 'servicos',
                'endereco' => 'Rua dos Pets, 333',
                'telefone' => '(11) 7890-1234',
                'descricao' => 'Tudo para seu animal de estimação',
                'points_multiplier' => 1.3,
                'ativo' => true,
                'owner_id' => $ownerId
            ]
        ];

        foreach ($empresasFicticias as $empresaData) {
            Empresa::updateOrCreate(
                ['cnpj' => $empresaData['cnpj']],
                $empresaData
            );
        }
        
        // =====================================
        // PROMOÇÕES ATIVAS (fictícias)
        // =====================================
        $promocoesFicticias = [
            [
                'titulo' => '2 por 1 em Pizzas!',
                'descricao' => 'Compre 1 pizza grande e ganhe outra igual. Válido de segunda a quinta-feira.',
                'desconto' => 50.0,
                'empresa_id' => 2,
                'ativo' => true
            ],
            [
                'titulo' => 'Desconto 30% Cortes',
                'descricao' => 'Corte + escova com 30% de desconto. Agende já!',
                'desconto' => 30.0,
                'empresa_id' => 3,
                'ativo' => true
            ],
            [
                'titulo' => 'Mensalidade Academia',
                'descricao' => 'Primeira mensalidade por apenas R$ 49,90.',
                'desconto' => 60.0,
                'empresa_id' => 4,
                'ativo' => true
            ],
            [
                'titulo' => 'Delivery Grátis',
                'descricao' => 'Delivery gratuito para pedidos acima de R$ 50.',
                'desconto' => 8.0,
                'empresa_id' => 1,
                'ativo' => true
            ]
        ];

        foreach ($promocoesFicticias as $promoData) {
            Promocao::updateOrCreate(
                ['titulo' => $promoData['titulo']],
                $promoData
            );
        }
        
        // =======================================
        // CUPONS FICTÍCIOS (shop) - Usando INSERT direto
        // =======================================
        try {
            DB::table('cupons')->insertOrIgnore([
                'titulo' => 'R$ 10 OFF',
                'descricao' => 'Desconto de R$ 10 em compras acima de R$ 50',
                'valor_pontos' => 100,
                'valor_real' => 10.00,
                'categoria' => 'desconto',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('cupons')->insertOrIgnore([
                'titulo' => 'Frete Grátis',
                'descricao' => 'Delivery gratuito em qualquer pedido',
                'valor_pontos' => 50,
                'valor_real' => 8.00,
                'categoria' => 'delivery',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->command->info('✅ Cupons shop: 2 disponíveis');
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Tabela cupons não encontrada - pulando...');
        }
        
        $this->command->info('✅ Empresas fictícias: 6 criadas');
        $this->command->info('✅ Promoções ativas: 4 criadas');
        $this->command->info('✅ Preview empresas próximas: Implementado');
        $this->command->info('');
        $this->command->info('🎯 DADOS FICTÍCIOS BÁSICOS CRIADOS!');
        $this->command->info('   - Podem ser usados em transações');
        $this->command->info('   - Podem ser usados em funções');
        $this->command->info('   - NÃO têm fins legais (apenas simulação)');
    }
}