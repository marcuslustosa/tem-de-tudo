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
        // Usuários de teste para diferentes roles

        // Admin Master
        User::factory()->create([
            'name' => 'Admin Master',
            'email' => 'admin@temdetudo.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
        ]);

        // Cliente
        User::factory()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@temdetudo.com',
            'password' => Hash::make('Cliente123!'),
            'role' => 'cliente',
        ]);

        // Empresa
        User::factory()->create([
            'name' => 'Empresa Teste',
            'email' => 'empresa@temdetudo.com',
            'password' => Hash::make('Empresa123!'),
            'role' => 'empresa',
        ]);

        // Usuário adicional para testes
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'cliente',
        ]);
    }
}
