<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    private function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);
        $allowed = array_flip($columns);

        return array_intersect_key($data, $allowed);
    }

    private function upsert(string $table, array $where, array $values): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $where = $this->filterColumns($table, $where);
        $values = $this->filterColumns($table, $values);

        if (empty($where) || empty($values)) {
            return;
        }

        DB::table($table)->updateOrInsert($where, $values);
    }

    public function run(): void
    {
        $now = now();
        $logos = [
            '/assets/images/company1.jpg',
            '/assets/images/company2.jpg',
            '/assets/images/company3.jpg',
            '/assets/images/company4.jpg',
        ];

        // Usuarios base obrigatorios
        $admin = User::updateOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Administrador Master',
                'password' => Hash::make('admin123'),
                'perfil' => 'admin',
                'status' => 'ativo',
                'telefone' => '(11) 99999-0001',
                'permissions' => json_encode([
                    'manage_system',
                    'manage_users',
                    'view_reports',
                    'manage_companies',
                    'manage_promotions',
                ]),
                'pontos' => 0,
                'email_verified_at' => $now,
            ]
        );

        $cliente = User::updateOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'name' => 'Cliente Teste',
                'password' => Hash::make('123456'),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 99999-0002',
                'pontos' => 350,
                'email_verified_at' => $now,
            ]
        );

        $empresaUser = User::updateOrCreate(
            ['email' => 'empresa@teste.com'],
            [
                'name' => 'Empresa Teste Ltda',
                'password' => Hash::make('123456'),
                'perfil' => 'empresa',
                'status' => 'ativo',
                'telefone' => '(11) 99999-0003',
                'pontos' => 0,
                'email_verified_at' => $now,
            ]
        );

        // Empresa base
        $this->upsert('empresas',
            ['owner_id' => $empresaUser->id],
            [
                'owner_id' => $empresaUser->id,
                'nome' => 'Empresa Teste Ltda',
                'ramo' => 'restaurante',
                'descricao' => 'Empresa seed para demonstracao',
                'logo' => $logos[0],
                'endereco' => 'Av. Central, 1000 - Sao Paulo, SP',
                'telefone' => '(11) 4000-0000',
                'cnpj' => '12.345.678/0001-00',
                'ativo' => true,
                'status' => 'ativo',
                'points_multiplier' => 1.0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $empresaBaseId = DB::table('empresas')->where('owner_id', $empresaUser->id)->value('id');

        if ($empresaBaseId && Schema::hasTable('promocoes')) {
            $this->upsert('promocoes',
                ['empresa_id' => $empresaBaseId, 'titulo' => '10% OFF na primeira compra'],
                [
                    'empresa_id' => $empresaBaseId,
                    'titulo' => '10% OFF na primeira compra',
                    'descricao' => 'Desconto de boas-vindas para novos clientes',
                    'desconto' => 10,
                    'imagem' => $logos[1],
                    'data_inicio' => $now->copy()->subDays(2),
                    'validade' => $now->copy()->addMonths(1),
                    'ativo' => true,
                    'status' => 'ativa',
                    'visualizacoes' => 25,
                    'resgates' => 4,
                    'usos' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Dados do cliente base
        if (Schema::hasTable('pontos') && $empresaBaseId) {
            DB::table('pontos')
                ->where('user_id', $cliente->id)
                ->where('descricao', 'like', '[SEED]%')
                ->delete();

            foreach ([120, 80, 150] as $idx => $pts) {
                DB::table('pontos')->insert($this->filterColumns('pontos', [
                    'user_id' => $cliente->id,
                    'empresa_id' => $empresaBaseId,
                    'pontos' => $pts,
                    'tipo' => 'ganho',
                    'descricao' => '[SEED] Compra ' . ($idx + 1),
                    'created_at' => $now->copy()->subDays($idx + 1),
                    'updated_at' => $now,
                ]));
            }
        }

        if (Schema::hasTable('coupons') && $empresaBaseId) {
            $this->upsert('coupons',
                ['user_id' => $cliente->id, 'codigo' => 'CUPOM-SEED-001'],
                [
                    'user_id' => $cliente->id,
                    'empresa_id' => $empresaBaseId,
                    'codigo' => 'CUPOM-SEED-001',
                    'tipo' => 'discount',
                    'descricao' => 'Cupom de boas-vindas',
                    'custo_pontos' => 0,
                    'porcentagem_desconto' => 10,
                    'status' => 'active',
                    'expira_em' => $now->copy()->addMonths(1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Empresas extras para listagem
        $empresas = [
            ['nome' => 'Restaurante Sabor & Arte', 'ramo' => 'restaurante', 'descricao' => 'Restaurante contemporaneo.'],
            ['nome' => 'Academia Corpo Forte', 'ramo' => 'academia', 'descricao' => 'Academia completa.'],
            ['nome' => 'Cafeteria Aroma Premium', 'ramo' => 'cafeteria', 'descricao' => 'Cafes especiais e doces artesanais.'],
            ['nome' => 'Pet Shop Amigo Fiel', 'ramo' => 'pet_shop', 'descricao' => 'Tudo para seu pet.'],
            ['nome' => 'Salao Beleza Total', 'ramo' => 'salao', 'descricao' => 'Beleza completa.'],
            ['nome' => 'Mercado Bom Preco', 'ramo' => 'mercado', 'descricao' => 'Ofertas diarias.'],
            ['nome' => 'Farmacia Saude Mais', 'ramo' => 'farmacia', 'descricao' => 'Farmacia completa.'],
            ['nome' => 'Padaria Pao Quentinho', 'ramo' => 'padaria', 'descricao' => 'Paes frescos diarios.'],
        ];

        foreach ($empresas as $idx => $empresa) {
            $logo = $logos[$idx % count($logos)];
            $this->upsert('empresas',
                ['nome' => $empresa['nome']],
                [
                    'owner_id' => $empresaUser->id,
                    'nome' => $empresa['nome'],
                    'ramo' => $empresa['ramo'],
                    'descricao' => $empresa['descricao'],
                    'logo' => $logo,
                    'endereco' => 'Rua Exemplo, ' . (100 + $idx) . ' - Sao Paulo, SP',
                    'telefone' => sprintf('(11) 9%04d-%04d', 1000 + $idx, 2000 + $idx),
                    'cnpj' => sprintf('11.111.111/%04d-11', 1000 + $idx),
                    'ativo' => true,
                    'status' => 'ativo',
                    'points_multiplier' => 1.0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $empresaId = DB::table('empresas')->where('nome', $empresa['nome'])->value('id');
            if ($empresaId && Schema::hasTable('promocoes')) {
                $this->upsert('promocoes',
                    ['empresa_id' => $empresaId, 'titulo' => 'Cashback especial'],
                    [
                        'empresa_id' => $empresaId,
                        'titulo' => 'Cashback especial',
                        'descricao' => 'Ganhe pontos extras nas compras desta semana.',
                        'desconto' => 5,
                        'imagem' => $logo,
                        'data_inicio' => $now->copy()->subDays(1),
                        'validade' => $now->copy()->addDays(20),
                        'ativo' => true,
                        'status' => 'ativa',
                        'visualizacoes' => 18,
                        'resgates' => 3,
                        'usos' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $this->upsert('promocoes',
                    ['empresa_id' => $empresaId, 'titulo' => 'Oferta limitada'],
                    [
                        'empresa_id' => $empresaId,
                        'titulo' => 'Oferta limitada',
                        'descricao' => 'Desconto progressivo em produtos selecionados.',
                        'desconto' => 15,
                        'imagem' => $logo,
                        'data_inicio' => $now->copy()->subDays(5),
                        'validade' => $now->copy()->addDays(10),
                        'ativo' => true,
                        'status' => 'pausada',
                        'visualizacoes' => 11,
                        'resgates' => 1,
                        'usos' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // Clientes demo
        for ($i = 1; $i <= 20; $i++) {
            $demo = User::updateOrCreate(
                ['email' => "demo_cliente{$i}@email.com"],
                [
                    'name' => "Cliente Demo {$i}",
                    'password' => Hash::make('senha123'),
                    'perfil' => 'cliente',
                    'status' => 'ativo',
                    'telefone' => sprintf('(11) 9%04d-%04d', 3000 + $i, 4000 + $i),
                    'pontos' => 200 + ($i * 37),
                    'email_verified_at' => $now,
                ]
            );

            if (Schema::hasTable('coupons') && $empresaBaseId) {
                $this->upsert('coupons',
                    ['user_id' => $demo->id, 'codigo' => 'CUPOM-DEMO-' . $i],
                    [
                        'user_id' => $demo->id,
                        'empresa_id' => $empresaBaseId,
                        'codigo' => 'CUPOM-DEMO-' . $i,
                        'tipo' => 'discount',
                        'descricao' => 'Cupom demo',
                        'custo_pontos' => 0,
                        'porcentagem_desconto' => 5,
                        'status' => $i % 2 === 0 ? 'used' : 'active',
                        'expira_em' => $now->copy()->addDays(30),
                        'usado_em' => $i % 2 === 0 ? $now->copy()->subDay() : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            Notification::updateOrCreate(
                ['user_id' => $demo->id, 'title' => "Saldo atualizado #{$i}"],
                [
                    'message' => 'Voce recebeu pontos em sua ultima compra.',
                    'type' => 'info',
                    'payload' => ['origin' => 'seed'],
                ]
            );
        }

        // Notificacoes base
        Notification::updateOrCreate(
            ['user_id' => $cliente->id, 'title' => 'Boas-vindas'],
            [
                'message' => 'Voce ganhou pontos de boas-vindas.',
                'type' => 'info',
                'payload' => ['origin' => 'seed'],
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $empresaUser->id, 'title' => 'Nova promocao ativa'],
            [
                'message' => 'Sua promocao de boas-vindas esta ativa.',
                'type' => 'success',
                'payload' => ['origin' => 'seed'],
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $admin->id, 'title' => 'Admin: revise relatorios'],
            [
                'message' => 'Relatorios disponiveis para revisao.',
                'type' => 'alert',
                'payload' => ['origin' => 'seed'],
            ]
        );

        // Conteudo admin
        if (Schema::hasTable('categorias')) {
            foreach ([
                ['name' => 'Restaurantes', 'slug' => 'restaurantes', 'active' => true, 'position' => 1],
                ['name' => 'Beleza', 'slug' => 'beleza', 'active' => true, 'position' => 2],
                ['name' => 'Saude', 'slug' => 'saude', 'active' => true, 'position' => 3],
            ] as $cat) {
                $this->upsert('categorias', ['slug' => $cat['slug']], array_merge($cat, ['updated_at' => $now, 'created_at' => $now]));
            }
        }

        if (Schema::hasTable('banners')) {
            $this->upsert('banners',
                ['title' => 'Semana de Pontos em Dobro'],
                [
                    'title' => 'Semana de Pontos em Dobro',
                    'image_url' => $logos[2],
                    'link' => '/recompensas.html',
                    'active' => true,
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $this->upsert('banners',
                ['title' => 'Novos Parceiros na Plataforma'],
                [
                    'title' => 'Novos Parceiros na Plataforma',
                    'image_url' => $logos[3],
                    'link' => '/parceiros_tem_de_tudo.html',
                    'active' => true,
                    'position' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
