<?php

/*
 * Contas de demonstracao usadas pelo I9PlusDemoSeeder e pelo comando de boot
 * app:ensure-demo-access.
 *
 * Ficam em config, e nao espalhadas em env() pelo codigo, por dois motivos:
 *
 * 1. env() so e confiavel dentro de arquivos de config. Se algum dia o deploy
 *    passar a rodar `php artisan config:cache`, todo env() fora daqui devolve
 *    null e o app volta a semear a conta errada sem avisar ninguem.
 * 2. O endpoint de provisionamento sobrescreve estes valores em tempo de
 *    execucao, o que so funciona com config().
 *
 * Os defaults sao os de desenvolvimento local. Em producao, definir as
 * variaveis correspondentes no ambiente do servico.
 */

return [
    'admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@demo.local'),
    'admin_nome' => env('DEMO_ADMIN_NAME', 'Admin Demo'),
    'admin_senha' => env('DEMO_ADMIN_PASSWORD', 'password'),

    'empresa_email' => env('DEMO_EMPRESA_EMAIL', 'malagueta@demo.local'),
    'empresa_nome' => env('DEMO_EMPRESA_NAME', 'Camila Malagueta'),
    'empresa_senha' => env('DEMO_EMPRESA_PASSWORD', 'password'),

    'cliente_email' => env('DEMO_CLIENTE_EMAIL', 'joao@demo.local'),
    'cliente_nome' => env('DEMO_CLIENTE_NAME', 'João Cliente Demo'),
    'cliente_senha' => env('DEMO_CLIENTE_PASSWORD', 'password'),

    'acesso_habilitado' => env('DEMO_ACCESS_ENABLED', true),
    'endpoint_provisionamento' => env('DEMO_PROVISION_ENDPOINT', true),

    'empresa_razao' => env('DEMO_EMPRESA_RAZAO', 'Malagueta Galpao'),
    'empresa_cnpj' => env('DEMO_EMPRESA_CNPJ', '11.111.111/0001-11'),
];
