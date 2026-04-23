<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Compatibility Mode
    |--------------------------------------------------------------------------
    |
    | When true, empresa routes protected by subscription middleware will still
    | operate if the company does not yet have a subscription record.
    | Set to false to enforce strict subscription checks for all companies.
    |
    */
    'allow_without_subscription' => env('BILLING_ALLOW_WITHOUT_SUBSCRIPTION', true),

    /*
    |--------------------------------------------------------------------------
    | Billing Retry / Dunning
    |--------------------------------------------------------------------------
    */
    'payment_retry' => [
        'enabled' => (bool) env('BILLING_RETRY_ENABLED', true),
        'max_attempts' => (int) env('BILLING_RETRY_MAX_ATTEMPTS', 3),
        'backoff_days' => env('BILLING_RETRY_BACKOFF_DAYS', '1,3,5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Reconciliation
    |--------------------------------------------------------------------------
    */
    'reconciliation' => [
        'enabled' => (bool) env('BILLING_RECONCILIATION_ENABLED', true),
        'lookback_days' => (int) env('BILLING_RECONCILIATION_LOOKBACK_DAYS', 30),
    ],
];
