<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback: qualquer rota não encontrada → frontend index.html
Route::get('/{any}', function () {
    return file_get_contents(base_path('../frontend/index.html'));
})->where('any', '.*');
