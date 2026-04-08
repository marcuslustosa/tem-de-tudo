<?php
// Script para popular dados fictícios realistas
$db = new SQLite3('database/database.sqlite');
$db->exec('PRAGMA journal_mode=WAL');

$now = date('Y-m-d H:i:s');
$userId = 2; // cliente@teste.com

// Empresas existentes (IDs 1-16)
$empresas = [];
$res = $db->query('SELECT id, nome, points_multiplier FROM empresas WHERE ativo=1');
while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
    $empresas[] = $r;
}

echo "Empresas ativas: " . count($empresas) . PHP_EOL;

// Adicionar checkins variados para cliente@teste.com nos últimos 45 dias
$novosTransacoes = [
    // [empresa_id, pontos, tipo, descricao, dias_atras]
    [2, 120, 'checkin', 'Check-in no restaurante', 1],
    [3, 80,  'checkin', 'Check-in na academia', 2],
    [4, 60,  'checkin', 'Check-in na cafeteria', 3],
    [1, 150, 'checkin', 'Compra qualificada', 4],
    [5, 40,  'checkin', 'Check-in no pet shop', 5],
    [6, 200, 'checkin', 'Compra semanal', 6],
    [7, 90,  'checkin', 'Check-in na farmacia', 7],
    [8, 110, 'checkin', 'Compra na padaria', 8],
    [2, 130, 'checkin', 'Almoco especial', 10],
    [3, 85,  'checkin', 'Treino semanal', 11],
    [9, 75,  'checkin', 'Check-in na doceria', 12],
    [10, 95, 'checkin', 'Check-in na barbearia', 14],
    [1, 180, 'earn',    'Cashback boas-vindas', 15],
    [4, 55,  'checkin', 'Cafe da tarde', 16],
    [6, 220, 'checkin', 'Compra quinzenal', 18],
    [11, 65, 'checkin', 'Check-in no salao', 20],
    [2, 140, 'checkin', 'Jantar em familia', 21],
    [7, 100, 'checkin', 'Medicamentos mensais', 22],
    [3, 80,  'checkin', 'Aula de spinning', 24],
    [12, 45, 'checkin', 'Check-in new parceiro', 25],
    [1, 300, 'earn',    'Bonus fidelidade mensal', 28],
    [6, 170, 'checkin', 'Compra mensal grande', 30],
    [4, 50,  'checkin', 'Cafe expresso', 32],
    [13, 85, 'checkin', 'Check-in beleza', 35],
    [-1, 200, 'resgate', 'Resgate voucher jantar', 38],
    [2, 160, 'checkin', 'Almoço de negócios', 40],
    [3, 70,  'checkin', 'Personal trainer', 42],
];

$insertCount = 0;
$stmt = $db->prepare('INSERT INTO pontos (user_id, empresa_id, pontos, descricao, tipo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');

foreach ($novosTransacoes as [$empId, $pts, $tipo, $desc, $daysAgo]) {
    $actualEmpId = $empId < 0 ? 1 : $empId;
    if ($actualEmpId > count($empresas)) $actualEmpId = ($actualEmpId % count($empresas)) + 1;
    $date = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
    
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $actualEmpId, SQLITE3_INTEGER);
    $stmt->bindValue(3, $pts, SQLITE3_INTEGER);
    $stmt->bindValue(4, $desc, SQLITE3_TEXT);
    $stmt->bindValue(5, $tipo, SQLITE3_TEXT);
    $stmt->bindValue(6, $date, SQLITE3_TEXT);
    $stmt->bindValue(7, $date, SQLITE3_TEXT);
    $stmt->execute();
    $stmt->reset();
    $insertCount++;
}

echo "Inseridas $insertCount transacoes para cliente\@teste.com" . PHP_EOL;

// Calcular novo saldo
$novoTotal = $db->querySingle("SELECT SUM(pontos) FROM pontos WHERE user_id=$userId AND tipo NOT IN ('resgate','redeem')");
$novoGasto = $db->querySingle("SELECT SUM(pontos) FROM pontos WHERE user_id=$userId AND tipo IN ('resgate','redeem')");
$novoSaldo = $novoTotal - $novoGasto;
echo "Novo total ganho: $novoTotal, gasto: $novoGasto, saldo calculado: $novoSaldo" . PHP_EOL;

