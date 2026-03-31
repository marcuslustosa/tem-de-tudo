<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
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
                'permissions' => json_encode([
                    'manage_system',
                    'manage_users',
                    'view_reports',
                    'manage_companies',
                    'manage_promotions'
                ]),
                'telefone' => '(11) 99999-0001',
                'status' => 'ativo',
                'pontos' => 0,
                'email_verified_at' => now()
            ]
        );
        echo "✅ Admin criado: admin@temdetudo.com / admin123\n";

        // Cliente base
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

        // Empresa base (usuário tipo empresa)
        $empresaUser = User::updateOrCreate(
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

        // Empresa base na tabela empresas
        $empresaRecord = DB::table('empresas')->where('owner_id', $empresaUser->id)->first();
        if (!$empresaRecord) {
            $empresaId = DB::table('empresas')->insertGetId([
                'owner_id' => $empresaUser->id,
                'nome' => 'Empresa Teste Ltda',
                'ramo' => 'restaurante',
                'descricao' => 'Empresa seed para demonstração',
                'logo' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=400&fit=crop',
                'ativo' => true,
                'points_multiplier' => 1.0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $empresaRecord = DB::table('empresas')->where('id', $empresaId)->first();
        }

        // Promo seed da empresa base
        $promoId = DB::table('promocoes')
            ->where('empresa_id', $empresaRecord->id)
            ->where('titulo', '10% OFF na primeira compra')
            ->value('id');
        if (!$promoId) {
            $promoId = DB::table('promocoes')->insertGetId([
                'empresa_id' => $empresaRecord->id,
                'titulo' => '10% OFF na primeira compra',
                'descricao' => 'Desconto de boas-vindas para novos clientes',
                'desconto' => 10,
                'imagem' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600',
                'data_inicio' => now()->subDays(2),
                'validade' => now()->addMonths(1),
                'ativo' => true,
                'status' => 'ativa',
                'visualizacoes' => 10,
                'resgates' => 2,
                'usos' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Cupom seed para cliente teste
        DB::table('cupons')->updateOrInsert(
            [
                'user_id' => $cliente->id,
                'promocao_id' => $promoId,
                'codigo' => 'CUPOM-SEED-001'
            ],
            [
                'status' => 'pendente',
                'validade' => now()->addMonths(1),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Notificações base
        Notification::updateOrCreate(
            ['user_id' => $cliente->id, 'title' => 'Boas-vindas'],
            [
                'message' => 'Você ganhou 50 pontos de boas-vindas.',
                'type' => 'info',
                'payload' => ['origin' => 'seed']
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $empresaUser->id, 'title' => 'Nova promoção ativa'],
            [
                'message' => 'Sua promoção de boas-vindas está ativa.',
                'type' => 'success',
                'payload' => ['promocao_id' => $promoId]
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $admin->id ?? null, 'title' => 'Admin: revise relatórios'],
            [
                'message' => 'Relatórios disponíveis para revisão.',
                'type' => 'alert',
                'payload' => ['section' => 'reports']
            ]
        );

        // Clientes demo ricos
        echo "\n📚 Criando 30 clientes com pontos/cupons/histórico...\n";
        for ($i = 1; $i <= 30; $i++) {
            $user = User::updateOrCreate(
                ['email' => "demo_cliente{$i}@email.com"],
                [
                    'name' => "Cliente Demo {$i}",
                    'password' => Hash::make('senha123'),
                    'perfil' => 'cliente',
                    'telefone' => sprintf('(11) 9%04d-%04d', rand(1000, 9999), rand(1000, 9999)),
                    'status' => 'ativo',
                    'pontos' => rand(200, 1500),
                    'email_verified_at' => now()
                ]
            );

            for ($j = 0; $j < 3; $j++) {
                DB::table('pontos')->insert([
                    'user_id' => $user->id,
                    'empresa_id' => $empresaRecord->id,
                    'pontos' => rand(50, 200),
                    'tipo' => 'ganho',
                    'descricao' => 'Compra ' . ($j + 1),
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }

            DB::table('cupons')->insertOrIgnore([
                'user_id' => $user->id,
                'promocao_id' => $promoId,
                'codigo' => 'CUPOM-DEMO-' . $i,
                'status' => $i % 2 === 0 ? 'used' : 'pending',
                'validade' => now()->addDays(30),
                'created_at' => now()->subDays(rand(1, 10)),
                'updated_at' => now(),
            ]);

            Notification::updateOrCreate(
                ['user_id' => $user->id, 'title' => "Saldo atualizado #{$i}"],
                [
                    'message' => 'Você recebeu pontos em sua última compra.',
                    'type' => 'info',
                    'payload' => ['origin' => 'seed'],
                ]
            );

            if ($i % 10 === 0) echo "  ✅ {$i} clientes demo criados...\n";
        }
        echo "✅ Clientes demo criados\n";

        // Empresas adicionais com promoções
        echo "\n🏪 Criando empresas parceiras...\n";
        $empresasData = [
            ['nome' => 'Restaurante Sabor & Arte', 'ramo' => 'restaurante', 'logo' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=400&fit=crop', 'descricao' => 'Restaurante contemporâneo.'],
            ['nome' => 'Academia Corpo Forte', 'ramo' => 'academia', 'logo' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=400&fit=crop', 'descricao' => 'Academia completa.'],
            ['nome' => 'Cafeteria Aroma Premium', 'ramo' => 'cafeteria', 'logo' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400&h=400&fit=crop', 'descricao' => 'Cafés especiais e doces artesanais.'],
            ['nome' => 'Pet Shop Amigo Fiel', 'ramo' => 'pet_shop', 'logo' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400&h=400&fit=crop', 'descricao' => 'Tudo para seu pet.'],
            ['nome' => 'Salão Beleza Total', 'ramo' => 'salao', 'logo' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=400&fit=crop', 'descricao' => 'Beleza completa.'],
            ['nome' => 'Mercado Bom Preço', 'ramo' => 'mercado', 'logo' => 'https://images.unsplash.com/photo-1583736902931-063382c8e67f?w=400&h=400&fit=crop', 'descricao' => 'Ofertas diárias.'],
            ['nome' => 'Farmácia Saúde Mais', 'ramo' => 'farmacia', 'logo' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400&h=400&fit=crop', 'descricao' => 'Farmácia completa.'],
            ['nome' => 'Padaria Pão Quentinho', 'ramo' => 'padaria', 'logo' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=400&fit=crop', 'descricao' => 'Pães frescos diários.'],
        ];

        foreach ($empresasData as $empData) {
            $exists = DB::table('empresas')->where('nome', $empData['nome'])->first();
            if ($exists) continue;
            $cid = DB::table('empresas')->insertGetId([
                'owner_id' => $empresaUser->id,
                'nome' => $empData['nome'],
                'ramo' => $empData['ramo'],
                'logo' => $empData['logo'],
                'descricao' => $empData['descricao'],
                'endereco' => 'Rua Exemplo, ' . rand(100, 9999) . ' - São Paulo, SP',
                'telefone' => sprintf('(11) 9%04d-%04d', rand(1000, 9999), rand(1000, 9999)),
                'cnpj' => sprintf('%02d.%03d.%03d/%04d-%02d', rand(10, 99), rand(100, 999), rand(100, 999), rand(1000, 9999), rand(10, 99)),
                'ativo' => true,
                'points_multiplier' => 1.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('promocoes')->insert([
                [
                    'empresa_id' => $cid,
                    'titulo' => 'Cashback especial',
                    'descricao' => 'Ganhe pontos extras nas compras desta semana.',
                    'desconto' => 5,
                    'imagem' => $empData['logo'],
                    'data_inicio' => now()->subDays(1),
                    'validade' => now()->addDays(20),
                    'ativo' => true,
                    'status' => 'ativa',
                    'visualizacoes' => rand(10, 50),
                    'resgates' => rand(0, 10),
                    'usos' => rand(0, 5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'empresa_id' => $cid,
                    'titulo' => 'Oferta limitada',
                    'descricao' => 'Desconto progressivo em produtos selecionados.',
                    'desconto' => 15,
                    'imagem' => $empData['logo'],
                    'data_inicio' => now()->subDays(5),
                    'validade' => now()->addDays(10),
                    'ativo' => rand(0, 1) === 1,
                    'status' => rand(0, 1) === 1 ? 'ativa' : 'pausada',
                    'visualizacoes' => rand(5, 30),
                    'resgates' => rand(0, 8),
                    'usos' => rand(0, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        echo "✅ Empresas parceiras e promoções criadas\n";

        echo "\n========================================\n";
        echo "✅ SEEDER CONCLUÍDO COM SUCESSO!\n";
        echo "========================================\n";
        echo "\n📃 CREDENCIAIS DE ACESSO:\n";
        echo "┌────────────────────────────────────────┐\n";
        echo "│ Admin:   admin@temdetudo.com / admin123         │\n";
        echo "│ Cliente: cliente@teste.com / 123456             │\n";
        echo "│ Empresa: empresa@teste.com / 123456             │\n";
        echo "│ Clientes demo: demo_cliente1-30@email.com / senha123 │\n";
        echo "└────────────────────────────────────────┘\n";
        echo "\n";
        $totalUsers = User::count();
        echo "📊 Total de usuários: {$totalUsers}\n";
        echo "========================================\n\n";
    }
}
