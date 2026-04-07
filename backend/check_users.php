<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 USUÁRIOS DE TESTE TEM DE TUDO:\n";
echo "==================================\n\n";

$emails = [
    'admin@temdetudo.com',
    'cliente@teste.com',
    'empresa@teste.com',
    'admin@temdetudo.com.br',
    'cliente1@temdetudo.com.br',
    'empresa1@temdetudo.com.br',
];

$users = DB::table('users')->whereIn('email', $emails)->get();

if($users->count() > 0) {
    foreach($users as $user) {
        $role = $user->role ?? 'N/A';
        $pontos = isset($user->pontos) ? $user->pontos : 'N/A';
        echo "✅ {$user->name}\n";
        echo "   📧 Email: {$user->email}\n";
        echo "   👤 Perfil: {$role}\n";
        echo "   💰 Pontos: {$pontos}\n";
        echo "   📅 Criado: {$user->created_at}\n\n";
    }
    
    echo "\n🎯 CREDENCIAIS PARA TESTE:\n";
    echo "=========================\n";
    echo "🔴 ADMIN:   admin@temdetudo.com / senha123\n";
    echo "🔵 CLIENTE: cliente@teste.com   / senha123\n";
    echo "🟢 EMPRESA: empresa@teste.com   / senha123\n\n";
} else {
    echo "❌ Nenhum usuário de teste encontrado!\n";
    echo "📋 Execute: php seed_users.php para criar os usuários\n";
}

// Verificar total de usuários
$total = DB::table('users')->count();
echo "📊 Total de usuários no sistema: {$total}\n";