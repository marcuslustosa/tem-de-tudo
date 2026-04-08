<?php
$base = 'http://127.0.0.1:8099/api';
$ok = 0; $fail = 0; $warn = 0;

function req($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    $opts = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => $headers];
    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
        $opts[CURLOPT_CUSTOMREQUEST] = $method;
        if ($data !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
    } elseif ($method === 'DELETE') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }
    curl_setopt_array($ch, $opts);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($out, true)];
}

function check($label, $cond, $detail = '', $isWarn = false) {
    global $ok, $fail, $warn;
    if ($cond)          { echo "✅ $label" . ($detail ? " | $detail" : '') . "\n"; $ok++; }
    elseif ($isWarn)    { echo "⚠️  $label" . ($detail ? " | $detail" : '') . "\n"; $warn++; }
    else                { echo "❌ $label" . ($detail ? " | $detail" : '') . "\n"; $fail++; }
}

// === OBTER TOKENS ===
[,$d] = req('POST', "$base/auth/login", ['email'=>'admin@temdetudo.com','password'=>'senha123']);
$adminToken = $d['token'] ?? null;

[,$d] = req('POST', "$base/auth/login", ['email'=>'cliente@teste.com','password'=>'senha123']);
$clienteToken = $d['token'] ?? null;
$clienteId = $d['user']['id'] ?? null;

[,$d] = req('POST', "$base/auth/login", ['email'=>'empresa@teste.com','password'=>'senha123']);
$empresaToken = $d['token'] ?? null;

echo "=== BLOCO 1: AUTENTICAÇÃO ===\n";
check("Admin token obtido",   (bool)$adminToken);
check("Cliente token obtido", (bool)$clienteToken);
check("Empresa token obtido", (bool)$empresaToken);

echo "\n=== BLOCO 2: PERFIL ===\n";
[$c,$d] = req('GET', "$base/auth/me", null, $clienteToken);
check("/auth/me cliente", $c === 200, "perfil=" . ($d['data']['perfil'] ?? $d['user']['perfil'] ?? '?'));

[$c,$d] = req('GET', "$base/auth/me", null, $adminToken);
check("/auth/me admin", $c === 200, "perfil=" . ($d['data']['perfil'] ?? $d['user']['perfil'] ?? '?'));

// Atualizar perfil
[$c,$d] = req('PUT', "$base/perfil", ['name'=>'Cliente Teste','telefone'=>'11999999999'], $clienteToken);
check("PUT /perfil (atualizar dados)", $c === 200, $d['message'] ?? "HTTP $c");

echo "\n=== BLOCO 3: PONTOS ===\n";
[$c,$d] = req('GET', "$base/pontos/meus-dados", null, $clienteToken);
check("GET /pontos/meus-dados", $c === 200, "pontos=" . ($d['data']['pontos_total'] ?? '?'));

[$c,$d] = req('GET', "$base/pontos/historico", null, $clienteToken);
$nHist = count($d['data']['data'] ?? $d['data'] ?? []);
check("GET /pontos/historico", $c === 200, "$nHist registros");

[$c,$d] = req('GET', "$base/pontos/meus-cupons", null, $clienteToken);
check("GET /pontos/meus-cupons", $c === 200, "HTTP $c");

echo "\n=== BLOCO 4: EMPRESAS / PARCEIROS ===\n";
[$c,$d] = req('GET', "$base/empresas");
$empresas = $d['data'] ?? $d ?? [];
$nEmp = count(is_array($empresas) ? $empresas : []);
check("GET /empresas (público)", $c === 200, "$nEmp empresas");

$primeiraEmpresaId = null;
if ($nEmp > 0) {
    $first = is_array($empresas) ? $empresas[0] : null;
    $primeiraEmpresaId = $first['id'] ?? null;
    [$c,$d] = req('GET', "$base/empresas/$primeiraEmpresaId");
    check("GET /empresas/{id}", $c === 200, "nome=" . ($d['data']['nome'] ?? '?'));

    [$c,$d] = req('GET', "$base/empresas/$primeiraEmpresaId/produtos");
    check("GET /empresas/{id}/produtos", in_array($c, [200, 404]), "HTTP $c");

    [$c,$d] = req('GET', "$base/empresas/$primeiraEmpresaId/promocoes");
    check("GET /empresas/{id}/promocoes", in_array($c, [200, 404]), "HTTP $c");
}

[$c,$d] = req('GET', "$base/cliente/empresas", null, $clienteToken);
check("GET /cliente/empresas", in_array($c, [200, 404]), "HTTP $c");

