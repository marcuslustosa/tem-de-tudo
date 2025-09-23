<?php

use Illuminate\Support\Facades\Route;

// Rota fallback: qualquer rota serve o index.html do frontend
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
