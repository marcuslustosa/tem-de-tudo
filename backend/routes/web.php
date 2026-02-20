<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }
    return response()->json(['error' => 'Index page not found'], 404);
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});

Route::get('/api/health', function () {
    return response()->json(['status' => 'healthy']);
});

// Rota dinâmica para TODAS as páginas HTML
// Deve vir DEPOIS das rotas específicas para não conflitar
Route::get('/{page}', function ($page) {
    // Remove extensão .html se vier na URL
    $page = str_replace('.html', '', $page);
    
    $filePath = public_path("{$page}.html");
    
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    
    // Se não encontrar, retorna 404
    return abort(404, "Página {$page}.html não encontrada");
})->where('page', '[a-zA-Z0-9_][a-zA-Z0-9_-]*');

Route::get('/debug', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'app_key' => env('APP_KEY') ? 'SET' : 'NOT SET',
        'db_connection' => env('DB_CONNECTION'),
        'db_host' => env('DB_HOST'),
        'storage_writable' => is_writable(storage_path()),
        'bootstrap_writable' => is_writable(base_path('bootstrap/cache')),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'errors' => session()->get('errors'),
    ]);
});
