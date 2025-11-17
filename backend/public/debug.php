<?php
// Debug endpoint para testar conectividade e configuração
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verificar se estamos no ambiente Laravel
    if (!function_exists('env')) {
        throw new Exception('Laravel não carregado');
    }

    // Testar conexão com banco
    $dbConnection = env('DB_CONNECTION', 'pgsql');
    $dbHost = env('DB_HOST');
    $dbDatabase = env('DB_DATABASE');

    // Tentar conectar ao banco
    $pdo = new PDO(
        "{$dbConnection}:host={$dbHost};dbname={$dbDatabase}",
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Testar query simples
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar tabelas existentes
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'OK',
        'message' => 'Sistema funcionando corretamente',
        'database' => [
            'connection' => $dbConnection,
            'host' => $dbHost,
            'database' => $dbDatabase,
            'status' => 'connected',
            'total_users' => $result['total_users'],
            'tables' => $tables
        ],
        'server' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ],
        'environment' => [
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'session_driver' => env('SESSION_DRIVER'),
            'cache_driver' => env('CACHE_DRIVER')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Erro no sistema',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 500)
    ]);
}
?>
