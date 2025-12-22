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
                'password' => Hash::make('admin123'),
                'perfil' => 'admin',
                'pontos' => 0,
                'nivel' => 'Diamante',
                'telefone' => '(11) 99999-9999',
                'status' => 'ativo',
                'email_verified_at' => now()
            ]
        );

        // Criar usuário admin operacional
        User::updateOrCreate(
            ['email' => 'operador@temdetudo.com'],
            [
                'name' => 'Operador Sistema',
                'email' => 'operador@temdetudo.com',
                'password' => Hash::make('operador123'),
                'perfil' => 'admin',
                'pontos' => 0,
                'nivel' => 'Ouro',
                'telefone' => '(11) 88888-8888',
                'status' => 'ativo',
                'email_verified_at' => now()
            ]
        );

        // Criar usuário cliente teste
        User::updateOrCreate(
            ['email' => 'cliente.extra@teste.com'],
            [
                'name' => 'Cliente Extra',
                'email' => 'cliente.extra@teste.com',
                'password' => Hash::make('cliente123'),
                'perfil' => 'cliente',
                'pontos' => 500,
                'nivel' => 'Bronze',
                'telefone' => '(11) 77777-7777',
                'status' => 'ativo',
                'email_verified_at' => now()
            ]
        );
    }
}