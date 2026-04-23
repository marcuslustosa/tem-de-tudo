<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Requisicao de Chave de Idempotencia
    |--------------------------------------------------------------------------
    |
    | Em producao, o header pode ser exigido para endpoints mutaveis.
    | Em ambientes nao produtivos, o bypass continua permitido por padrao.
    |
    */
    'enforce_header' => (bool) env('IDEMPOTENCY_ENFORCE_HEADER', true),
    'require_in_production' => (bool) env('IDEMPOTENCY_REQUIRE_IN_PRODUCTION', true),
    'allow_bypass_non_production' => (bool) env('IDEMPOTENCY_ALLOW_BYPASS_NON_PRODUCTION', true),
    'default_ttl' => (int) env('IDEMPOTENCY_DEFAULT_TTL', 3600),
];

