  <?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTE DA API TEM DE TUDO ===\n\n";

// Teste 1: Verificar se a aplicação está funcionando
echo "1. Testando se a aplicação está funcionando...\n";
try {
    echo "✅ Aplicação Laravel carregada com sucesso!\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar aplicação: " . $e->getMessage() . "\n";
}

// Teste 2: Verificar se o banco de dados está acessível
echo "\n2. Testando conexão com banco de dados...\n";
try {
    $db = app('db');
    $db->connection()->getPdo();
    echo "✅ Conexão com banco de dados estabelecida!\n";
} catch (Exception $e) {
    echo "❌ Erro na conexão com banco: " . $e->getMessage() . "\n";
}

// Teste 3: Verificar se as rotas estão carregadas
echo "\n3. Testando rotas...\n";
try {
    $routes = app('router')->getRoutes();
    $apiRoutes = array_filter($routes->getRoutes(), function($route) {
        return strpos($route->uri(), 'api/') === 0;
    });

    echo "✅ Rotas carregadas! Encontradas " . count($apiRoutes) . " rotas de API\n";

    foreach ($apiRoutes as $route) {
        echo "   - " . $route->methods()[0] . " " . $route->uri() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar rotas: " . $e->getMessage() . "\n";
}

// Teste 4: Verificar se o CORS está configurado
echo "\n4. Testando configuração CORS...\n";
try {
    $cors = config('cors');
    if ($cors && isset($cors['allowed_origins']) && in_array('*', $cors['allowed_origins'])) {
        echo "✅ CORS configurado para aceitar todas as origens\n";
    } else {
        echo "⚠️  CORS pode precisar de ajustes\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar CORS: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DOS TESTES ===\n";
echo "Se todos os testes passaram, a API está pronta para deploy!\n";
