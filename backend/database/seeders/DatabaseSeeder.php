<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

        // Empresa vinculada na tabela empresas
        $empresaRecord = DB::table('empresas')->where('owner_id', $empresa->id)->first();
        if (!$empresaRecord) {
            $empresaId = DB::table('empresas')->insertGetId([
                'owner_id' => $empresa->id,
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

        // Promoções seed (fluxo oficial EmpresaAPIController)
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
                'visualizacoes' => 0,
                'resgates' => 0,
                'usos' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Cupom seed para cliente teste
        $cupomExiste = DB::table('cupons')
            ->where('user_id', $cliente->id)
            ->where('promocao_id', $promoId)
            ->exists();
        if (!$cupomExiste) {
            DB::table('cupons')->insert([
                'user_id' => $cliente->id,
                'promocao_id' => $promoId,
                'codigo' => 'CUPOM-SEED-001',
                'status' => 'pendente',
                'validade' => now()->addMonths(1),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Notificações de exemplo
        Notification::updateOrCreate(
            ['user_id' => $cliente->id, 'title' => 'Boas-vindas'],
            [
                'message' => 'Você ganhou 50 pontos de boas-vindas.',
                'type' => 'info',
                'payload' => ['origin' => 'seed']
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $empresa->id, 'title' => 'Nova promoção ativa'],
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

        // DESABILITAR temporariamente criação de empresas e dados adicionais
        // para garantir que pelo menos os usuários são criados
        echo "\n⚠️ Empresas e dados adicionais desabilitados temporariamente\n";
        echo "✅ SETUP BÁSICO CONCLUÍDO - Apenas usuários criados\n";
        
        /*
        // Criar empresas fictícias na tabela empresas
        echo "\n🏪 Criando empresas parceiras...\n";
        
        $empresasData = [
            [
                'nome' => 'Restaurante Sabor & Arte', 
                'ramo' => 'restaurante', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=400&fit=crop',
                'descricao' => 'Restaurante contemporâneo com pratos autorais e ambiente sofisticado. Especialidade em gastronomia italiana e brasileira.'
            ],
            [
                'nome' => 'Academia Corpo Forte', 
                'ramo' => 'academia', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=400&fit=crop',
                'descricao' => 'Academia completa com musculação, funcional, pilates e aulas coletivas. Profissionais qualificados e equipamentos de última geração.'
            ],
            [
                'nome' => 'Cafeteria Aroma Premium', 
                'ramo' => 'cafeteria', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400&h=400&fit=crop',
                'descricao' => 'Cafés especiais, doces artesanais e ambiente aconchegante. Grãos selecionados e métodos de preparo tradicionais.'
            ],
            [
                'nome' => 'Pet Shop Amigo Fiel', 
                'ramo' => 'pet_shop', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400&h=400&fit=crop',
                'descricao' => 'Tudo para seu pet: ração, acessórios, banho e tosa. Veterinário disponível e produtos premium.'
            ],
            [
                'nome' => 'Salão Beleza Total', 
                'ramo' => 'salao', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=400&fit=crop',
                'descricao' => 'Salão de beleza completo: cabelo, unhas, estética e maquiagem. Profissionais especializados e produtos de qualidade.'
            ],
            [
                'nome' => 'Mercado Bom Preço', 
                'ramo' => 'mercado', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1583736902931-063382c8e67f?w=400&h=400&fit=crop',
                'descricao' => 'Supermercado com variedade de produtos, hortifruti fresquinho e ofertas diárias. Delivery disponível.'
            ],
            [
                'nome' => 'Farmácia Saúde Mais', 
                'ramo' => 'farmacia', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400&h=400&fit=crop',
                'descricao' => 'Farmácia completa com medicamentos, dermocosméticos e atendimento farmacêutico personalizado.'
            ],
            [
                'nome' => 'Padaria Pão Quentinho', 
                'ramo' => 'padaria', 
                'owner_id' => $empresa->id,
                'logo' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=400&fit=crop',
                'descricao' => 'Padaria artesanal com pães frescos, bolos caseiros e salgados deliciosos. Fabricação própria diária.'
            ],
        ];
        
        foreach ($empresasData as $empData) {
            // Verificar se empresa já existe
            $exists = DB::table('empresas')->where('nome', $empData['nome'])->exists();
            
            if (!$exists) {
                // Usar SQL RAW para garantir tipos corretos no PostgreSQL
                $endereco = 'Rua Exemplo, ' . rand(100, 9999) . ' - São Paulo, SP';
                $telefone = sprintf('(11) 9%04d-%04d', rand(1000, 9999), rand(1000, 9999));
                $cnpj = sprintf('%02d.%03d.%03d/%04d-%02d', rand(10, 99), rand(100, 999), rand(100, 999), rand(1000, 9999), rand(10, 99));
                
                DB::statement("INSERT INTO empresas 
                    (nome, owner_id, ramo, logo, descricao, endereco, telefone, cnpj, ativo, points_multiplier, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, 1.0, NOW(), NOW())",
                    [
                        $empData['nome'],
                        $empData['owner_id'],
                        $empData['ramo'],
                        $empData['logo'],
                        $empData['descricao'],
                        $endereco,
                        $telefone,
                        $cnpj
                    ]
                );
            }
        }
        echo "✅ 8 empresas parceiras criadas\n";

        // Chamar DataSeeder para criar empresas e dados adicionais
        echo "\n📊 Populando dados adicionais...\n";
        $this->call([
            DataSeeder::class,
        ]);
        */

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
        echo "📊 Total de usuários: {$totalUsers}\n";
        echo "========================================\n\n";
    }
}
