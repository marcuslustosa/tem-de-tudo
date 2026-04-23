<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== USUÁRIOS NO BANCO ===\n";
echo "Total: " . User::count() . "\n";
echo "Admins: " . User::where('perfil', 'admin')->count() . "\n";
echo "Clientes: " . User::where('perfil', 'cliente')->count() . "\n";
echo "Empresas: " . User::where('perfil', 'empresa')->count() . "\n\n";

echo "=== VERIFICANDO USUÁRIOS DE TESTE ===\n";
$testUsers = [
    'admin@temdetudo.com',
    'cliente@teste.com',
    'empresa@teste.com'
];

foreach ($testUsers as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "✅ $email existe (perfil: {$user->perfil})\n";
    } else {
        echo "❌ $email NÃO existe\n";
    }
}

echo "\n=== AMOSTRA DE USUÁRIOS ===\n";
User::take(10)->get()->each(function($u) {
    echo "- {$u->email} (perfil: {$u->perfil}, ID: {$u->id})\n";
});
