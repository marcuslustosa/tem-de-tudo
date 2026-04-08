<?php
$base = 'http://127.0.0.1:8099/api';
$ok = 0; $fail = 0;

function req($method, $url, $data = null, $token = null) {
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    if ($token) $headers .= "Authorization: Bearer $token\r\n";
    $opts = ['http' => ['method' => $method, 'header' => $headers, 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = json_encode($data);
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    preg_match('/HTTP\/\d\.\d (\d+)/', implode("\n", $http_response_header), $m);
    return ['code' => $m[1] ?? '?', 'body' => json_decode($res, true)];
}

function ok($label, $cond, $info = '') { global $ok, $fail;
    if ($cond) { echo "✅ $label" . ($info ? " | $info" : '') . PHP_EOL; $ok++; }
    else        { echo "❌ $label" . ($info ? " | $info" : '') . PHP_EOL; $fail++; }
}

// =====================================================
// 1. CADASTRO + LOGIN
// =====================================================
echo "=== 1. CADASTRO + LOGIN ===" . PHP_EOL;

$email = 'fluxo_teste_' . time() . '@teste.com';
$senha = 'senhaFluxo123';

$r = req('POST', "$base/auth/register", [
    'name' => 'Fluxo Teste', 'email' => $email,
    'password' => $senha, 'password_confirmation' => $senha,
    'perfil' => 'cliente', 'terms' => true,
]);
ok('Cadastro | HTTP 201', $r['code'] == 201, 'HTTP ' . $r['code']);

$rLogin = req('POST', "$base/auth/login", ['email' => $email, 'password' => $senha]);
ok('Login pós-cadastro | HTTP 200', $rLogin['code'] == 200, 'HTTP ' . $rLogin['code']);
$token = $rLogin['body']['token'] ?? null;
ok('Token gerado', !empty($token));

// =====================================================
// 2. TROCA DE SENHA (change-password)
// =====================================================
echo PHP_EOL . "=== 2. TROCA DE SENHA ===" . PHP_EOL;

$novaSenha = 'novaSenha456';
$rChange = req('POST', "$base/auth/change-password", [
    'current_password' => $senha,
    'password' => $novaSenha,
    'password_confirmation' => $novaSenha,
], $token);
ok('Troca de senha | HTTP 200', $rChange['code'] == 200, ($rChange['body']['message'] ?? 'HTTP ' . $rChange['code']));

// Login com a nova senha
$rLogin2 = req('POST', "$base/auth/login", ['email' => $email, 'password' => $novaSenha]);
ok('Login com nova senha | HTTP 200', $rLogin2['code'] == 200, 'HTTP ' . $rLogin2['code']);

// Login com a senha antiga deve falhar
$rLogin3 = req('POST', "$base/auth/login", ['email' => $email, 'password' => $senha]);
ok('Senha antiga rejeitada | HTTP 401', $rLogin3['code'] == 401, 'HTTP ' . $rLogin3['code']);

// =====================================================
// 3. ADMIN CRIA USUÁRIO (create-user)
// =====================================================
echo PHP_EOL . "=== 3. ADMIN CRIA USUÁRIO ===" . PHP_EOL;

$rAdmin = req('POST', "$base/auth/login", ['email' => 'admin@temdetudo.com', 'password' => 'senha123']);
$adminToken = $rAdmin['body']['token'] ?? null;
ok('Admin login', !empty($adminToken));

$emailAdmin = 'criado_pelo_admin_' . time() . '@teste.com';
$rCreate = req('POST', "$base/admin/create-user", [
    'name' => 'Criado pelo Admin',
    'email' => $emailAdmin,
    'password' => 'senha123',
    'perfil' => 'cliente',
], $adminToken);
ok('Admin cria usuário | HTTP 201', $rCreate['code'] == 201, ($rCreate['body']['message'] ?? 'HTTP ' . $rCreate['code']));

// Login com o usuário criado pelo admin
$rLoginNew = req('POST', "$base/auth/login", ['email' => $emailAdmin, 'password' => 'senha123']);
ok('Login usuário criado por admin | HTTP 200', $rLoginNew['code'] == 200, 'HTTP ' . $rLoginNew['code']);

// =====================================================
// RESULTADO
// =====================================================
echo PHP_EOL . "=== RESULTADO ===" . PHP_EOL;
echo ($fail === 0 ? "🎉 " : "⚠️  ") . "$ok OK  |  $fail falhas" . PHP_EOL;