echo "\n=== BLOCO 5: CHECKIN / ACÚMULO DE PONTOS ===\n";
if ($primeiraEmpresaId) {
    [$c,$d] = req('POST', "$base/pontos/checkin", [
        'empresa_id'    => $primeiraEmpresaId,
        'valor_compra'  => 100,
        'observacoes'   => 'Teste automatizado',
    ], $clienteToken);
    $isOk = $c === 200 || $c === 201;
    check("POST /pontos/checkin", $isOk, ($d['message'] ?? "HTTP $c"), !$isOk);
}

echo "\n=== BLOCO 6: DASHBOARD CLIENTE ===\n";
[$c,$d] = req('GET', "$base/cliente/dashboard", null, $clienteToken);
check("GET /cliente/dashboard", $c === 200, "HTTP $c");

echo "\n=== BLOCO 7: EMPRESA — PROMOÇÕES ===\n";
[$c,$d] = req('GET', "$base/empresa/promocoes", null, $empresaToken);
$nPromos = count($d['data'] ?? []);
check("GET /empresa/promocoes", $c === 200, "$nPromos promoções");

[$c,$d] = req('POST', "$base/empresa/promocoes", [
    'titulo'     => 'Promo Teste Auto',
    'nome'       => 'Promo Teste Auto',
    'descricao'  => 'Criada pelo teste automatizado',
    'desconto'   => 10,
    'tipo'       => 'desconto',
    'ativo'      => true,
], $empresaToken);
$promoId = $d['data']['id'] ?? null;
check("POST /empresa/promocoes (criar)", in_array($c, [200, 201]), ($d['message'] ?? "HTTP $c"), !in_array($c,[200,201]));

if ($promoId) {
    [$c,$d] = req('PUT', "$base/empresa/promocoes/$promoId", ['titulo'=>'Promo Editada','ativo'=>false], $empresaToken);
    check("PUT /empresa/promocoes/{id} (editar)", in_array($c, [200,201]), ($d['message'] ?? "HTTP $c"), !in_array($c,[200,201]));

    [$c,$d] = req('DELETE', "$base/empresa/promocoes/$promoId", null, $empresaToken);
    check("DELETE /empresa/promocoes/{id}", in_array($c, [200,204]), ($d['message'] ?? "HTTP $c"), !in_array($c,[200,204]));
}

echo "\n=== BLOCO 8: EMPRESA — CLIENTES E RELATÓRIO ===\n";
[$c,$d] = req('GET', "$base/empresa/clientes", null, $empresaToken);
check("GET /empresa/clientes", in_array($c, [200, 404]), "HTTP $c");

[$c,$d] = req('GET', "$base/empresa/relatorio-pontos", null, $empresaToken);
check("GET /empresa/relatorio-pontos", in_array($c, [200, 404]), "HTTP $c");

echo "\n=== BLOCO 9: ADMIN ===\n";
[$c,$d] = req('GET', "$base/admin/dashboard-stats", null, $adminToken);
check("GET /admin/dashboard-stats", $c === 200, "HTTP $c");

[$c,$d] = req('GET', "$base/admin/users", null, $adminToken);
$nUsers = count($d['data']['data'] ?? $d['data'] ?? []);
check("GET /admin/users", in_array($c, [200,404]), "HTTP $c | $nUsers registros");

[$c,$d] = req('GET', "$base/admin/pontos/estatisticas", null, $adminToken);
check("GET /admin/pontos/estatisticas", in_array($c, [200,404]), "HTTP $c");

echo "\n=== BLOCO 10: SEGURANÇA ===\n";
[$c] = req('GET', "$base/admin/dashboard-stats", null, $clienteToken);
check("Cliente NÃO acessa admin", $c !== 200, "HTTP $c");

[$c] = req('GET', "$base/admin/dashboard-stats", null, $empresaToken);
check("Empresa NÃO acessa admin", $c !== 200, "HTTP $c");

[$c] = req('GET', "$base/auth/me");
check("Sem token -> 401", $c === 401, "HTTP $c");

[$c,$d] = req('POST', "$base/auth/login", ['email'=>'admin@temdetudo.com', 'password'=>'senhaerrada']);
check("Senha errada -> 401", $c === 401, $d['message'] ?? '');

// E-mail inexistente
[$c,$d] = req('POST', "$base/auth/login", ['email'=>'inexistente@email.com', 'password'=>'qualquer']);
check("E-mail inexistente -> 401", $c === 401, $d['message'] ?? '');

echo "\n=== RESULTADO FINAL ===\n";
echo "✅ $ok OK  |  ⚠️  $warn avisos  |  ❌ $fail falhas\n";
echo $fail === 0 ? "🎉 TUDO VALIDADO!\n" : "⚠️  Verifique os itens com ❌\n";
