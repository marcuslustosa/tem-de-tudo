<?php
// Teste detalhado para identificar o erro exato
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

// Login como cliente
[$code, $data] = req("$base/auth/login", 'POST', json_encode([
    'email' => 'cliente@teste.com',
    'password' => 'senha123'
]));
$token = $data['token'] ?? $data['data']['token'] ?? null;
echo "Token: " . substr($token ?? 'NENHUM', 0, 30) . "\n";

// Debug /cliente/dashboard completo
echo "\n=== /cliente/dashboard COMPLETO ===\n";
[$code, $data, $raw] = req("$base/cliente/dashboard", 'GET', null, $token);
echo "HTTP: $code\n";
echo json_encode(json_decode($raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Debug /cliente/empresas
echo "\n=== /cliente/empresas ===\n";
[$code, $data, $raw] = req("$base/cliente/empresas", 'GET', null, $token);
echo "HTTP: $code\n";
if ($code !== 200) echo json_encode(json_decode($raw), JSON_PRETTY_PRINT) . "\n";
else {
    $empresas = $data['data'] ?? [];
    echo "Total: " . count($empresas) . "\n";
    if (count($empresas) > 0) echo "Primeiro: " . json_encode($empresas[0]) . "\n";
}

// Verificar DB diretamente
echo "\n=== VERIFICAR DB DIRETAMENTE ===\n";
$dbPath = __DIR__ . '/database/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tipos de pontos existentes
$tipos = $pdo->query("SELECT tipo, COUNT(*) as cnt FROM pontos GROUP BY tipo")->fetchAll(PDO::FETCH_ASSOC);
echo "Tipos de pontos:\n";
foreach ($tipos as $t) echo "  {$t['tipo']}: {$t['cnt']}\n";

// Verificar empresas
$emps = $pdo->query("SELECT id, nome, ativo FROM empresas LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "\nEmpresas (primeiras 5):\n";
foreach ($emps as $e) echo "  [{$e['id']}] {$e['nome']} (ativo: {$e['ativo']})\n";

// Verificar promoções - quais colunas tem
echo "\nColunas de promoções:\n";
try {
    $info = $pdo->query("PRAGMA table_info(promocoes)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($info as $col) echo "  {$col['name']} ({$col['type']})\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

// Verificar se há algum erro na query das empresas favoritas
echo "\n=== TESTE QUERY EMPRESAS FAVORITAS ===\n";
try {
    $result = $pdo->query("
        SELECT empresas.id, empresas.nome, SUM(pontos.pontos) as total_pontos
        FROM pontos
        JOIN empresas ON pontos.empresa_id = empresas.id
        WHERE pontos.user_id = 2 AND pontos.tipo = 'ganho'
        GROUP BY empresas.id
        ORDER BY total_pontos DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "Empresas favoritas (tipo='ganho'): " . count($result) . "\n";
    
    $result2 = $pdo->query("
        SELECT empresas.id, empresas.nome, SUM(pontos.pontos) as total_pontos
        FROM pontos
        JOIN empresas ON pontos.empresa_id = empresas.id
        WHERE pontos.user_id = 2
        GROUP BY empresas.id
        ORDER BY total_pontos DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "Empresas com TODOS tipos: " . count($result2) . "\n";
    foreach ($result2 as $r) echo "  {$r['nome']}: {$r['total_pontos']} pts\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

// Verificar query de promoções
echo "\n=== TESTE QUERY PROMOÇÕES ===\n";
try {
    $info = $pdo->query("PRAGMA table_info(promocoes)")->fetchAll(PDO::FETCH_ASSOC);
    $cols = array_column($info, 'name');
    echo "Colunas: " . implode(', ', $cols) . "\n";
    
    $hasAtivo = in_array('ativo', $cols);
    $hasStatus = in_array('status', $cols);
    echo "Tem 'ativo': " . ($hasAtivo ? 'SIM' : 'NAO') . "\n";
    echo "Tem 'status': " . ($hasStatus ? 'SIM' : 'NAO') . "\n";
    
    $count = $pdo->query("SELECT COUNT(*) FROM promocoes")->fetchColumn();
    echo "Total promoções: $count\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n";
