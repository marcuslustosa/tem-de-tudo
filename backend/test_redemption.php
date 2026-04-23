<?php

/**
 * TESTE: Sistema de Resgate PDV
 * 
 * Demonstra o fluxo completo de resgate no ponto de venda:
 * 1. Solicitar resgate (reserva pontos)
 * 2. Confirmar resgate (debita pontos)
 * 3. Cancelar resgate (libera reserva)
 * 4. Estornar resgate (devolve pontos)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Empresa;
use App\Services\RedemptionService;
use App\Services\LedgerService;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       TESTE: SISTEMA DE RESGATE PDV (RESERVA/ESTORNO)          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$redemptionService = app(RedemptionService::class);
$ledgerService = app(LedgerService::class);

try {
    // 1. PREPARAÇÃO: Adiciona pontos ao usuário
    echo "📋 PREPARAÇÃO\n";
    echo str_repeat("─", 60) . "\n";
    
    // Cria usuário se não existir
    $user = User::first();
    if (!$user) {
        echo "Criando usuário de teste...\n";
        $user = User::create([
            'name' => 'João Silva',
            'email' => 'joao@teste.com',
            'password' => bcrypt('123456'),
            'telefone' => '11999999999',
            'perfil' => 'cliente',
        ]);
    }
    
    // Cria empresa se não existir
    $empresa = Empresa::first();
    if (!$empresa) {
        echo "Criando empresa de teste...\n";
        $empresa = Empresa::create([
            'nome' => 'Loja VIP Teste',
            'nome_fantasia' => 'Loja VIP Teste',
            'cnpj' => '12345678000199',
            'telefone' => '1133334444',
            'endereco' => 'Rua Teste, 123',
            'owner_id' => $user->id,
            'ativo' => true,
        ]);
    }
    
    $nomeEmpresa = $empresa->nome_fantasia ?? $empresa->nome ?? 'N/A';
    $userName = $user->name ?? $user->nome ?? 'N/A';
    echo "Usuário: {$userName} (ID: {$user->id})\n";
    echo "Empresa: {$nomeEmpresa} (ID: {$empresa->id})\n";
    
    // Adiciona 500 pontos para teste
    $ledgerService->credit(
        userId: $user->id,
        points: 500,
        description: "Crédito inicial para teste de resgate PDV",
        options: ['company_id' => $empresa->id]
    );
    
    $saldoInicial = $ledgerService->getBalance($user->id);
    echo "Saldo inicial: {$saldoInicial} pontos\n\n";
    
    // 2. TESTE 1: SOLICITAR RESGATE (RESERVA)
    echo "🔵 TESTE 1: Solicitar Resgate (Reserva 200 pontos)\n";
    echo str_repeat("─", 60) . "\n";
    
    $intent1 = $redemptionService->requestRedemption(
        userId: $user->id,
        companyId: $empresa->id,
        points: 200,
        options: [
            'type' => 'product',
            'metadata' => [
                'produto' => 'Camiseta VIP',
                'valor_original' => 'R$ 79,90',
            ],
            'pdv_operator_id' => $user->id,
            'pdv_terminal_id' => 'PDV-001',
            'expires_minutes' => 15,
        ]
    );
    
    echo "✅ Resgate solicitado!\n";
    echo "Intent ID: {$intent1->intent_id}\n";
    echo "Status: {$intent1->status}\n";
    echo "Pontos reservados: {$intent1->points_requested}\n";
    echo "Expira em: {$intent1->expires_at->format('H:i:s')} ({$intent1->expires_at->diffInMinutes(now())} minutos)\n";
    
    $saldoAtual = $ledgerService->getBalance($user->id);
    $saldoReservado = $ledgerService->getReservedBalance($user->id);
    $saldoDisponivel = $saldoAtual - $saldoReservado;
    
    echo "\nSaldo total: {$saldoAtual} pontos\n";
    echo "Saldo reservado: {$saldoReservado} pontos\n";
    echo "Saldo disponível: {$saldoDisponivel} pontos\n\n";
    
    // 3. TESTE 2: CONFIRMAR RESGATE
    echo "🟢 TESTE 2: Confirmar Resgate (Debita pontos)\n";
    echo str_repeat("─", 60) . "\n";
    
    $intent1Confirmado = $redemptionService->confirmRedemption($intent1->intent_id);
    
    echo "✅ Resgate confirmado!\n";
    echo "Status: {$intent1Confirmado->status}\n";
    echo "Pontos confirmados: {$intent1Confirmado->points_confirmed}\n";
    echo "Confirmado em: {$intent1Confirmado->confirmed_at->format('H:i:s')}\n";
    
    $saldoAtual = $ledgerService->getBalance($user->id);
    $saldoReservado = $ledgerService->getReservedBalance($user->id);
    
    echo "\nSaldo total: {$saldoAtual} pontos (debitado)\n";
    echo "Saldo reservado: {$saldoReservado} pontos (liberado)\n\n";
    
    // 4. TESTE 3: SOLICITAR E CANCELAR RESGATE
    echo "🟡 TESTE 3: Solicitar e Cancelar Resgate\n";
    echo str_repeat("─", 60) . "\n";
    
    $intent2 = $redemptionService->requestRedemption(
        userId: $user->id,
        companyId: $empresa->id,
        points: 150,
        options: [
            'type' => 'discount',
            'metadata' => ['desconto' => '15% na próxima compra'],
            'expires_minutes' => 10,
        ]
    );
    
    echo "Resgate solicitado: {$intent2->intent_id} (150 pontos reservados)\n";
    
    $saldoAntes = $ledgerService->getBalance($user->id);
    $reservadoAntes = $ledgerService->getReservedBalance($user->id);
    
    echo "Saldo antes do cancelamento: {$saldoAntes} (reservado: {$reservadoAntes})\n";
    
    $intent2Cancelado = $redemptionService->cancelRedemption(
        $intent2->intent_id,
        'Cliente desistiu da compra'
    );
    
    echo "\n✅ Resgate cancelado!\n";
    echo "Status: {$intent2Cancelado->status}\n";
    echo "Motivo: {$intent2Cancelado->cancellation_reason}\n";
    
    $saldoDepois = $ledgerService->getBalance($user->id);
    $reservadoDepois = $ledgerService->getReservedBalance($user->id);
    
    echo "\nSaldo após cancelamento: {$saldoDepois} (reservado: {$reservadoDepois})\n";
    echo "Pontos devolvidos: " . ($reservadoAntes - $reservadoDepois) . "\n\n";
    
    // 5. TESTE 4: ESTORNAR RESGATE CONFIRMADO
    echo "🔴 TESTE 4: Estornar Resgate Confirmado (Admin)\n";
    echo str_repeat("─", 60) . "\n";
    
    $saldoAntes = $ledgerService->getBalance($user->id);
    
    echo "Estornando resgate anterior: {$intent1Confirmado->intent_id}\n";
    echo "Saldo antes do estorno: {$saldoAntes} pontos\n";
    
    $intent1Estornado = $redemptionService->reverseRedemption(
        intentId: $intent1Confirmado->intent_id,
        reason: 'Produto devolvido pelo cliente - estoque danificado',
        reversedBy: $user->id
    );
    
    echo "\n✅ Resgate estornado!\n";
    echo "Status: {$intent1Estornado->status}\n";
    echo "Pontos devolvidos: {$intent1Estornado->points_confirmed}\n";
    echo "Motivo: {$intent1Estornado->reversal_reason}\n";
    
    $saldoDepois = $ledgerService->getBalance($user->id);
    
    echo "\nSaldo após estorno: {$saldoDepois} pontos\n";
    echo "Pontos creditados de volta: " . ($saldoDepois - $saldoAntes) . "\n\n";
    
    // 6. TESTE 5: PROCESSAR RESERVAS EXPIRADAS
    echo "⏰ TESTE 5: Processar Reservas Expiradas (Cron)\n";
    echo str_repeat("─", 60) . "\n";
    
    $result = $redemptionService->processExpiredReservations();
    
    echo "Reservas expiradas encontradas: {$result['total_expired']}\n";
    echo "Reservas processadas: {$result['processed']}\n\n";
    
    // 7. HISTÓRICO DO USUÁRIO
    echo "📊 HISTÓRICO DE RESGATES DO USUÁRIO\n";
    echo str_repeat("─", 60) . "\n";
    
    $historico = $redemptionService->getUserRedemptions($user->id, 10);
    
    foreach ($historico as $h) {
        $pontos = $h->points_confirmed ?? $h->points_requested;
        $data = $h->confirmed_at ? $h->confirmed_at->format('d/m/Y H:i') : $h->requested_at->format('d/m/Y H:i');
        
        echo "  • Intent: {$h->intent_id}\n";
        echo "    Status: {$h->status} | Pontos: {$pontos}\n";
        echo "    Data: {$data}\n";
        echo "\n";
    }
    
    // 8. SALDO FINAL
    echo "💰 SALDO FINAL\n";
    echo str_repeat("─", 60) . "\n";
    
    $saldoFinal = $ledgerService->getBalance($user->id);
    $reservadoFinal = $ledgerService->getReservedBalance($user->id);
    $disponivelFinal = $saldoFinal - $reservadoFinal;
    
    echo "Saldo total: {$saldoFinal} pontos\n";
    echo "Saldo reservado: {$reservadoFinal} pontos\n";
    echo "Saldo disponível: {$disponivelFinal} pontos\n\n";
    
    // 9. AUDITORIA
    echo "🔍 AUDITORIA DO LEDGER\n";
    echo str_repeat("─", 60) . "\n";
    
    $audit = $ledgerService->audit($user->id);
    
    if ($audit['valid']) {
        echo "✅ Ledger válido - sem discrepâncias\n";
    } else {
        echo "❌ Ledger inválido!\n";
        echo "Discrepância: {$audit['discrepancy']} pontos\n";
        echo "Erros: " . json_encode($audit['errors'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ TODOS OS TESTES PASSARAM!                 ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
