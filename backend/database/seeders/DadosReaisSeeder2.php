<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;

class DadosReaisSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n🌱 POPULANDO DADOS REALISTAS...\n\n";

        $owner = User::where('email', 'empresa@teste.com')->first();
        if (!$owner) {
            echo "❌ Usuário não encontrado!\n";
            return;
        }

        echo "🏪 Criando 10 empresas...\n";
        
        $empresas = [
            ['nome' => 'Sabor e Arte', 'cnpj' => '12.345.678/0001-90', 'telefone' => '(11) 98765-4321', 'logo' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400', 'descricao' => 'Restaurante brasileiro'],
            ['nome' => 'Bella Napoli', 'cnpj' => '23.456.789/0001-12', 'telefone' => '(11) 97654-3210', 'logo' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400', 'descricao' => 'Pizzaria artesanal'],
            ['nome' => 'FitLife Academia', 'cnpj' => '34.567.890/0001-34', 'telefone' => '(11) 96543-2109', 'logo' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400', 'descricao' => 'Academia completa'],
            ['nome' => 'Beleza Total', 'cnpj' => '45.678.901/0001-56', 'telefone' => '(11) 95432-1098', 'logo' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400', 'descricao' => 'Salão de beleza'],
            ['nome' => 'Café & Cia', 'cnpj' => '56.789.012/0001-78', 'telefone' => '(11) 94321-0987', 'logo' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400', 'descricao' => 'Cafeteria'],
            ['nome' => 'Pet Shop Amigo Fiel', 'cnpj' => '67.890.123/0001-90', 'telefone' => '(11) 93210-9876', 'logo' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400', 'descricao' => 'Pet Shop'],
            ['nome' => 'Farmácia Saúde Plus', 'cnpj' => '78.901.234/0001-01', 'telefone' => '(11) 92109-8765', 'logo' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400', 'descricao' => 'Farmácia 24h'],
            ['nome' => 'Mercado Bom Preço', 'cnpj' => '89.012.345/0001-23', 'telefone' => '(11) 91098-7654', 'logo' => 'https://images.unsplash.com/photo-1583736902931-063382c8e67f?w=400', 'descricao' => 'Supermercado'],
            ['nome' => 'Burger Gourmet', 'cnpj' => '90.123.456/0001-45', 'telefone' => '(11) 90987-6543', 'logo' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400', 'descricao' => 'Hamburgueria'],
            ['nome' => 'Zen Spa', 'cnpj' => '01.234.567/0001-67', 'telefone' => '(11) 99876-5432', 'logo' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=400', 'descricao' => 'Spa'],
        ];

        foreach ($empresas as $emp) {
            Empresa::create([
                'nome' => $emp['nome'],
                'cnpj' => $emp['cnpj'],
                'telefone' => $emp['telefone'],
                'endereco' => 'São Paulo, SP',
                'logo' => $emp['logo'],
                'descricao' => $emp['descricao'],
                'ativo' => true,
                'points_multiplier' => rand(10, 20) / 10,
                'owner_id' => $owner->id
            ]);
            echo "   ✅ {$emp['nome']}\n";
        }

        echo "\n✨ Concluído!\n\n";
    }
}
