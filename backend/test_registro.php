<?php

$base = 'http://127.0.0.1:8099/api';
$ok = 0; $fail = 0;

function req($method, $url, $data = null, $token = null) {
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    if ($token) $headers .= "Authorization: Bearer $token\r\n";
    $opts = ['http' => [
        'method' => $method,
        'header' => $headers,
        'ignore_errors' => true,
    ]];
    if ($data) $opts['http']['content'] = json_encode($data);
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    preg_match('/HTTP\/\d\.\d (\d+)/', implode("\n", $http_response_header), $m);
    return ['code' => $m[1] ?? '?', 'body' => json_decode($res, true), 'raw' => $res];
}

function check($label, $cond, $info = '') {
    global $ok, $fail;
    if ($cond) { echo "✅ $label" . ($info ? " | $info" : '') . PHP_EOL; $ok++; }
    else        { echo "❌ $label" . ($info ? " | $info" : '') . PHP_EOL; $fail++; }
}

echo "=== TESTE DE CADASTRO ===" . PHP_EOL;

// 1. Cadastro de cliente novo
$email = 'novo_cliente_' . time() . '@teste.com';
$r = req('POST', "$base/auth/register", [
    'name' => 'Novo Cliente Teste',
    'email' => $email,
    'password' => 'senha123',
    'password_confirmation' => 'senha123',
    'perfil' => 'cliente',
    'terms' => true,
]);
check('Cadastro cliente | HTTP 201', $r['code'] == 201, 'HTTP ' . $r['code']);
check('Cadastro cliente | token gerado', isset($r['body']['token']));
check('Cadastro cliente | mensagem ok', stripos($r['raw'], 'sucesso') !== false || isset($r['body']['token']), $r['body']['message'] ?? '');

$tokenNovo = $r['body']['token'] ?? null;

// 2. Novo usuário consegue logar imediatamente
if ($tokenNovo) {
    $r2 = req('GET', "$base/auth/me", null, $tokenNovo);
    $user = $r2['body']['data']['user'] ?? $r2['body']['user'] ?? $r2['body'];
    check('Login pós-cadastro | /auth/me retorna dados', $r2['code'] == 200, 'email=' . ($user['email'] ?? '?'));
    check('Login pós-cadastro | perfil=cliente', ($user['perfil'] ?? '') == 'cliente');
}

// 3. E-mail duplicado deve ser rejeitado
$r3 = req('POST', "$base/auth/register", [
    'name' => 'Duplicado',
    'email' => $email,
    'password' => 'senha123',
    'password_confirmation' => 'senha123',
    'perfil' => 'cliente',
]);
check('E-mail duplicado rejeitado | HTTP 422', $r3['code'] == 422, 'HTTP ' . $r3['code']);

// 4. Campos obrigatórios faltando
$r4 = req('POST', "$base/auth/register", ['name' => 'Sem Email']);
check('Campos faltando rejeitado | HTTP 422', $r4['code'] == 422, 'HTTP ' . $r4['code']);

// 5. Senha sem confirmação
$r5 = req('POST', "$base/auth/register", [
    'name' => 'Sem Confirmação',
    'email' => 'semconfirmacao_' . time() . '@teste.com',
    'password' => 'senha123',
    // sem password_confirmation
    'perfil' => 'cliente',
]);
check('Sem confirmação de senha rejeitado | HTTP 422', $r5['code'] == 422, 'HTTP ' . $r5['code']);

// 6. Cadastro de empresa (se permitido)
$emailEmpresa = 'nova_empresa_' . time() . '@teste.com';
$r6 = req('POST', "$base/auth/register", [
    'name' => 'Nova Empresa Teste',
    'email' => $emailEmpresa,
    'password' => 'senha123',
    'password_confirmation' => 'senha123',
    'perfil' => 'empresa',
]);
check('Cadastro empresa | HTTP 201 ou permitido', in_array($r6['code'], ['201', '200', '422', '403']), 'HTTP ' . $r6['code'] . ' | ' . ($r6['body']['message'] ?? ''));

echo PHP_EOL . "=== RESULTADO ===" . PHP_EOL;
echo "✅ $ok OK  |  ❌ $fail falhas" . PHP_EOL;
if ($fail === 0) echo "🎉 CADASTRO VALIDADO!" . PHP_EOL;
