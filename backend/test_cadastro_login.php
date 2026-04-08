<?php
$base = 'http://127.0.0.1:8099/api';

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

$email = 'usuario_teste_' . time() . '@teste.com';
$senha = 'minhaSenha123';

echo "=== TESTE CADASTRO + LOGIN ===" . PHP_EOL;
echo "Email: $email" . PHP_EOL;
echo "Senha: $senha" . PHP_EOL . PHP_EOL;

// 1. Cadastrar
$r1 = req('POST', "$base/auth/register", [
    'name' => 'Usuario Teste',
    'email' => $email,
    'password' => $senha,
    'password_confirmation' => $senha,
    'perfil' => 'cliente',
    'terms' => true,
]);
echo "CADASTRO >> HTTP " . $r1['code'] . " | " . ($r1['body']['message'] ?? json_encode($r1['body'])) . PHP_EOL;

if ($r1['code'] != 201) {
    echo "❌ Cadastro falhou. Erro: " . json_encode($r1['body']['errors'] ?? $r1['body']) . PHP_EOL;
    exit(1);
}
echo "✅ Cadastro OK" . PHP_EOL . PHP_EOL;

// 2. Login com as mesmas credenciais
$r2 = req('POST', "$base/auth/login", ['email' => $email, 'password' => $senha]);
echo "LOGIN >> HTTP " . $r2['code'] . " | " . ($r2['body']['message'] ?? json_encode($r2['body'])) . PHP_EOL;

if ($r2['code'] == 200 && isset($r2['body']['token'])) {
    echo "✅ Login funcionou! Token gerado." . PHP_EOL;
    echo "Perfil: " . ($r2['body']['user']['perfil'] ?? '?') . PHP_EOL;
    echo "Redirect: " . ($r2['body']['redirect_to'] ?? '?') . PHP_EOL;
} else {
    echo "❌ LOGIN FALHOU: " . ($r2['body']['message'] ?? json_encode($r2['body'])) . PHP_EOL;
}
