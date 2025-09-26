<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário admin principal
        User::updateOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Administrador Principal',
                'email' => 'admin@temdetudo.com',
                'password' => Hash::make('TemDeTudo2024!'),
                'role' => 'admin',
                'nivel_acesso' => 'super_admin',
                'pontos' => 0,
                'telefone' => '(11) 99999-9999',
                'status' => 'ativo',
                'is_active' => true,
                'permissions' => json_encode([
                    'create_users',
                    'manage_users', 
                    'view_reports',
                    'manage_companies',
                    'system_settings',
                    'audit_logs'
                ]),
                'email_verified_at' => now()
            ]
        );

        // Criar usuário admin operacional
        User::updateOrCreate(
            ['email' => 'operador@temdetudo.com'],
            [
                'name' => 'Operador Sistema',
                'email' => 'operador@temdetudo.com',
                'password' => Hash::make('Operador2024!'),
                'role' => 'admin',
                'nivel_acesso' => 'admin',
                'pontos' => 0,
                'telefone' => '(11) 88888-8888',
                'status' => 'ativo',
                'is_active' => true,
                'permissions' => json_encode([
                    'view_reports',
                    'manage_users',
                    'manage_companies'
                ]),
                'email_verified_at' => now()
            ]
        );

        // Criar usuário cliente teste
        User::updateOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'name' => 'Cliente Teste',
                'email' => 'cliente@teste.com',
                'password' => Hash::make('cliente123'),
                'role' => 'cliente',
                'pontos' => 500,
                'telefone' => '(11) 77777-7777',
                'status' => 'ativo',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );
    }
}