<?php
// Arquivo de diagnóstico para erros em produção

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Informações básicas
echo "=== Diagnóstico do Sistema ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Laravel Version: " . app()->version() . "\n";

// Variáveis de ambiente
echo "\n=== Variáveis de Ambiente ===\n";
echo "APP_ENV: " . env('APP_ENV') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "APP_URL: " . env('APP_URL') . "\n";

// Conexão com banco
echo "\n=== Conexão com Banco ===\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "✓ Conexão DB OK\n";
    echo "Driver: " . DB::connection()->getDriverName() . "\n";
    echo "Database: " . DB::connection()->getDatabaseName() . "\n";
    
    // Listar tabelas
    echo "\nTabelas encontradas:\n";
    $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
    foreach ($tables as $table) {
        echo "- " . $table->tablename . "\n";
    }
} catch (Exception $e) {
    echo "✗ ERRO DB: " . $e->getMessage() . "\n";
}

// Permissões
echo "\n=== Permissões ===\n";
$paths = ['storage', 'storage/logs', 'storage/framework', 'bootstrap/cache'];
foreach ($paths as $path) {
    echo "$path: " . 
         (is_readable(base_path($path)) ? 'R' : '-') .
         (is_writable(base_path($path)) ? 'W' : '-') .
         (is_executable(base_path($path)) ? 'X' : '-') . "\n";
}

// Última Mensagem de Erro
echo "\n=== Último Erro PHP ===\n";
$error = error_get_last();
if ($error) {
    echo "Tipo: " . $error['type'] . "\n";
    echo "Mensagem: " . $error['message'] . "\n";
    echo "Arquivo: " . $error['file'] . "\n";
    echo "Linha: " . $error['line'] . "\n";
} else {
    echo "Nenhum erro registrado\n";
}

// Laravel Logs (últimas 5 linhas)
echo "\n=== Últimas Linhas do Log ===\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $log = file_get_contents($logPath);
    $lines = array_slice(explode("\n", $log), -5);
    echo implode("\n", $lines) . "\n";
} else {
    echo "Log não encontrado\n";
}