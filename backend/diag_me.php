<?php
// Testa a estrutura de resposta do /me endpoint
$base = 'http://127.0.0.1:8099/api';

function req($url, $method = 'GET', $body = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($resp, true), $resp];
}

// Login
[$code, $data] = req("$base/auth/login", 'POST', json_encode([
    'email' => 'cliente@teste.com', 'password' => 'senha123'
]));
$token = $data['token'] ?? $data['data']['token'] ?? null;
echo "Token: " . substr($token ?? 'NENHUM', 0, 30) . "\n\n";

// /me
[$code, $data, $raw] = req("$base/me", 'GET', null, $token);
echo "=== /me raw ===\n";
echo $raw . "\n\n";

// /auth/me
[$code, $data, $raw] = req("$base/auth/me", 'GET', null, $token);
echo "=== /auth/me raw ===\n";
echo $raw . "\n\n";

// /pontos/historico completo
[$code, $data, $raw] = req("$base/pontos/historico", 'GET', null, $token);
echo "=== /pontos/historico ===\n";
echo json_encode(json_decode($raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// /cliente/dashboard estrutura
[$code, $data, $raw] = req("$base/cliente/dashboard", 'GET', null, $token);
echo "\n=== /cliente/dashboard ===\n";
echo json_encode(json_decode($raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