// Atualizar pontos do usuario
$db->exec("UPDATE users SET pontos=$novoSaldo WHERE id=$userId");
echo "Atualizado users.pontos = $novoSaldo para user_id=$userId" . PHP_EOL;

// Adicionar checkins de outros clientes na empresa ID=1 (empresa@teste.com)
// Verificar quantos clientes já existem
$clientesExist = $db->querySingle('SELECT COUNT(DISTINCT user_id) FROM pontos WHERE empresa_id=1 AND tipo NOT IN ("resgate","redeem")');
echo PHP_EOL . "Clientes existentes na empresa ID=1: $clientesExist" . PHP_EOL;

// Adicionar mais 10 clientes com checkins na empresa_id=1
$extraClientes = [
    [4, 200, 3],  [5, 150, 5], [6, 300, 2],  [7, 180, 8], [8, 120, 10],
    [9, 250, 1],  [10, 90, 4], [11, 175, 6], [12, 210, 7], [13, 130, 9],
];
$stmtE = $db->prepare('INSERT INTO pontos (user_id, empresa_id, pontos, descricao, tipo, created_at, updated_at) VALUES (?, 1, ?, "Check-in no parceiro", "checkin", ?, ?)');
foreach ($extraClientes as [$uid, $pts, $days]) {
    $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $stmtE->bindValue(1, $uid, SQLITE3_INTEGER);
    $stmtE->bindValue(2, $pts, SQLITE3_INTEGER);
    $stmtE->bindValue(3, $date, SQLITE3_TEXT);
    $stmtE->bindValue(4, $date, SQLITE3_TEXT);
    $stmtE->execute();
    $stmtE->reset();
}
echo "Adicionados 10 clientes extras na empresa ID=1" . PHP_EOL;

// Garantir que empresa ID=1 tem promos ativas
$promosAtivas = $db->querySingle('SELECT COUNT(*) FROM promocoes WHERE empresa_id=1 AND status="ativa" AND ativo=1');
echo "Promos ativas na empresa ID=1 antes: $promosAtivas" . PHP_EOL;

if ($promosAtivas < 3) {
    $db->exec("INSERT OR IGNORE INTO promocoes (empresa_id, titulo, descricao, desconto, ativo, status, created_at, updated_at) VALUES
        (1, 'Fidelidade Premium', 'Pontos em dobro para clientes frequentes', 0, 1, 'ativa', '$now', '$now'),
        (1, 'Weekend Special', '15% OFF aos finais de semana', 15, 1, 'ativa', '$now', '$now'),
        (1, 'Bonus Mensal', 'Bônus de 200 pontos na compra acima de R\$100', 0, 1, 'ativa', '$now', '$now')
    ");
    echo "Adicionadas promos para empresa ID=1" . PHP_EOL;
}

// Atualizar avaliação média das empresas
$db->exec("UPDATE empresas SET avaliacao_media=4.5, total_avaliacoes=28 WHERE id=1");
$db->exec("UPDATE empresas SET avaliacao_media=4.7, total_avaliacoes=45 WHERE id=2");
$db->exec("UPDATE empresas SET avaliacao_media=4.3, total_avaliacoes=19 WHERE id=3");

// Verificar estado final
$totalPontos = $db->querySingle("SELECT COUNT(*) FROM pontos");
echo PHP_EOL . "Total pontos no DB: $totalPontos" . PHP_EOL;
$clientesEmpresa1 = $db->querySingle('SELECT COUNT(DISTINCT user_id) FROM pontos WHERE empresa_id=1 AND tipo NOT IN ("resgate","redeem")');
echo "Clientes na empresa ID=1: $clientesEmpresa1" . PHP_EOL;
$saldoFinal = $db->querySingle("SELECT pontos FROM users WHERE id=$userId");
echo "Saldo final cliente\@teste.com: $saldoFinal" . PHP_EOL;

echo PHP_EOL . "DONE!" . PHP_EOL;
