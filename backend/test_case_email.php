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

echo "=== TESTE CASE-INSENSITIVE DE EMAIL ===" . PHP_EOL;

// 1. Cadastro com email EM MAIÚSCULO (como o usuário digitou)
$emailMaiu = 'TESTE_CASE_' . time() . '@GMAIL.COM';
$senha = '123456';

$r = req('POST', "$base/auth/register", [
    'name' => 'Teste Case',
    'email' => $emailMaiu,
    'password' => $senha,
    'password_confirmation' => $senha,
    'perfil' => 'cliente',
    'terms' => true,
]);
ok('Cadastro com email MAIÚSCULO | HTTP 201', $r['code'] == 201, 'HTTP ' . $r['code']);

// Verifica como foi salvo
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$stmt = $db->prepare("SELECT email FROM users WHERE LOWER(email) = LOWER(?)");
$stmt->execute([$emailMaiu]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
$emailSalvo = $u['email'] ?? '?';
ok('Email salvo em minúsculo no banco', $emailSalvo === strtolower($emailMaiu), "salvo como: $emailSalvo");

// 2. Login com email em MINÚSCULO
$r2 = req('POST', "$base/auth/login", ['email' => strtolower($emailMaiu), 'password' => $senha]);
ok('Login com email minúsculo | HTTP 200', $r2['code'] == 200, $r2['body']['message'] ?? 'HTTP ' . $r2['code']);

// 3. Login com email MAIÚSCULO
$r3 = req('POST', "$base/auth/login", ['email' => $emailMaiu, 'password' => $senha]);
ok('Login com email MAIÚSCULO | HTTP 200', $r3['code'] == 200, $r3['body']['message'] ?? 'HTTP ' . $r3['code']);

// 4. Login com email misto (Caso do Gmail)
$emailMisto = 'Teste_Case_' . time() . '@Gmail.Com';
$r4 = req('POST', "$base/auth/register", [
    'name' => 'Teste Misto',
    'email' => $emailMisto,
    'password' => $senha,
    'password_confirmation' => $senha,
    'perfil' => 'cliente',
    'terms' => true,
]);
ok('Cadastro email misto | HTTP 201', $r4['code'] == 201);

$r5 = req('POST', "$base/auth/login", ['email' => strtolower($emailMisto), 'password' => $senha]);
ok('Login email misto → minúsculo | HTTP 200', $r5['code'] == 200, $r5['body']['message'] ?? 'HTTP ' . $r5['code']);

// 5. Senha de 6 dígitos (caso do usuário: 123456)
$emailNum = 'teste_senha6_' . time() . '@teste.com';
$r6 = req('POST', "$base/auth/register", [
    'name' => 'Senha Seis',
    'email' => $emailNum,
    'password' => '123456',
    'password_confirmation' => '123456',
    'perfil' => 'cliente',
    'terms' => true,
]);
ok('Cadastro senha 6 dígitos | HTTP 201', $r6['code'] == 201, 'HTTP ' . $r6['code']);

$r7 = req('POST', "$base/auth/login", ['email' => $emailNum, 'password' => '123456']);
ok('Login senha 6 dígitos | HTTP 200', $r7['code'] == 200, $r7['body']['message'] ?? 'HTTP ' . $r7['code']);

echo PHP_EOL . "=== RESULTADO ===" . PHP_EOL;
echo ($fail === 0 ? "🎉 " : "⚠️  ") . "$ok OK  |  $fail falhas" . PHP_EOL;
