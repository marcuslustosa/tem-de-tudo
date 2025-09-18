<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpresaController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('user', [AuthController::class, 'user']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('empresas', [EmpresaController::class, 'index']);
    Route::get('empresas/{id}', [EmpresaController::class, 'show']);
});
