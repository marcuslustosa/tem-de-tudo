<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BillingService;
use App\Models\Empresa;
use App\Models\SubscriptionPlan;

$billingService = app(BillingService::class);

echo "💰 Teste do Sistema de Cobrança\n";
echo "════════════════════════════════\n\n";

// 1. Lista planos disponíveis
echo "📋 Planos disponíveis:\n";
echo "────────────────────────\n";
$plans = SubscriptionPlan::where('is_active', true)->get();
foreach ($plans as $plan) {
    echo "• {$plan->display_name}\n";
    echo "  Preço: R$ " . number_format($plan->monthly_price_cents / 100, 2, ',', '.') . "/mês\n";
    echo "  Trial: {$plan->trial_days} dias\n\n";
}

// 2. Pega primeira empresa
$empresa = Empresa::first();
if (!$empresa) {
    die("❌ Nenhuma empresa encontrada\n");
}

echo "🏢 Empresa: {$empresa->nome_fantasia} (ID: {$empresa->id})\n\n";

// 3. Verifica se já tem assinatura
$subscriptionExistente = $empresa->activeSubscription;
if ($subscriptionExistente) {
    echo "ℹ️  Empresa já tem assinatura:\n";
    echo "   Plano: {$subscriptionExistente->plan->display_name}\n";
    echo "   Status: {$subscriptionExistente->status}\n";
    echo "   Trial até: " . ($subscriptionExistente->trial_ends_at ? $subscriptionExistente->trial_ends_at->format('d/m/Y') : 'N/A') . "\n\n";
} else {
    // 4. Cria assinatura
    echo "➕ Criando assinatura (plano Basic)...\n";
    $subscription = $billingService->createSubscription($empresa->id, 'basic');
    
    echo "✅ Assinatura criada!\n";
    echo "   ID: {$subscription->id}\n";
    echo "   Status: {$subscription->status}\n";
    echo "   Trial até: " . ($subscription->trial_ends_at ? $subscription->trial_ends_at->format('d/m/Y H:i') : 'N/A') . "\n";
    echo "   Próxima cobrança: " . ($subscription->next_billing_date ? $subscription->next_billing_date->format('d/m/Y') : 'A definir') . "\n\n";
}

// 5. Verifica se pode operar
$canOperate = $billingService->canOperate($empresa->id);
echo "🔍 Verificação de operação:\n";
echo "────────────────────────────\n";
if ($canOperate['allowed']) {
    echo "✅ Empresa PODE operar\n";
} else {
    echo "❌ Empresa BLOQUEADA\n";
    echo "   Motivo: {$canOperate['reason']}\n";
}

// 6. Simula geração de fatura
echo "\n📄 Gerando fatura de teste...\n";
$subscription = $empresa->activeSubscription ?? Empresa::first()->activeSubscription;
if ($subscription) {
    $invoice = $billingService->generateInvoice(
        $subscription,
        now()->addDays(5)
    );
    
    echo "✅ Fatura gerada!\n";
    echo "   Número: {$invoice->invoice_number}\n";
    echo "   Valor: R$ " . number_format($invoice->amount_cents / 100, 2, ',', '.') . "\n";
    echo "   Vencimento: {$invoice->due_date->format('d/m/Y')}\n";
    echo "   Status: {$invoice->status}\n\n";
    
    // 7. Simula pagamento
    echo "💳 Simulando pagamento...\n";
    $billingService->markInvoicePaid($invoice->id, [
        'payment_method' => 'credit_card',
        'transaction_id' => 'TESTE-' . uniqid(),
        'paid_at' => now()
    ]);
    
    $invoice->refresh();
    echo "✅ Fatura PAGA!\n";
    echo "   Status: {$invoice->status}\n";
    echo "   Pago em: {$invoice->paid_at->format('d/m/Y H:i')}\n\n";
}

// 8. Simula processamento de cobrança (cron diário)
echo "⚙️  Processando cobranças (cron)...\n";
echo "────────────────────────────────────\n";

$result = $billingService->processOverdueInvoices();
echo "✅ Processamento concluído!\n";
echo "   Faturas marcadas como vencidas: {$result['overdue_marked']}\n";
echo "   Assinaturas em atraso: {$result['subscriptions_past_due']}\n";
echo "   Empresas suspensas: {$result['subscriptions_suspended']}\n\n";

echo "✅ Teste de cobrança concluído!\n";
