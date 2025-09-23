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
        'name' => 'Admin Master',
        'email' => 'admin@temdetudo.com',
        'password' => Hash::make('Admin123!'),
        'role' => 'admin',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Cliente Teste',
        'email' => 'cliente@temdetudo.com',
        'password' => Hash::make('Cliente123!'),
        'role' => 'cliente',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Empresa Teste',
        'email' => 'empresa@temdetudo.com',
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
echo "\nCredenciais:\n";
echo "Admin: admin@temdetudo.com / Admin123!\n";
echo "Cliente: cliente@temdetudo.com / Cliente123!\n";
echo "Empresa: empresa@temdetudo.com / Empresa123!\n";
echo "Test: test@example.com / password\n";
