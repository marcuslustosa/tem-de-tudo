<?php
// SETUP AUTOMÁTICO - Acesse uma vez e tudo fica pronto
// URL: https://aplicativo-tem-de-tudo.onrender.com/setup-auto.php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Automático - Tem de Tudo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            background: rgba(26, 26, 46, 0.9);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
        }
        h1 {
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #b0b0b0;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .log {
            background: rgba(0,0,0,0.5);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .info { color: #2196f3; }
        .warning { color: #ff9800; }
        .credentials {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .credential-item {
            padding: 8px 0;
            font-size: 14px;
        }
        .credential-item strong {
            color: #667eea;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        pre { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup Automático</h1>
        <p class="subtitle">Inicializando sistema...</p>

        <div class="log">
<?php
echo "<pre class='info'>========================================\n";
echo "🚀 SETUP AUTOMÁTICO - TEM DE TUDO\n";
echo "========================================\n\n";

try {
    // 1. Testar conexão
    echo "<span class='info'>📡 Testando conexão com banco...</span>\n";
    DB::connection()->getPdo();
    echo "<span class='success'>✅ Conexão OK: " . config('database.default') . "</span>\n\n";

    // 2. Rodar migrations
    echo "<span class='info'>📦 Executando migrations...</span>\n";
    Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();
    echo htmlspecialchars($output);
    echo "<span class='success'>✅ Migrations concluídas</span>\n\n";

    // 3. Rodar seeders
    echo "<span class='info'>🌱 Criando usuários e dados...</span>\n";
    Artisan::call('db:seed', ['--force' => true, '--class' => 'Database\\Seeders\\DatabaseSeeder']);
    $output = Artisan::output();
    echo htmlspecialchars($output);
    echo "<span class='success'>✅ Seeders concluídos</span>\n\n";

    // 4. Verificar usuários
    echo "<span class='info'>📊 Verificando dados criados...</span>\n";
    $totalUsers = DB::table('users')->count();
    $totalEmpresas = DB::table('empresas')->count();
    
    $admin = DB::table('users')->where('email', 'admin@temdetudo.com')->first();
    $cliente = DB::table('users')->where('email', 'cliente@teste.com')->first();
    
    echo "<span class='success'>✅ Total de usuários: {$totalUsers}</span>\n";
    echo "<span class='success'>✅ Total de empresas: {$totalEmpresas}</span>\n";
    echo "<span class='success'>✅ Admin: " . ($admin ? 'CRIADO' : 'ERRO') . "</span>\n";
    echo "<span class='success'>✅ Cliente teste: " . ($cliente ? 'CRIADO' : 'ERRO') . "</span>\n\n";

    // 5. Limpar caches
    echo "<span class='info'>🧹 Limpando caches...</span>\n";
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    echo "<span class='success'>✅ Caches limpos</span>\n\n";

    echo "========================================\n";
    echo "<span class='success'>✅✅✅ SISTEMA PRONTO! ✅✅✅</span>\n";
    echo "========================================\n";
    echo "</pre>";

    // Mostrar credenciais
    echo '</div>';
    echo '<div class="credentials">';
    echo '<h3 style="margin-bottom: 15px; color: #667eea;">🔑 Credenciais de Acesso</h3>';
    echo '<div class="credential-item"><strong>Admin:</strong> admin@temdetudo.com / admin123</div>';
    echo '<div class="credential-item"><strong>Cliente:</strong> cliente@teste.com / 123456</div>';
    echo '<div class="credential-item"><strong>Empresa:</strong> empresa@teste.com / 123456</div>';
    echo '<div class="credential-item"><strong>Clientes 1-50:</strong> cliente1@email.com até cliente50@email.com / senha123</div>';
    echo '</div>';

    echo '<a href="/entrar.html" class="btn">🔐 Ir para Login</a>';
    echo '<a href="/index.html" class="btn" style="background: rgba(255,255,255,0.1); margin-top: 10px;">🏠 Voltar ao Início</a>';

} catch (\Exception $e) {
    echo "</pre>";
    echo '<pre class="error">';
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo '</pre>';
    echo '</div>';
    echo '<a href="/setup-auto.php" class="btn" style="background: #f44336;">🔄 Tentar Novamente</a>';
}
?>
        </div>
    </div>
</body>
</html>
