<?php
// Script temporário para atualizar senhas dos usuários de teste

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "🔧 Atualizando senhas dos usuários de teste...\n\n";

$users = [
    'cliente@teste.com',
    'empresa@teste.com',
    'maria.silva@teste.com',
    'joao.santos@teste.com',
    'ana.costa@teste.com'
];

$updated = 0;
$notFound = 0;

foreach ($users as $email) {
    $user = User::where('email', $email)->first();
    
    if ($user) {
        $user->password = Hash::make('senha123');
        $user->save();
        echo "✅ $email - Senha atualizada\n";
        $updated++;
    } else {
        echo "⚠️  $email - Não encontrado\n";
        $notFound++;
    }
}

echo "\n📊 RESUMO:\n";
echo "Atualizados: $updated\n";
echo "Não encontrados: $notFound\n";
echo "\n✅ Todos os usuários agora podem logar com: senha123\n";
