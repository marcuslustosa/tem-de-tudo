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

// Named login route to avoid middleware warnings.
Route::get('/login', function () {
    return redirect('/entrar.html');
})->name('login');

if (app()->environment(['local', 'testing'])) {
    Route::get('/debug', function () {
        return response()->json([
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_writable' => is_writable(base_path('bootstrap/cache')),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    });
}

// Dynamic route for all HTML pages. Keep this after specific routes.
Route::get('/{page}', function ($page) {
    $page = str_replace('.html', '', $page);
    $filePath = public_path("{$page}.html");

    if (file_exists($filePath)) {
        return response()->file($filePath);
    }

    return abort(404, "Pagina {$page}.html nao encontrada");
})->where('page', '[a-zA-Z0-9_][a-zA-Z0-9_-]*');
