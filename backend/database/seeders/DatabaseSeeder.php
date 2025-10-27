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
            ['email' => 'admin@sistema.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'pontos' => 0,
                'pontos_pendentes' => 0,
                'telefone' => '(11) 99999-9999',
                'email_verified_at' => now(),
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

        $this->command->info('✅ Usuários padrão criados!');
        $this->command->info('👤 Admin: admin@sistema.com / admin123');
        $this->command->info('👥 Cliente: cliente@teste.com / 123456');
        $this->command->info('🏪 Empresa: empresa@teste.com / 123456');
    }
}
