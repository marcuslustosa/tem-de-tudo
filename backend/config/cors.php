<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_ENV') === 'production' 
        ? [
            'https://vipus.com.br',
            'https://www.vipus.com.br',
            'https://app.vipus.com.br',
            'https://tem-de-tudo.onrender.com',
        ]
        : ['*'], // Dev: permite tudo

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-Subscription-Warning',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];