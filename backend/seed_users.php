<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Criar usuários de teste diretamente
$users = [
    [
        'name' => 'Admin Vivo',
        'email' => 'admin@vivo.com.br',
        'password' => Hash::make('Admin123!'),
        'role' => 'admin',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Cliente Vivo',
        'email' => 'cliente@vivo.com.br',
        'password' => Hash::make('Cliente123!'),
        'role' => 'cliente',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Empresa Vivo',
        'email' => 'empresa@vivo.com.br',
        'password' => Hash::make('Empresa123!'),
        'role' => 'empresa',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'cliente',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

foreach ($users as $userData) {
    DB::table('users')->insert($userData);
}

echo "Usuários de teste criados com sucesso!\n";
echo "\nCredenciais VIVO:\n";
echo "Admin: admin@vivo.com.br / Admin123!\n";
echo "Cliente: cliente@vivo.com.br / Cliente123!\n";
echo "Empresa: empresa@vivo.com.br / Empresa123!\n";
echo "Test: test@example.com / password\n";
