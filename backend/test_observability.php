<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTE DE OBSERVABILIDADE ===\n\n";

// Teste 1: Health Check
echo "📊 Testando HealthController...\n";
$controller = new App\Http\Controllers\HealthController();

try {
    $ping = $controller->ping();
    echo "✅ PING: " . $ping->getContent() . "\n";
} catch (Exception $e) {
    echo "❌ PING FALHOU: " . $e->getMessage() . "\n";
}

try {
    $health = $controller->health();
    echo "✅ HEALTH: " . json_encode(json_decode($health->getContent()), JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "❌ HEALTH FALHOU: " . $e->getMessage() . "\n";
}

try {
    $metrics = $controller->metrics();
    echo "✅ METRICS: " . json_encode(json_decode($metrics->getContent()), JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "❌ METRICS FALHOU: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE DE MIDDLEWARE ===\n\n";

// Teste 2: RequestLogger
$middleware = new \App\Http\Middleware\RequestLogger();
echo "✅ RequestLogger criado com sucesso\n";

echo "\n=== TESTE DE MONITORAMENTO ===\n\n";

// Teste 3: MonitorSystem command
Artisan::call('monitor:system');
echo Artisan::output();

echo "\n=== TESTE CONCLUÍDO ===\n";
