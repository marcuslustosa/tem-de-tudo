<?php

return [
    'debug' => env('APP_DEBUG', false),
    'env' => env('APP_ENV', 'production'),
    'name' => env('APP_NAME', 'Tem de Tudo'),
    'url' => env('APP_URL', 'https://app-tem-de-tudo.onrender.com'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'locale' => env('APP_LOCALE', 'pt_BR'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'pt_BR'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
    'providers' => [
        // Service providers...
    ],
];