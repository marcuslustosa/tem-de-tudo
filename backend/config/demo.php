<?php

/*
 * Conta demo usada pelo I9PlusDemoSeeder.
 *
 * Fica em config (e nao so em env()) porque em producao o env() nao chega ao
 * app — o seeder acabava semeando joao@demo.local mesmo com DEMO_CLIENTE_EMAIL
 * definido na Railway. Passando por config, o valor pode ser sobrescrito em
 * tempo de execucao (ex.: pelo endpoint de provisionamento), sem depender do
 * ambiente nem de config:cache.
 */

return [
    'cliente_email' => env('DEMO_CLIENTE_EMAIL', 'joao@demo.local'),
    'cliente_nome' => env('DEMO_CLIENTE_NAME', 'João Cliente Demo'),
];
