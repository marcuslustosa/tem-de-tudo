<?php
$db = new SQLite3('database/database.sqlite');
echo 'Empresas: ' . $db->querySingle('SELECT COUNT(*) FROM empresas') . PHP_EOL;
echo 'Pontos: ' . $db->querySingle('SELECT COUNT(*) FROM pontos') . PHP_EOL;
echo 'Usuarios: ' . $db->querySingle('SELECT COUNT(*) FROM users') . PHP_EOL;
echo 'Promocoes: ' . $db->querySingle('SELECT COUNT(*) FROM promocoes') . PHP_EOL;
$hasBanners = $db->querySingle('SELECT COUNT(*) FROM sqlite_master WHERE type="table" AND name="banners"') > 0;
echo 'Banners: ' . ($hasBanners ? $db->querySingle('SELECT COUNT(*) FROM banners') : 'tabela nao existe') . PHP_EOL;

$res = $db->query('SELECT DISTINCT ramo FROM empresas');
echo 'Ramos existentes: ';
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { echo $row['ramo'] . ', '; }
echo PHP_EOL;

echo 'Clientes: ' . $db->querySingle('SELECT COUNT(*) FROM users WHERE perfil="cliente"') . PHP_EOL;
echo 'Empresas perfil: ' . $db->querySingle('SELECT COUNT(*) FROM users WHERE perfil="empresa"') . PHP_EOL;

// show a sample empresa
$emp = $db->query('SELECT id, nome, ramo, ativo FROM empresas LIMIT 5');
echo PHP_EOL . 'Sample empresas:' . PHP_EOL;
while ($row = $emp->fetchArray(SQLITE3_ASSOC)) {
    echo "  ID={$row['id']}, nome={$row['nome']}, ramo={$row['ramo']}, ativo={$row['ativo']}" . PHP_EOL;
}

// sample pontos tipos
$tipos = $db->query('SELECT tipo, COUNT(*) as cnt, SUM(pontos) as total FROM pontos GROUP BY tipo');
echo PHP_EOL . 'Tipos de pontos:' . PHP_EOL;
while ($row = $tipos->fetchArray(SQLITE3_ASSOC)) {
    echo "  tipo={$row['tipo']}, count={$row['cnt']}, total={$row['total']}" . PHP_EOL;
}

// sample promocoes
$promRes = $db->query('SELECT id, titulo, empresa_id, ativo, status FROM promocoes LIMIT 5');
echo PHP_EOL . 'Sample promocoes:' . PHP_EOL;
while ($row = $promRes->fetchArray(SQLITE3_ASSOC)) {
    echo "  ID={$row['id']}, titulo={$row['titulo']}, empresa_id={$row['empresa_id']}, ativo={$row['ativo']}, status={$row['status']}" . PHP_EOL;
}
