<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

// Health check and API status routes
Route::get('/api/status', [HomeController::class, 'index']);
Route::get('/health', [HomeController::class, 'health']);

// Rotas específicas para páginas HTML
Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

Route::get('/login.html', function () {
    return file_get_contents(public_path('login.html'));
});

Route::get('/register.html', function () {
    return file_get_contents(public_path('register.html'));
});

Route::get('/profile-client.html', function () {
    return file_get_contents(public_path('profile-client.html'));
});

// Rota fallback para outras rotas não encontradas
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
