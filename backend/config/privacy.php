<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versao de Consentimento
    |--------------------------------------------------------------------------
    |
    | Versao atual dos termos/politica para auditoria de consentimento.
    |
    */
    'default_consent_version' => env('PRIVACY_CONSENT_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Exportacao de Dados (LGPD)
    |--------------------------------------------------------------------------
    |
    | Disco e limite maximo de registros por dataset no pacote exportado.
    |
    */
    'export_disk' => env('PRIVACY_EXPORT_DISK', env('FILESYSTEM_DISK', 'local')),
    'export_row_limit' => (int) env('PRIVACY_EXPORT_ROW_LIMIT', 5000),
];

