<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;
use App\Models\Empresa;

class ProdutosRecompensasSeeder extends Seeder
{
    /**
     * Popula produtos/recompensas para todas as empresas
     * Objetivo: Permitir que clientes possam resgatar recompensas na demo
     */
    public function run(): void
    {
        $this->command->info('🎁 Criando produtos e recompensas...');

        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma empresa encontrada. Execute DatabaseSeeder primeiro.');
            return;
        }

        // Categorias de recompensas com pontos necessários
        $recompensas = [
            // Descontos Percentuais
            [
                'nome' => '10% de Desconto',
                'descricao' => 'Ganhe 10% de desconto em qualquer compra',
                'categoria' => 'desconto',
                'pontos_necessarios' => 50,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => '15% de Desconto',
                'descricao' => 'Ganhe 15% de desconto em qualquer compra',
                'categoria' => 'desconto',
                'pontos_necessarios' => 80,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => '20% de Desconto',
                'descricao' => 'Ganhe 20% de desconto em qualquer compra',
                'categoria' => 'desconto',
                'pontos_necessarios' => 100,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => '50% de Desconto',
                'descricao' => 'Aproveite metade do preço em qualquer produto',
                'categoria' => 'desconto',
                'pontos_necessarios' => 250,
                'preco' => 0,
                'estoque' => 50,
            ],

            // Descontos em Valor Fixo
            [
                'nome' => 'R$ 10 OFF',
                'descricao' => 'Desconto de R$ 10 em compras acima de R$ 50',
                'categoria' => 'voucher',
                'pontos_necessarios' => 80,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => 'R$ 20 OFF',
                'descricao' => 'Desconto de R$ 20 em compras acima de R$ 100',
                'categoria' => 'voucher',
                'pontos_necessarios' => 150,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => 'R$ 50 OFF',
                'descricao' => 'Desconto de R$ 50 em compras acima de R$ 200',
                'categoria' => 'voucher',
                'pontos_necessarios' => 400,
                'preco' => 0,
                'estoque' => 50,
            ],

            // Produtos/Serviços Grátis
            [
                'nome' => 'Produto Grátis',
                'descricao' => 'Resgate um produto de até R$ 30 gratuitamente',
                'categoria' => 'produto_gratis',
                'pontos_necessarios' => 150,
                'preco' => 0,
                'estoque' => 80,
            ],
            [
                'nome' => 'Brinde Especial',
                'descricao' => 'Ganhe um brinde exclusivo da loja',
                'categoria' => 'produto_gratis',
                'pontos_necessarios' => 120,
                'preco' => 0,
                'estoque' => 60,
            ],
            [
                'nome' => '2 por 1',
                'descricao' => 'Leve 2 produtos e pague apenas 1',
                'categoria' => 'produto_gratis',
                'pontos_necessarios' => 200,
                'preco' => 0,
                'estoque' => 70,
            ],

            // Cashback
            [
                'nome' => 'Cashback 5%',
                'descricao' => 'Receba 5% de volta em pontos na sua próxima compra',
                'categoria' => 'cashback',
                'pontos_necessarios' => 300,
                'preco' => 0,
                'estoque' => 100,
            ],
            [
                'nome' => 'Cashback 10%',
                'descricao' => 'Receba 10% de volta em pontos na sua próxima compra',
                'categoria' => 'cashback',
                'pontos_necessarios' => 500,
                'preco' => 0,
                'estoque' => 80,
            ],

            // Frete Grátis
            [
                'nome' => 'Frete Grátis',
                'descricao' => 'Ganhe frete grátis em qualquer compra',
                'categoria' => 'servico',
                'pontos_necessarios' => 50,
                'preco' => 0,
                'estoque' => 150,
            ],

            // Experiências Especiais
            [
                'nome' => 'Atendimento VIP',
                'descricao' => 'Tenha prioridade no atendimento',
                'categoria' => 'servico',
                'pontos_necessarios' => 180,
                'preco' => 0,
                'estoque' => 40,
            ],
        ];

        $totalProdutos = 0;

        foreach ($empresas as $empresa) {
            // Criar entre 4-6 produtos aleatórios para cada empresa
            $produtosSelecionados = collect($recompensas)->random(rand(4, 6));

            foreach ($produtosSelecionados as $recompensa) {
                // Personalizar descrição com nome da empresa
                $descricaoPersonalizada = $recompensa['descricao'] . " em {$empresa->nome}";

                // Gerar imagem temática baseada na categoria da empresa
                $seed = md5($empresa->id . $recompensa['nome']);
                $imagem = "https://picsum.photos/seed/{$seed}/400/300";

                Produto::create([
                    'empresa_id' => $empresa->id,
                    'nome' => $recompensa['nome'],
                    'descricao' => $descricaoPersonalizada,
                    'preco' => $recompensa['preco'],
                    'categoria' => $recompensa['categoria'],
                    'imagem' => $imagem,
                    'ativo' => true,
                    'estoque' => $recompensa['estoque'],
                    'pontos_gerados' => $recompensa['pontos_necessarios'], // Pontos necessários para resgate
                ]);

                $totalProdutos++;
            }

            $this->command->info("  ✓ {$empresa->nome}: {$produtosSelecionados->count()} recompensas");
        }

        $this->command->info("✅ Total de {$totalProdutos} produtos/recompensas criados para {$empresas->count()} empresas");
    }
}
