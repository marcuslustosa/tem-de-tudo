<?php

use Illuminate\Support\Facades\Route;

// Rotas API
Route::prefix('api')->group(function () {
    require __DIR__.'/api.php';
});

// Rotas frontend
Route::get('/{any}', function () {
    return file_get_contents(base_path('../frontend/index.html'));
})->where('any', '.*');
