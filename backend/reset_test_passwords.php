<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== REDEFININDO SENHAS DOS USUÁRIOS DE TESTE ===\n\n";

$testUsers = [
    ['email' => 'admin@temdetudo.com', 'password' => 'senha123'],
    ['email' => 'cliente@teste.com', 'password' => 'senha123'],
    ['email' => 'empresa@teste.com', 'password' => 'senha123'],
];

foreach ($testUsers as $data) {
    $user = User::where('email', $data['email'])->first();
    if ($user) {
        $user->password = Hash::make($data['password']);
        $user->save();
        echo "✅ Senha redefinida para: {$data['email']}\n";
        echo "   Login: {$data['email']} / Senha: {$data['password']}\n\n";
    } else {
        echo "❌ Usuário não encontrado: {$data['email']}\n\n";
    }
}

echo "=== TESTE DE AUTENTICAÇÃO ===\n\n";

foreach ($testUsers as $data) {
    $user = User::where('email', $data['email'])->first();
    if ($user && Hash::check($data['password'], $user->password)) {
        echo "✅ Autenticação OK: {$data['email']}\n";
    } else {
        echo "❌ Falha na autenticação: {$data['email']}\n";
    }
}
