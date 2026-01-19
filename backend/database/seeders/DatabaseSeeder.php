<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🌱 SEEDER - Populando banco de dados\n";
        echo "========================================\n\n";

        // Admin Master
        $admin = User::updateOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Administrador Master',
                'password' => Hash::make('admin123'),
                'perfil' => 'admin',
                'telefone' => '(11) 99999-0001',
                'status' => 'ativo',
                'pontos' => 0,
                'email_verified_at' => now()
            ]
        );
        echo "✅ Admin criado: admin@temdetudo.com / admin123\n";

        // Cliente de Teste
        $cliente = User::updateOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'name' => 'Cliente Teste',
                'password' => Hash::make('123456'),
                'perfil' => 'cliente',
                'telefone' => '(11) 99999-0002',
                'status' => 'ativo',
                'pontos' => 250,
                'email_verified_at' => now()
            ]
        );
        echo "✅ Cliente criado: cliente@teste.com / 123456\n";

        // Empresa Parceira
        $empresa = User::updateOrCreate(
            ['email' => 'empresa@teste.com'],
            [
                'name' => 'Empresa Teste Ltda',
                'password' => Hash::make('123456'),
                'perfil' => 'empresa',
                'telefone' => '(11) 99999-0003',
                'status' => 'ativo',
                'pontos' => 0,
                'email_verified_at' => now()
            ]
        );
        echo "✅ Empresa criada: empresa@teste.com / 123456\n";

        // Criar 50 clientes (cliente1@email.com até cliente50@email.com)
        echo "\n📝 Criando 50 clientes...\n";
        for ($i = 1; $i <= 50; $i++) {
            User::updateOrCreate(
                ['email' => "cliente{$i}@email.com"],
                [
                    'name' => "Cliente {$i}",
                    'password' => Hash::make('senha123'),
                    'perfil' => 'cliente',
                    'telefone' => sprintf('(11) 9%04d-%04d', rand(1000, 9999), rand(1000, 9999)),
                    'status' => 'ativo',
                    'pontos' => rand(0, 1000),
                    'email_verified_at' => now()
                ]
            );
            if ($i % 10 == 0) {
                echo "  ✓ {$i} clientes criados...\n";
            }
        }
        echo "✅ 50 clientes criados (cliente1@email.com até cliente50@email.com / senha123)\n";

        // Criar empresas fictícias na tabela empresas
        echo "\n🏪 Criando empresas parceiras...\n";
        
        $empresasData = [
            ['nome' => 'Restaurante Sabor & Arte', 'ramo' => 'restaurante', 'owner_id' => $empresa->id],
            ['nome' => 'Academia Corpo Forte', 'ramo' => 'academia', 'owner_id' => $empresa->id],
            ['nome' => 'Cafeteria Aroma Premium', 'ramo' => 'cafeteria', 'owner_id' => $empresa->id],
            ['nome' => 'Pet Shop Amigo Fiel', 'ramo' => 'pet_shop', 'owner_id' => $empresa->id],
            ['nome' => 'Salão Beleza Total', 'ramo' => 'salao', 'owner_id' => $empresa->id],
            ['nome' => 'Mercado Bom Preço', 'ramo' => 'mercado', 'owner_id' => $empresa->id],
            ['nome' => 'Farmácia Saúde Mais', 'ramo' => 'farmacia', 'owner_id' => $empresa->id],
            ['nome' => 'Padaria Pão Quentinho', 'ramo' => 'padaria', 'owner_id' => $empresa->id],
        ];
        
        foreach ($empresasData as $empData) {
            \App\Models\Empresa::updateOrCreate(
                ['nome' => $empData['nome']],
                [
                    'owner_id' => $empData['owner_id'],
                    'ramo' => $empData['ramo'],
                    'endereco' => 'Rua Exemplo, ' . rand(100, 9999) . ' - São Paulo, SP',
                    'telefone' => sprintf('(11) 9%04d-%04d', rand(1000, 9999), rand(1000, 9999)),
                    'cnpj' => sprintf('%02d.%03d.%03d/%04d-%02d', rand(10, 99), rand(100, 999), rand(100, 999), rand(1000, 9999), rand(10, 99)),
                    'descricao' => 'Empresa parceira do programa de fidelidade Tem de Tudo',
                    'ativo' => true,
                    'points_multiplier' => 1.0,
                ]
            );
        }
        echo "✅ 8 empresas parceiras criadas\n";

        // Chamar DataSeeder para criar empresas e dados adicionais
        echo "\n📊 Populando dados adicionais...\n";
        $this->call([
            DataSeeder::class,
        ]);

        echo "\n========================================\n";
        echo "✅ SEEDER CONCLUÍDO COM SUCESSO!\n";
        echo "========================================\n";
        echo "\n📋 CREDENCIAIS DE ACESSO:\n";
        echo "┌─────────────────────────────────────────────┐\n";
        echo "│ Admin:   admin@temdetudo.com / admin123     │\n";
        echo "│ Cliente: cliente@teste.com / 123456         │\n";
        echo "│ Empresa: empresa@teste.com / 123456         │\n";
        echo "│ Clientes: cliente1-50@email.com / senha123  │\n";
        echo "└─────────────────────────────────────────────┘\n";
        echo "\n";
        
        $totalUsers = User::count();
        $totalEmpresas = \App\Models\Empresa::count();
        echo "📊 Total de usuários: {$totalUsers}\n";
        echo "📊 Total de empresas: {$totalEmpresas}\n";
        echo "========================================\n\n";
    }
}
