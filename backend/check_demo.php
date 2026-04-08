<?php
$db = new SQLite3('database/database.sqlite');
// Check pontos for cliente@teste.com (user_id=2)
$res = $db->query('SELECT tipo, COUNT(*) as c, SUM(pontos) as s FROM pontos WHERE user_id=2 GROUP BY tipo');
echo "Pontos de cliente@teste.com (user_id=2):" . PHP_EOL;
while ($r = $res->fetchArray(SQLITE3_ASSOC)) echo "  {$r['tipo']}: {$r['c']} registros, total={$r['s']}" . PHP_EOL;
echo "Total pontos field: " . $db->querySingle('SELECT pontos FROM users WHERE id=2') . PHP_EOL;

// Check empresa da empresa@teste.com
$empId = $db->querySingle('SELECT id FROM empresas WHERE user_id=3');
echo PHP_EOL . "empresa_id para empresa\@teste.com: $empId" . PHP_EOL;
if ($empId) {
    $empRes = $db->query("SELECT tipo, COUNT(*) as c, SUM(pontos) as s FROM pontos WHERE empresa_id=$empId GROUP BY tipo");
    echo "Transacoes na empresa ID=$empId:" . PHP_EOL;
    while ($r = $empRes->fetchArray(SQLITE3_ASSOC)) echo "  {$r['tipo']}: {$r['c']} registros, total={$r['s']}" . PHP_EOL;
    $clients = $db->querySingle("SELECT COUNT(DISTINCT user_id) FROM pontos WHERE empresa_id=$empId");
    echo "Clientes unicos: $clients" . PHP_EOL;
    $nome = $db->querySingle("SELECT nome FROM empresas WHERE id=$empId");
    echo "Nome empresa: $nome" . PHP_EOL;
}

// Check promos for this empresa
if ($empId) {
    $promos = $db->query("SELECT COUNT(*) as c, SUM(CASE WHEN status='ativa' THEN 1 ELSE 0 END) as ativas FROM promocoes WHERE empresa_id=$empId");
    $pr = $promos->fetchArray(SQLITE3_ASSOC);
    echo "Promos: total={$pr['c']}, ativas={$pr['ativas']}" . PHP_EOL;
}
