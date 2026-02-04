<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Promocao;
use Carbon\Carbon;

class PromocoesSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n🎁 CRIANDO PROMOÇÕES...\n\n";

        $empresas = Empresa::all();
        
        if ($empresas->count() == 0) {
            echo "❌ Nenhuma empresa encontrada! Execute o seed de empresas primeiro.\n";
            return;
        }

        $promocoes = [
            ['titulo' => '10% de Desconto', 'pontos' => 50, 'desconto' => 10, 'tipo' => 'percentual'],
            ['titulo' => '15% de Desconto', 'pontos' => 100, 'desconto' => 15, 'tipo' => 'percentual'],
            ['titulo' => 'R$ 10 OFF', 'pontos' => 80, 'desconto' => 10, 'tipo' => 'valor'],
            ['titulo' => 'R$ 20 OFF', 'pontos' => 150, 'desconto' => 20, 'tipo' => 'valor'],
            ['titulo' => '20% de Desconto', 'pontos' => 200, 'desconto' => 20, 'tipo' => 'percentual'],
            ['titulo' => 'Brinde Grátis', 'pontos' => 120, 'desconto' => 0, 'tipo' => 'brinde'],
            ['titulo' => '2 por 1', 'pontos' => 180, 'desconto' => 50, 'tipo' => 'percentual'],
            ['titulo' => 'R$ 50 OFF', 'pontos' => 300, 'desconto' => 50, 'tipo' => 'valor'],
        ];

        $contador = 0;

        foreach ($empresas as $empresa) {
            // Cada empresa recebe 2-3 promoções aleatórias
            $qtd = rand(2, 3);
            $promocoesSelecionadas = collect($promocoes)->random($qtd);

            foreach ($promocoesSelecionadas as $promo) {
                Promocao::create([
                    'empresa_id' => $empresa->id,
                    'titulo' => $promo['titulo'],
                    'descricao' => $this->gerarDescricao($promo, $empresa->nome),
                    'pontos_necessarios' => $promo['pontos'],
                    'desconto_percentual' => $promo['tipo'] === 'percentual' ? $promo['desconto'] : null,
                    'desconto_valor' => $promo['tipo'] === 'valor' ? $promo['desconto'] : null,
                    'validade' => now()->addMonths(3),
                    'ativo' => true,
                ]);

                $contador++;
                echo "   ✅ {$empresa->nome}: {$promo['titulo']}\n";
            }
        }

        echo "\n✨ Total: $contador promoções criadas!\n\n";
    }

    private function gerarDescricao($promo, $nomeEmpresa)
    {
        $descricoes = [
            'percentual' => "Ganhe {$promo['desconto']}% de desconto em qualquer compra na {$nomeEmpresa}!",
            'valor' => "Desconto de R$ {$promo['desconto']} em compras acima de R$ 50 na {$nomeEmpresa}!",
            'brinde' => "Ganhe um brinde exclusivo na {$nomeEmpresa}! Resgate agora seus pontos.",
        ];

        return $descricoes[$promo['tipo']] ?? "Promoção exclusiva na {$nomeEmpresa}!";
    }
}
