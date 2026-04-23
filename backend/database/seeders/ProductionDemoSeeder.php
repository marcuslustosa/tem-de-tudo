<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Admin;
use App\Models\SubscriptionPlan;
use App\Models\CampanhaMultiplicador;
use App\Models\Badge;
use App\Models\FraudRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionDemoSeeder extends Seeder
{
    /**
     * Seed completo para demonstração/produção.
     * Cria dados realistas para apresentar ao cliente.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seed de demonstração para produção...');

        // 1. Admin master
        $this->seedAdmin();

        // 2. Planos de subscription
        $this->seedSubscriptionPlans();

        // 3. Empresas parceiras
        $empresas = $this->seedEmpresas();

        // 4. Usuários clientes
        $users = $this->seedUsers();

        // 5. Badges do sistema
        $this->seedBadges();

        // 6. Campanhas de multiplicador
        $this->seedCampanhas($empresas);

        // 7. Regras de anti-fraude
        $this->seedFraudRules();

        $this->command->info('✅ Seed de demonstração concluído com sucesso!');
        $this->command->info('📧 Admin: admin@temdetudo.com / senha: Admin@2026');
        $this->command->info('👤 Cliente demo: cliente@demo.com / senha: Demo@2026');
    }

    private function seedAdmin(): void
    {
        $this->command->info('👨‍💼 Criando admin master...');

        Admin::firstOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Administrador Master',
                'password' => Hash::make('Admin@2026'),
                'permissions' => json_encode([
                    'all',
                    'manage_users',
                    'manage_empresas',
                    'view_reports',
                    'manage_system',
                    'manage_billing',
                ]),
                'ativo' => true,
            ]
        );
    }

    private function seedSubscriptionPlans(): void
    {
        $this->command->info('💳 Criando planos de subscription...');

        $plans = [
            ['name' => 'Básico', 'monthly_price' => 99.00],
            ['name' => 'Pro', 'monthly_price' => 199.00],
            ['name' => 'Enterprise', 'monthly_price' => 499.00],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }

    private function seedEmpresas(): array
    {
        $this->command->info('🏢 Criando empresas parceiras...');

        $empresas = [
            [
                'nome' => 'Pizzaria Bella Napoli',
                'email' => 'contato@bellanapoli.com',
                'categoria' => 'Alimentação',
                'descricao' => 'A melhor pizza artesanal da cidade',
                'telefone' => '(11) 3456-7890',
                'endereco' => 'Rua das Pizzas, 123 - Centro',
            ],
            [
                'nome' => 'Academia FitLife',
                'email' => 'contato@fitlife.com',
                'categoria' => 'Saúde e Bem-estar',
                'descricao' => 'Academia completa com equipamentos modernos',
                'telefone' => '(11) 3456-7891',
                'endereco' => 'Av. da Saúde, 456 - Jardins',
            ],
            [
                'nome' => 'Cafeteria Aroma & Sabor',
                'email' => 'contato@aromaeador.com',
                'categoria' => 'Alimentação',
                'descricao' => 'Cafés especiais e bolos artesanais',
                'telefone' => '(11) 3456-7892',
                'endereco' => 'Rua dos Cafés, 789 - Vila Nova',
            ],
            [
                'nome' => 'Farmácia Saúde Total',
                'email' => 'contato@saudetotal.com',
                'categoria' => 'Saúde',
                'descricao' => 'Farmácia com manipulação e delivery',
                'telefone' => '(11) 3456-7893',
                'endereco' => 'Av. Principal, 321 - Centro',
            ],
            [
                'nome' => 'Pet Shop Amigo Fiel',
                'email' => 'contato@amigofiel.com',
                'categoria' => 'Pet',
                'descricao' => 'Tudo para seu pet com muito amor',
                'telefone' => '(11) 3456-7894',
                'endereco' => 'Rua dos Pets, 654 - Zona Sul',
            ],
        ];

        $empresasCreated = [];
        foreach ($empresas as $empresaData) {
            $empresa = Empresa::firstOrCreate(
                ['email' => $empresaData['email']],
                array_merge($empresaData, [
                    'password' => Hash::make('Empresa@2026'),
                    'ativo' => true,
                    'points_multiplier' => 1.0,
                    'qr_code_token' => Str::random(32),
                ])
            );
            $empresasCreated[] = $empresa;
        }

        return $empresasCreated;
    }

    private function seedUsers(): array
    {
        $this->command->info('👥 Criando usuários clientes...');

        $users = [
            [
                'name' => 'João Silva',
                'email' => 'cliente@demo.com',
                'pontos' => 1500,
                'pontos_lifetime' => 5000,
                'nivel' => 'Ouro',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@demo.com',
                'pontos' => 3200,
                'pontos_lifetime' => 12000,
                'nivel' => 'Platina',
            ],
            [
                'name' => 'Pedro Costa',
                'email' => 'pedro@demo.com',
                'pontos' => 800,
                'pontos_lifetime' => 2500,
                'nivel' => 'Prata',
            ],
            [
                'name' => 'Ana Paula',
                'email' => 'ana@demo.com',
                'pontos' => 5000,
                'pontos_lifetime' => 25000,
                'nivel' => 'Diamante',
            ],
            [
                'name' => 'Carlos Eduardo',
                'email' => 'carlos@demo.com',
                'pontos' => 450,
                'pontos_lifetime' => 1200,
                'nivel' => 'Bronze',
            ],
        ];

        $usersCreated = [];
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('Demo@2026'),
                    'cpf' => $this->generateFakeCPF(),
                    'data_nascimento' => now()->subYears(rand(20, 60))->format('Y-m-d'),
                    'telefone' => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'multiplicador_pontos' => $this->getMultiplierByLevel($userData['nivel']),
                    'referral_code' => strtoupper(Str::random(8)),
                ])
            );
            $usersCreated[] = $user;
        }

        return $usersCreated;
    }

    private function seedBadges(): void
    {
        $this->command->info('🏅 Criando badges do sistema...');

        $badges = [
            ['nome' => 'Iniciante', 'descricao' => 'Primeiro check-in realizado', 'icone' => '🌟', 'criterio_tipo' => 'checkins', 'criterio_valor' => 1],
            ['nome' => 'Explorador', 'descricao' => '10 check-ins em empresas diferentes', 'icone' => '🗺️', 'criterio_tipo' => 'empresas_visitadas', 'criterio_valor' => 10],
            ['nome' => 'Fiel', 'descricao' => '50 check-ins realizados', 'icone' => '💎', 'criterio_tipo' => 'checkins', 'criterio_valor' => 50],
            ['nome' => 'Milionário', 'descricao' => 'Acumulou 10.000 pontos lifetime', 'icone' => '💰', 'criterio_tipo' => 'pontos_lifetime', 'criterio_valor' => 10000],
            ['nome' => 'Streak Master', 'descricao' => '30 dias consecutivos de check-in', 'icone' => '🔥', 'criterio_tipo' => 'dias_consecutivos', 'criterio_valor' => 30],
        ];

        foreach ($badges as $badgeData) {
            Badge::firstOrCreate(
                ['nome' => $badgeData['nome']],
                array_merge($badgeData, ['ativo' => true])
            );
        }
    }

    private function seedCampanhas(array $empresas): void
    {
        $this->command->info('🎯 Criando campanhas de multiplicador...');

        if (empty($empresas)) {
            return;
        }

        // Campanha ativa na pizzaria (2x pontos)
        CampanhaMultiplicador::firstOrCreate(
            ['empresa_id' => $empresas[0]->id, 'nome' => 'Fim de Semana em Dobro'],
            [
                'descricao' => 'Ganhe 2x pontos em todos os pedidos nos finais de semana',
                'multiplicador' => 2.0,
                'data_inicio' => now()->startOfWeek(),
                'data_fim' => now()->endOfWeek()->addWeeks(4),
                'ativo' => true,
            ]
        );

        // Campanha ativa na academia (3x pontos)
        if (isset($empresas[1])) {
            CampanhaMultiplicador::firstOrCreate(
                ['empresa_id' => $empresas[1]->id, 'nome' => 'Janeiro em Forma'],
                [
                    'descricao' => 'Triplique seus pontos nos treinos de janeiro',
                    'multiplicador' => 3.0,
                    'data_inicio' => now()->startOfMonth(),
                    'data_fim' => now()->endOfMonth(),
                    'ativo' => true,
                ]
            );
        }
    }

    private function seedFraudRules(): void
    {
        $this->command->info('🛡️ Criando regras de anti-fraude...');

        $rules = [
            [
                'name' => 'Device Limit',
                'description' => 'Máximo de 10 transações por device por hora',
                'rule_type' => 'device_limit',
                'parameters' => json_encode(['max_transactions' => 10, 'time_window_minutes' => 60]),
            ],
            [
                'name' => 'IP Limit',
                'description' => 'Máximo de 20 transações por IP por hora',
                'rule_type' => 'ip_limit',
                'parameters' => json_encode(['max_transactions' => 20, 'time_window_minutes' => 60]),
            ],
            [
                'name' => 'Geo Anomaly',
                'description' => 'Detecta viagem impossível (>100km/h)',
                'rule_type' => 'geo_anomaly',
                'parameters' => json_encode(['max_km_per_hour' => 100]),
            ],
            [
                'name' => 'Velocity Check',
                'description' => 'Mínimo de 10 segundos entre transações',
                'rule_type' => 'velocity',
                'parameters' => json_encode(['min_seconds' => 10]),
            ],
            [
                'name' => 'Geo Fencing',
                'description' => 'Transação deve estar próxima à empresa (raio configurável)',
                'rule_type' => 'geo_fencing',
                'parameters' => json_encode(['max_distance_km' => 5]),
            ],
        ];

        foreach ($rules as $ruleData) {
            FraudRule::firstOrCreate(
                ['name' => $ruleData['name']],
                array_merge($ruleData, [
                    'is_active' => true,
                    'severity' => 'medium',
                ])
            );
        }
    }

    private function generateFakeCPF(): string
    {
        $n = rand(100000000, 999999999);
        return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . rand(10, 99);
    }

    private function getMultiplierByLevel(string $nivel): float
    {
        return match ($nivel) {
            'Bronze' => 1.0,
            'Prata' => 1.2,
            'Ouro' => 1.5,
            'Platina' => 2.0,
            'Diamante' => 3.0,
            default => 1.0,
        };
    }
}
