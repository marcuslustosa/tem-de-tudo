<?php

use Illuminate\Support\Facades\Route;

// PÁGINA PRINCIPAL - index.html
Route::get('/', function () {
    try {
        return file_get_contents(public_path('index.html'));
    } catch (Exception $e) {
        return response('Sistema em manutenção', 500);
    }
});

// Health check para API
Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'time' => date('Y-m-d H:i:s')]);
});

Route::get('/api/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Sistema Tem de Tudo funcionando!',
        'time' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// Rotas para páginas HTML
Route::get('/admin.html', function () {
    try {
        return file_get_contents(public_path('admin.html'));
    } catch (Exception $e) {
        return response('Página não encontrada', 404);
    }
});

Route::get('/login.html', function () {
    try {
        return file_get_contents(public_path('login.html'));
    } catch (Exception $e) {
        return response('Página não encontrada', 404);
    }
});

Route::get('/register.html', function () {
    try {
        return file_get_contents(public_path('register.html'));
    } catch (Exception $e) {
        return response('Página não encontrada', 404);
    }
});

// Fallback para index
Route::get('/{any}', function () {
    try {
        return file_get_contents(public_path('index.html'));
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Sistema em manutenção',
            'time' => date('Y-m-d H:i:s')
        ], 500);
    }
})->where('any', '.*');
