<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// Rota fallback: qualquer rota não encontrada serve o index.html do frontend
Route::fallback(function () {
    $indexPath = public_path('index.html'); // assume que index.html está em public/

    if (File::exists($indexPath)) {
        return Response::file($indexPath); // retorna com o tipo correto
    }

    return abort(404);
});
