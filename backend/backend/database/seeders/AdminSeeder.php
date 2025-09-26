<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Criar super admin inicial
        Admin::create([
            'name' => 'Super Administrador',
            'email' => 'admin@temdetudo.com',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'permissions' => [
                'create_users',
                'manage_users', 
                'delete_users',
                'view_reports',
                'manage_system',
                'audit_logs'
            ],
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Criar admin moderador
        Admin::create([
            'name' => 'Admin Moderador',
            'email' => 'moderador@temdetudo.com', 
            'password' => Hash::make('mod123'),
            'role' => 'admin',
            'permissions' => [
                'create_users',
                'manage_users',
                'view_reports'
            ],
            'is_active' => true,
            'email_verified_at' => now()
        ]);
    }
}