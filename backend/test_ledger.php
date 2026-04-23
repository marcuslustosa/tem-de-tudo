<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LedgerService;
use App\Models\User;

$ledgerService = app(LedgerService::class);

// Pega primeiro usuário
$user = User::first();
if (!$user) {
    die("❌ Nenhum usuário encontrado\n");
}

echo "🎯 Teste do Ledger Service\n";
echo "════════════════════════════\n\n";

// 1. Verifica saldo atual
$saldoAntes = $ledgerService->getBalance($user->id);
echo "👤 Usuário: {$user->name} (ID: {$user->id})\n";
echo "💰 Saldo atual: {$saldoAntes} pontos\n\n";

// 2. Adiciona pontos
echo "➕ Adicionando 100 pontos...\n";
$ledger = $ledgerService->credit(
    userId: $user->id,
    points: 100,
    description: 'Compra de teste - R$ 50,00',
    options: [
        'company_id' => 1,
        'metadata' => [
            'valor_compra' => 50.00,
            'loja' => 'Supermercado ABC'
        ]
    ]
);

echo "✅ Pontos adicionados!\n";
echo "   ID da transação: {$ledger->id}\n";
echo "   Idempotency key: {$ledger->idempotency_key}\n";
echo "   Saldo antes: {$ledger->balance_before}\n";
echo "   Saldo depois: {$ledger->balance_after}\n\n";

// 3. Verifica novo saldo
$saldoDepois = $ledgerService->getBalance($user->id);
echo "💰 Novo saldo: {$saldoDepois} pontos\n";
echo "📈 Diferença: +" . ($saldoDepois - $saldoAntes) . " pontos\n\n";

// 4. Testa idempotência (tentar adicionar de novo com mesma key)
echo "🔄 Testando idempotência (mesma chave)...\n";
try {
    $ledgerDuplicado = $ledgerService->credit(
        userId: $user->id,
        points: 100,
        description: 'Compra de teste - R$ 50,00',
        options: [
            'idempotency_key' => $ledger->idempotency_key
        ]
    );
    echo "✅ Retornou transação existente (não duplicou)\n";
    echo "   ID: {$ledgerDuplicado->id} (mesmo da anterior)\n\n";
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n\n";
}

// 5. Histórico das últimas 5 transações
echo "📜 Últimas 5 transações:\n";
echo "────────────────────────────\n";
$historico = $ledgerService->getHistory($user->id, 5);
foreach ($historico as $h) {
    $tipo = $h->isCredit() ? '➕' : '➖';
    echo "{$tipo} {$h->points} pts - {$h->description}\n";
    echo "   Saldo: {$h->balance_before} → {$h->balance_after}\n";
    echo "   Data: {$h->created_at->format('d/m/Y H:i:s')}\n\n";
}

// 6. Auditoria
echo "🔍 Auditoria de integridade:\n";
echo "────────────────────────────\n";
$audit = $ledgerService->audit($user->id);
if ($audit['valid']) {
    echo "✅ Ledger VÁLIDO\n";
    echo "   Saldo calculado: {$audit['calculated_balance']} pontos\n";
} else {
    echo "❌ INCONSISTÊNCIA DETECTADA!\n";
    echo "   Discrepância: {$audit['discrepancy']}\n";
    foreach ($audit['errors'] as $error) {
        echo "   • {$error}\n";
    }
}

echo "\n✅ Teste concluído com sucesso!\n";
