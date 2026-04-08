<?php
// Diagnóstico completo de todas as páginas reportadas com problema

$base = 'http://127.0.0.1:8099/api';
$results = [];

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

function ok($label, $cond, $msg = '') {
    $icon = $cond ? '✅' : '❌';
    echo "$icon $label" . ($msg ? " | $msg" : '') . "\n";
    return $cond;
}

function section($title) {
    echo "\n=== $title ===\n";
}

// 1. Login como cliente
section('1. LOGIN CLIENTE');
[$code, $data] = req("$base/auth/login", 'POST', json_encode([
    'email' => 'cliente@teste.com',
    'password' => 'senha123'
]));
ok('Login cliente', $code === 200, "HTTP $code");
$token = $data['token'] ?? $data['data']['token'] ?? null;
ok('Token recebido', !empty($token), $token ? substr($token, 0, 20).'...' : 'NENHUM');

// 2. /pontos/meus-dados
section('2. /pontos/meus-dados');
[$code, $data, $raw] = req("$base/pontos/meus-dados", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";
else {
    $d = $data['data'] ?? [];
    echo "   pontos_total: " . ($d['pontos_total'] ?? 'N/A') . "\n";
    echo "   nivel: " . json_encode($d['nivel'] ?? 'N/A') . "\n";
}

// 3. /pontos/historico
section('3. /pontos/historico');
[$code, $data, $raw] = req("$base/pontos/historico", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";
else {
    $items = $data['data']['data'] ?? $data['data'] ?? [];
    echo "   Total registros: " . count($items) . "\n";
    if (count($items) > 0) {
        echo "   Primeiro: " . json_encode(array_slice($items[0], 0, 5)) . "\n";
    }
}

// 4. /cliente/dashboard
section('4. /cliente/dashboard');
[$code, $data, $raw] = req("$base/cliente/dashboard", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";
else {
    $d = $data['data'] ?? [];
    echo "   usuario.saldo_pontos: " . ($d['usuario']['saldo_pontos'] ?? 'N/A') . "\n";
    echo "   empresas_count: " . count($d['empresas'] ?? []) . "\n";
}

// 5. /pontos/meus-cupons
section('5. /pontos/meus-cupons');
[$code, $data, $raw] = req("$base/pontos/meus-cupons", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";

// 6. /cliente/historico-pontos  
section('6. /cliente/historico-pontos');
[$code, $data, $raw] = req("$base/cliente/historico-pontos", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";

// 7. /me
section('7. /me (dados do usuário)');
[$code, $data, $raw] = req("$base/me", 'GET', null, $token);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";
else {
    $u = $data['data'] ?? $data['user'] ?? $data;
    echo "   name: " . ($u['name'] ?? 'N/A') . "\n";
    echo "   perfil: " . ($u['perfil'] ?? $u['role'] ?? 'N/A') . "\n";
    echo "   pontos: " . ($u['pontos'] ?? 'N/A') . "\n";
}

// 8. Login empresa
section('8. LOGIN EMPRESA');
[$code, $data] = req("$base/auth/login", 'POST', json_encode([
    'email' => 'empresa@teste.com',
    'password' => 'senha123'
]));
ok('Login empresa', $code === 200, "HTTP $code");
$tokenEmpresa = $data['token'] ?? $data['data']['token'] ?? null;
ok('Token recebido', !empty($tokenEmpresa));

// 9. /empresa/dashboard
section('9. /empresa/dashboard');
[$code, $data, $raw] = req("$base/empresa/dashboard", 'GET', null, $tokenEmpresa);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";
else {
    $d = $data['data'] ?? [];
    echo "   empresa: " . ($d['empresa']['nome'] ?? 'N/A') . "\n";
    echo "   clientes_total: " . ($d['clientes_total'] ?? 'N/A') . "\n";
}

// 10. /empresa/clientes
section('10. /empresa/clientes');
[$code, $data, $raw] = req("$base/empresa/clientes", 'GET', null, $tokenEmpresa);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";

// 11. /empresa/promocoes
section('11. /empresa/promocoes');
[$code, $data, $raw] = req("$base/empresa/promocoes", 'GET', null, $tokenEmpresa);
ok('Status HTTP', $code === 200, "HTTP $code");
if ($code !== 200) echo "   Resposta: $raw\n";

// 12. Tabela pontos - verificar dados
section('12. VERIFICAR DADOS NO BANCO');
$dbPath = __DIR__ . '/database/database.sqlite';
if (file_exists($dbPath)) {
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Contar usuários
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "   Total usuários: $count\n";
        
        // Contar pontos
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM pontos")->fetchColumn();
            echo "   Total registros pontos: $count\n";
        } catch (Exception $e) {
            echo "   ERRO tabela pontos: " . $e->getMessage() . "\n";
        }
        
        // Contar empresas
        $count = $pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
        echo "   Total empresas: $count\n";
        
        // Verificar cliente@teste.com
        $stmt = $pdo->prepare("SELECT id, name, email, perfil, pontos FROM users WHERE LOWER(email) = ?");
        $stmt->execute(['cliente@teste.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "   cliente@teste.com: ID={$user['id']}, pontos={$user['pontos']}\n";
        } else {
            echo "   AVISO: cliente@teste.com não encontrado!\n";
        }
        
        // Verificar empresa@teste.com
        $stmt = $pdo->prepare("SELECT id, name, email, perfil FROM users WHERE LOWER(email) = ?");
        $stmt->execute(['empresa@teste.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "   empresa@teste.com: ID={$user['id']}, perfil={$user['perfil']}\n";
            // buscar empresa vinculada
            $stmt2 = $pdo->prepare("SELECT id, nome, categoria FROM empresas WHERE user_id = ?");
            $stmt2->execute([$user['id']]);
            $emp = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                echo "   empresa vinculada: {$emp['nome']} ({$emp['categoria']})\n";
            } else {
                echo "   AVISO: nenhuma empresa vinculada ao user empresa@teste.com!\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ERRO DB: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ERRO: database.sqlite não encontrado!\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
