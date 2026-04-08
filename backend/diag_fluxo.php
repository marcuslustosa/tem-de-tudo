<?php
// Simula EXATAMENTE o que o formulário do usuário enviaria
$base = 'http://127.0.0.1:8099/api';

function req($method, $url, $data = null, $token = null) {
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    if ($token) $headers .= "Authorization: Bearer $token\r\n";
    $opts = ['http' => ['method' => $method, 'header' => $headers, 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = json_encode($data);
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    preg_match('/HTTP\/\d\.\d (\d+)/', implode("\n", $http_response_header), $m);
    return ['code' => $m[1] ?? '?', 'body' => json_decode($res, true), 'raw' => $res];
}

$email = 'MARCUSLUSTOSA90@GMAIL.COM';
$senha = '123456';

echo "=== SIMULANDO O CADASTRO DO USUÁRIO ===\n";
echo "Email: $email\n";
echo "Senha: $senha\n\n";

// 1. CADASTRO - exatamente o que o frontend envia
$r1 = req('POST', "$base/auth/register", [
    'name'                  => 'Marcus Lustosa',
    'email'                 => $email,
    'password'              => $senha,
    'password_confirmation' => $senha,
    'perfil'                => 'cliente',
    'terms'                 => true,
    'telefone'              => '',
]);

echo "CADASTRO: HTTP " . $r1['code'] . "\n";
echo "Resposta: " . ($r1['body']['message'] ?? $r1['raw']) . "\n";
if (isset($r1['body']['errors'])) {
    echo "Erros: " . json_encode($r1['body']['errors'], JSON_UNESCAPED_UNICODE) . "\n";
}

if ($r1['code'] != 201) {
    echo "\n❌ CADASTRO FALHOU. Parando.\n";
    exit;
}

echo "\n✅ CADASTRO OK!\n\n";
echo "=== TESTANDO LOGIN COM VÁRIAS COMBINAÇÕES ===\n";

// 2. Login com email MAIÚSCULO
$r2 = req('POST', "$base/auth/login", ['email' => $email, 'password' => $senha]);
echo "Login (email MAIÚSCULO): HTTP " . $r2['code'] . " | " . ($r2['body']['message'] ?? '') . "\n";

// 3. Login com email minúsculo
$r3 = req('POST', "$base/auth/login", ['email' => strtolower($email), 'password' => $senha]);
echo "Login (email minúsculo): HTTP " . $r3['code'] . " | " . ($r3['body']['message'] ?? '') . "\n";

// 4. Verificar como ficou salvo no banco
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$stmt = $db->prepare("SELECT id, email, perfil, status, substr(password,1,30) as pass_trunc FROM users WHERE LOWER(email) = LOWER(?)");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n=== DADOS NO BANCO ===\n";
if ($user) {
    echo "ID: " . $user['id'] . "\n";
    echo "Email salvo: " . $user['email'] . "\n";
    echo "Perfil: " . $user['perfil'] . "\n";
    echo "Status: " . $user['status'] . "\n";
    echo "Hash: " . $user['pass_trunc'] . "...\n";
} else {
    echo "❌ Usuário NÃO encontrado no banco!\n";
}
