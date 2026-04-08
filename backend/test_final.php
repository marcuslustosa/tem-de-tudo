<?php
// Teste com pausas para evitar SQLite lock
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

echo "=== TESTE: CADASTRO + LOGIN ===" . PHP_EOL . PHP_EOL;

// --- CENÁRIO 1: Email MAIÚSCULO, senha 6 dígitos (caso do usuário Marcus) ---
echo "--- Cenário 1: email MAIÚSCULO, senha 6 chars ---" . PHP_EOL;
$email1 = 'TESTEMARCUS_' . time() . '@GMAIL.COM';
$r = req('POST', "$base/auth/register", [
    'name' => 'Marcus Teste',
    'email' => $email1,
    'password' => '123456',
    'password_confirmation' => '123456',
    'perfil' => 'cliente',
    'terms' => true,
]);
ok('Cadastro MAIÚSCULO/123456 | HTTP 201', $r['code'] == 201, 'HTTP ' . $r['code'] . ' | ' . ($r['body']['message'] ?? json_encode($r['body']['errors'] ?? '')));
sleep(1);

$r2 = req('POST', "$base/auth/login", ['email' => strtolower($email1), 'password' => '123456']);
ok('Login minúsculo/123456 | HTTP 200', $r2['code'] == 200, 'HTTP ' . $r2['code'] . ' | ' . ($r2['body']['message'] ?? ''));
sleep(1);

$r3 = req('POST', "$base/auth/login", ['email' => $email1, 'password' => '123456']);
ok('Login MAIÚSCULO/123456 | HTTP 200', $r3['code'] == 200, 'HTTP ' . $r3['code'] . ' | ' . ($r3['body']['message'] ?? ''));
sleep(1);

// --- CENÁRIO 2: email normal minúsculo ---
echo PHP_EOL . "--- Cenário 2: email minúsculo normal ---" . PHP_EOL;
$email2 = 'testenormal_' . time() . '@teste.com';
$r4 = req('POST', "$base/auth/register", [
    'name' => 'Teste Normal',
    'email' => $email2,
    'password' => 'minhasenha',
    'password_confirmation' => 'minhasenha',
    'perfil' => 'cliente',
    'terms' => true,
]);
ok('Cadastro normal | HTTP 201', $r4['code'] == 201, 'HTTP ' . $r4['code']);
sleep(1);

$r5 = req('POST', "$base/auth/login", ['email' => $email2, 'password' => 'minhasenha']);
ok('Login normal | HTTP 200', $r5['code'] == 200, 'HTTP ' . $r5['code'] . ' | ' . ($r5['body']['message'] ?? ''));
sleep(1);

// --- CENÁRIO 3: verificar bonus pontos chegou (empresa_id nullable fix) ---
echo PHP_EOL . "--- Cenário 3: bônus de adesão (10 pontos) ---" . PHP_EOL;
$token = $r4['body']['token'] ?? null;
if ($token) {
    $r6 = req('GET', "$base/pontos/meus-dados", null, $token);
    $pontos = $r6['body']['data']['saldo'] ?? $r6['body']['saldo'] ?? $r6['body']['data']['pontos_total'] ?? null;
    ok('Pontos do novo usuário visíveis | HTTP 200', $r6['code'] == 200, 'HTTP ' . $r6['code']);
    // Bônus de adesão deve aparecer (10 pontos) – se empresa_id agora é nullable
    $db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $stmt = $db->prepare("SELECT pontos, tipo FROM pontos WHERE user_id = (SELECT id FROM users WHERE LOWER(email) = ?) ORDER BY id DESC LIMIT 5");
    $stmt->execute([strtolower($email2)]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $temBonus = false;
    foreach ($rows as $row) {
        if ($row['tipo'] === 'bonus_adesao') { $temBonus = true; break; }
    }
    ok('Bônus de adesão (10pts) creditado', $temBonus, $temBonus ? '' : 'bonus_adesao não encontrado nos pontos');
}

// --- RESULTADO ---
echo PHP_EOL . "=== RESULTADO ===" . PHP_EOL;
echo ($fail === 0 ? "🎉 " : "⚠️  ") . "$ok OK  |  $fail falhas" . PHP_EOL;
