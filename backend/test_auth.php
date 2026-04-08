<?php
$base = 'http://127.0.0.1:8099/api';

function postJson($url, $data) {
    $ch = curl_init($url);
    $payload = json_encode($data);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 8,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($out, true)];
}

function getJson($url, $token = null) {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 8,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($out, true)];
}

$ok = 0; $fail = 0;
function check($label, $condition, $detail = '') {
    global $ok, $fail;
    if ($condition) { echo "✅ $label" . ($detail ? " | $detail" : '') . "\n"; $ok++; }
    else            { echo "❌ $label" . ($detail ? " | $detail" : '') . "\n"; $fail++; }
}

echo "=== TESTE COMPLETO TEM DE TUDO ===\n\n";

// 1. Health
[$code, $data] = getJson("$base/health");
check("Servidor online", $code === 200, "status=" . ($data['status'] ?? '?'));

// 2. Login Admin
[$code, $data] = postJson("$base/auth/login", ['email' => 'admin@temdetudo.com', 'password' => 'senha123']);
$adminToken = $data['token'] ?? null;
check("Login ADMIN", $code === 200 && $adminToken, "perfil=" . ($data['user']['perfil'] ?? '?') . " redirect=" . ($data['redirect_to'] ?? '?'));

// 3. Login Cliente
[$code, $data] = postJson("$base/auth/login", ['email' => 'cliente@teste.com', 'password' => 'senha123']);
$clienteToken = $data['token'] ?? null;
check("Login CLIENTE", $code === 200 && $clienteToken, "perfil=" . ($data['user']['perfil'] ?? '?') . " redirect=" . ($data['redirect_to'] ?? '?'));

// 4. Login Empresa
[$code, $data] = postJson("$base/auth/login", ['email' => 'empresa@teste.com', 'password' => 'senha123']);
$empresaToken = $data['token'] ?? null;
check("Login EMPRESA", $code === 200 && $empresaToken, "perfil=" . ($data['user']['perfil'] ?? '?') . " redirect=" . ($data['redirect_to'] ?? '?'));

// 5. Credenciais erradas retornam 401
[$code, $data] = postJson("$base/auth/login", ['email' => 'admin@temdetudo.com', 'password' => 'senhaerrada']);
check("Senha errada -> 401", $code === 401, "msg=" . ($data['message'] ?? '?'));

// 6. Registro de novo cliente
$ts = time();
[$code, $data] = postJson("$base/auth/register", [
    'perfil' => 'cliente', 'name' => "Teste $ts",
    'email' => "teste$ts@mail.com", 'password' => 'senha123',
    'password_confirmation' => 'senha123', 'terms' => true,
]);
check("Registro CLIENTE", $code === 201, ($data['message'] ?? '?'));

// 7. /auth/me com token válido
[$code, $data] = getJson("$base/auth/me", $clienteToken);
check("/auth/me (cliente)", $code === 200, "perfil=" . ($data['data']['perfil'] ?? $data['user']['perfil'] ?? '?'));

// 8. /auth/me sem token -> 401
[$code, $data] = getJson("$base/auth/me");
check("/auth/me sem token -> 401", $code === 401);

// 9. Empresas (endpoint público)
[$code, $data] = getJson("$base/empresas");
$nEmpresas = count($data['data'] ?? $data ?? []);
check("GET /empresas (público)", $code === 200, "$nEmpresas empresas");

// 10. Dashboard cliente
[$code, $data] = getJson("$base/cliente/dashboard", $clienteToken);
check("GET /cliente/dashboard", $code === 200 || $code === 404, "HTTP $code");

// 11. Pontos do cliente
[$code, $data] = getJson("$base/pontos/meus-dados", $clienteToken);
check("GET /pontos/meus-dados", $code === 200 || $code === 404, "HTTP $code");

// 12. Dashboard admin
[$code, $data] = getJson("$base/admin/dashboard-stats", $adminToken);
check("GET /admin/dashboard-stats", $code === 200 || $code === 404, "HTTP $code");

// 13. Admin só pode acessar rotas admin (empresa não pode)
[$code, $data] = getJson("$base/admin/dashboard-stats", $empresaToken);
check("Empresa sem acesso admin -> !200", $code !== 200, "HTTP $code");

// 14. Logout (rota correta é /logout, autenticada)
function postJsonAuth($url, $data, $token) {
    $ch = curl_init($url);
    $payload = json_encode($data);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', "Authorization: Bearer $token"],
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 8,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($out, true)];
}
[$code, $data] = postJsonAuth("$base/logout", [], $clienteToken);
check("POST /logout (autenticado)", $code === 200, ($data['message'] ?? "HTTP $code"));

echo "\n=== RESULTADO: $ok OK, $fail FALHAS ===\n";
