<?php
// Script de diagnóstico - Verificar configuração do ambiente

header('Content-Type: application/json');

$diagnostics = [
    'timestamp' => date('Y-m-d H:M:S'),
    'php_version' => phpversion(),
    'current_dir' => getcwd(),
    'root_dir' => dirname(__DIR__),
];

// Verificar se .env existe
$envPath = dirname(__DIR__) . '/.env';
$diagnostics['env_file_exists'] = file_exists($envPath);
$diagnostics['env_file_readable'] = is_readable($envPath);

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $diagnostics['env_file_size'] = strlen($envContent);
    
    // Procurar APP_KEY
    if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches)) {
        $key = trim($matches[1]);
        $diagnostics['app_key_exists'] = true;
        $diagnostics['app_key_length'] = strlen($key);
        $diagnostics['app_key_starts_with'] = substr($key, 0, 10) . '...';
        $diagnostics['app_key_is_base64'] = strpos($key, 'base64:') === 0;
    } else {
        $diagnostics['app_key_exists'] = false;
    }
    
    // Verificar outras configs importantes
    $diagnostics['env_contains_app_name'] = strpos($envContent, 'APP_NAME') !== false;
    $diagnostics['env_contains_app_url'] = strpos($envContent, 'APP_URL') !== false;
    $diagnostics['env_contains_db'] = strpos($envContent, 'DB_CONNECTION') !== false;
}

// Verificar variáveis de ambiente do sistema
$diagnostics['env_vars'] = [
    'APP_KEY' => getenv('APP_KEY') ? substr(getenv('APP_KEY'), 0, 20) . '...' : 'NOT SET',
    'APP_ENV' => getenv('APP_ENV') ?: 'NOT SET',
    'APP_DEBUG' => getenv('APP_DEBUG') ?: 'NOT SET',
    'APP_URL' => getenv('APP_URL') ?: 'NOT SET',
];

// Verificar permissões
$diagnostics['permissions'] = [
    'env_file' => $diagnostics['env_file_exists'] ? substr(sprintf('%o', fileperms($envPath)), -4) : 'N/A',
    'storage_writable' => is_writable(dirname(__DIR__) . '/storage'),
    'bootstrap_cache_writable' => is_writable(dirname(__DIR__) . '/bootstrap/cache'),
];

// Listar arquivos no root
$diagnostics['root_files'] = array_slice(scandir(dirname(__DIR__)), 0, 20);

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
