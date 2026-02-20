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
        'name' => 'Admin Sistema',
        'email' => 'admin@temdetudo.com.br',
        'password' => Hash::make('Temdetudo123!'),
        'role' => 'admin',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Cliente Principal',
        'email' => 'cliente1@temdetudo.com.br',
        'password' => Hash::make('Temdetudo123!'),
        'role' => 'cliente',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Empresa Principal',
        'email' => 'empresa1@temdetudo.com.br',
        'password' => Hash::make('Temdetudo123!'),
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
    // Verificar se o usuário já existe
    $existing = DB::table('users')->where('email', $userData['email'])->first();
    if (!$existing) {
        DB::table('users')->insert($userData);
        echo "✅ Usuário {$userData['name']} criado!\n";
    } else {
        echo "⚠️  Usuário {$userData['email']} já existe, ignorando.\n";
    }
}

echo "Usuários de teste criados com sucesso!\n";
echo "\nCredenciais TEM DE TUDO:\n";
echo "Admin: admin@temdetudo.com.br / Temdetudo123!\n";
echo "Cliente: cliente1@temdetudo.com.br / Temdetudo123!\n";
echo "Empresa: empresa1@temdetudo.com.br / Temdetudo123!\n";
echo "Test: test@example.com / password\n";
