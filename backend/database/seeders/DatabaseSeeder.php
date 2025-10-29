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
        // Admin Master
        User::firstOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Administrador Master',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'pontos' => 1000,
                'pontos_pendentes' => 0,
                'telefone' => '(11) 99999-0001',
                'email_verified_at' => now(),
                'status' => 'ativo'
            ]
        );

        // Cliente de Teste
        User::firstOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'name' => 'Cliente Teste',
                'password' => Hash::make('123456'),
                'role' => 'cliente',
                'pontos' => 250,
                'pontos_pendentes' => 50,
                'telefone' => '(11) 99999-0002',
                'email_verified_at' => now(),
                'status' => 'ativo'
            ]
        );

        // Empresa Parceira
        User::firstOrCreate(
            ['email' => 'empresa@teste.com'],
            [
                'name' => 'Empresa Teste Ltda',
                'password' => Hash::make('123456'),
                'role' => 'empresa',
                'pontos' => 0,
                'pontos_pendentes' => 0,
                'telefone' => '(11) 99999-0003',
                'email_verified_at' => now(),
                'status' => 'ativo'
            ]
        );

        // Usuário VIP Gold
        User::firstOrCreate(
            ['email' => 'vip@teste.com'],
            [
                'name' => 'Cliente VIP',
                'password' => Hash::make('123456'),
                'role' => 'cliente',
                'pontos' => 2500, // Nível Gold
                'pontos_pendentes' => 100,
                'telefone' => '(11) 99999-0004',
                'email_verified_at' => now(),
                'status' => 'ativo'
            ]
        );

        // Cliente Teste
        User::firstOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'name' => 'Cliente Teste',
                'password' => Hash::make('123456'),
                'role' => 'cliente',
                'pontos' => 150,
                'pontos_pendentes' => 50,
                'telefone' => '(11) 77777-7777',
                'email_verified_at' => now(),
            ]
        );

        // Empresa Teste
        User::firstOrCreate(
            ['email' => 'empresa@teste.com'],
            [
                'name' => 'Empresa Teste',
                'password' => Hash::make('123456'),
                'role' => 'empresa',
                'pontos' => 0,
                'pontos_pendentes' => 0,
                'telefone' => '(11) 88888-8888',
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Usuários padrão criados!\n";
        echo "👤 Admin: admin@temdetudo.com / admin123\n";
        echo "👥 Cliente: cliente@teste.com / 123456\n";
        echo "🏪 Empresa: empresa@teste.com / 123456\n";
        echo "⭐ VIP: vip@teste.com / 123456\n";
    }
}
