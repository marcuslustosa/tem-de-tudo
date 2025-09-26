<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    */
    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'security_headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'login' => [
            'attempts' => env('LOGIN_RATE_LIMIT_ATTEMPTS', 5),
            'minutes' => env('LOGIN_RATE_LIMIT_MINUTES', 1),
            'lockout_minutes' => env('LOGIN_LOCKOUT_MINUTES', 15),
        ],
        'api' => [
            'attempts' => env('API_RATE_LIMIT', 60),
            'minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */
    'password_policy' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session_security' => [
        'regenerate_on_login' => true,
        'timeout_minutes' => env('SESSION_TIMEOUT', 120),
        'concurrent_sessions' => env('ALLOW_CONCURRENT_SESSIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_failed_logins' => true,
        'log_successful_logins' => true,
        'log_user_actions' => true,
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    ],
];