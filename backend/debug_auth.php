<?php
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'admin@temdetudo.com')->first();
echo "User found: " . ($user ? "YES" : "NO") . "\n";
if ($user) {
    echo "Password hash: " . $user->password . "\n";
    $passwords = ['admin123', 'Temdetudo123!', '123456', 'password', 'Admin@123', 'admin', 'temdetudo', 'Temdetudo@123'];
    foreach ($passwords as $p) {
        $match = \Illuminate\Support\Facades\Hash::check($p, $user->password);
        echo "Check '$p': " . ($match ? "MATCH!" : "nope") . "\n";
    }
}

// Check most recent users  
echo "\n--- Últimos usuários registrados ---\n";
$recent = \App\Models\User::orderBy('created_at', 'desc')->take(5)->get(['id','email','perfil','status','created_at']);
foreach ($recent as $u) {
    echo "{$u->id} | {$u->email} | {$u->perfil} | {$u->status} | {$u->created_at}\n";
}

// Check se há seed file
$seedFiles = glob(__DIR__ . '/database/*.sql');
echo "\n--- Arquivos SQL ---\n";
foreach ($seedFiles as $f) {
    echo basename($f) . "\n";
}
