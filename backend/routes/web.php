<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Serve index.html if it exists, otherwise use blade template
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }
    
    try {
        return view('welcome');
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load welcome view',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});

Route::get('/api/health', function () {
    return response()->json(['status' => 'healthy']);
});

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
