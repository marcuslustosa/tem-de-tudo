<?php
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \Illuminate\Support\Facades\DB::select('SELECT id, name, email, perfil, status, substr(password,1,30) as pass_part FROM users LIMIT 15');
foreach ($users as $u) {
    echo "ID:{$u->id} | {$u->email} | perfil:{$u->perfil} | status:{$u->status} | pass:{$u->pass_part}\n";
}

echo "\nTotal: " . count(\App\Models\User::all()) . " usuários\n";
