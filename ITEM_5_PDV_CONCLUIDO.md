# ✅ Item 5: OPERAÇÃO RESGATE PDV - CONCLUÍDO

## 🏪 O que foi implementado:

### 1️⃣ Tabela de Intenções de Resgate
**Migration:** `2026_04_22_000004_create_redemption_intents_table.php`

**Estrutura:**
- ✅ `intent_id` (UUID público)
- ✅ Rastreamento completo: user_id, company_id, pdv_operator_id, pdv_terminal_id
- ✅ Controle de ledger: reserved_ledger_id, confirmed_ledger_id, reversal_ledger_id
- ✅ Estados: pending → reserved → confirmed/canceled/reversed
- ✅ Expiração automática (expires_at)
- ✅ Metadados: tipo de resgate, produto, valor, etc
- ✅ Auditoria: timestamps para cada etapa

---

### 2️⃣ Model RedemptionIntent

**Estados do resgate:**
- `pending` - Solicitado, aguardando reserva
- `reserved` - Pontos reservados (bloqueados temporariamente)
- `confirmed` - Resgate confirmado (pontos debitados)
- `canceled` - Cancelado antes de confirmar
- `reversed` - Estornado após confirmação
- `expired` - Reserva expirou

**Tipos de resgate:**
- `product` - Troca por produto
- `discount` - Desconto em compra
- `cashback` - Reembolso em dinheiro

**Helpers:**
- `canBeConfirmed()` - Verifica se pode confirmar (reserved + não expirado)
- `canBeCanceled()` - Verifica se pode cancelar (pending ou reserved)
- `canBeReversed()` - Verifica se pode estornar (confirmed)
- `isExpired()` - Verifica se reserva expirou

---

### 3️⃣ Service de Resgate

**RedemptionService.php** - Gerencia todo o ciclo de vida do resgate PDV

**Métodos principais:**

**1. requestRedemption()** - Solicita resgate e reserva pontos
```php
$intent = $redemptionService->requestRedemption(
    userId: 1,
    companyId: 1,
    points: 200,
    options: [
        'type' => 'product',
        'metadata' => ['produto' => 'Camiseta VIP'],
        'pdv_operator_id' => 5,
        'pdv_terminal_id' => 'PDV-001',
        'expires_minutes' => 15, // Default: 15 minutos
    ]
);
```

**2. confirmRedemption()** - Confirma resgate e debita pontos
```php
$intent = $redemptionService->confirmRedemption(
    intentId: 'uuid-do-intent',
    finalPoints: 200 // Opcional: pode ajustar quantidade
);
```

**3. cancelRedemption()** - Cancela e libera reserva
```php
$intent = $redemptionService->cancelRedemption(
    intentId: 'uuid-do-intent',
    reason: 'Cliente desistiu da compra'
);
```

**4. reverseRedemption()** - Estorna resgate confirmado (ADMIN)
```php
$intent = $redemptionService->reverseRedemption(
    intentId: 'uuid-do-intent',
    reason: 'Produto devolvido - defeito',
    reversedBy: auth()->id() // Admin que autorizou
);
```

**5. processExpiredReservations()** - Processa expiradas (CRON)
```php
$result = $redemptionService->processExpiredReservations();
// Retorna: ['total_expired' => 5, 'processed' => 5]
```

---

### 4️⃣ Controller e Rotas

**RedemptionController.php** - API REST completa

**Rotas protegidas (auth:sanctum):**
```
POST   /api/redemption/request              - Solicita resgate (rate: 20/min)
POST   /api/redemption/confirm              - Confirma resgate (rate: 20/min)
POST   /api/redemption/cancel               - Cancela resgate (rate: 20/min)
POST   /api/redemption/reverse              - Estorna resgate [ADMIN] (rate: 10/min)
GET    /api/redemption/{intentId}           - Detalhes do resgate (rate: 60/min)
GET    /api/redemption/user/{userId}        - Histórico do usuário (rate: 60/min)
GET    /api/redemption/company/{id}/pending - Resgates pendentes PDV (rate: 60/min)
```

---

### 5️⃣ Comando Cron

**ProcessExpiredRedemptions.php**

**Execução:**
```bash
php artisan redemptions:process-expired
```

**Saída:**
```
🔄 Processando reservas expiradas...
✅ Processamento concluído:
   - Total expiradas: 3
   - Processadas: 3
⚠️  3 reservas foram expiradas e liberadas
```

**Agendamento recomendado:**
```php
// app/Console/Kernel.php
$schedule->command('redemptions:process-expired')->everyFiveMinutes();
```

---

## 🔄 Fluxo Completo PDV

### Cenário 1: Resgate Bem-Sucedido
```
1. Cliente solicita resgate no PDV
   → redemptionService->requestRedemption()
   → Status: PENDING → RESERVED
   → Pontos RESERVADOS (bloqueados)

2. PDV valida e confirma
   → redemptionService->confirmRedemption()
   → Status: CONFIRMED
   → Pontos DEBITADOS
   → Reserva LIBERADA

3. Cliente recebe produto/desconto
```

### Cenário 2: Cliente Desiste
```
1. Cliente solicita resgate
   → Status: RESERVED
   → Pontos RESERVADOS

2. Cliente cancela antes de confirmar
   → redemptionService->cancelRedemption()
   → Status: CANCELED
   → Pontos LIBERADOS (volta para saldo disponível)
```

### Cenário 3: Reserva Expira
```
1. Cliente solicita resgate (expires_at = +15min)
   → Status: RESERVED
   → Pontos RESERVADOS

2. Passam-se 15 minutos sem confirmação

3. Cron executa processExpiredReservations()
   → Status: CANCELED
   → Pontos LIBERADOS automaticamente
```

### Cenário 4: Estorno Admin
```
1. Resgate já confirmado
   → Status: CONFIRMED
   → Pontos DEBITADOS

2. Cliente devolve produto defeituoso
   → Admin solicita estorno
   → redemptionService->reverseRedemption()
   → Status: REVERSED
   → Pontos CREDITADOS de volta
```

---

## 🚀 Integração com App/PDV

### Fluxo Mobile (Cliente)
```typescript
// 1. Cliente vê saldo e escolhe produto
const saldo = await api.get('/fidelidade/cartao');
const produto = { nome: 'Camiseta VIP', pontos: 200 };

// 2. Gera QR Code ou mostra intent_id
const intent = await api.post('/redemption/request', {
    user_id: user.id,
    company_id: empresa.id,
    points: produto.pontos,
    type: 'product',
    metadata: { produto: produto.nome },
    expires_minutes: 15
});

// 3. Cliente mostra intent_id (ou QR Code) no PDV
showQRCode(intent.data.intent_id);
```

### Fluxo PDV (Operador)
```typescript
// 1. PDV escaneia QR Code do cliente
const intentId = scanQRCode();

// 2. Busca detalhes do resgate
const intent = await api.get(`/redemption/${intentId}`);

if (intent.data.is_expired) {
    alert('Resgate expirado!');
    return;
}

// 3. Valida e confirma
await api.post('/redemption/confirm', {
    intent_id: intentId
});

alert('Resgate confirmado! Entregue o produto.');
```

### Painel Admin (Estorno)
```typescript
// 1. Cliente retorna com produto defeituoso
const intent = await api.get(`/redemption/${intentId}`);

// 2. Admin autoriza estorno
await api.post('/redemption/reverse', {
    intent_id: intentId,
    reason: 'Produto com defeito - estoque danificado'
});

alert('Pontos devolvidos ao cliente!');
```

---

## 📊 Monitoramento

### Resgates pendentes do dia
```sql
SELECT 
    ri.intent_id,
    u.name as cliente,
    e.nome as empresa,
    ri.points_requested,
    ri.status,
    ri.expires_at
FROM redemption_intents ri
JOIN users u ON u.id = ri.user_id
JOIN empresas e ON e.id = ri.company_id
WHERE ri.status IN ('pending', 'reserved')
  AND DATE(ri.created_at) = CURDATE()
ORDER BY ri.expires_at ASC;
```

### Taxa de conversão PDV
```sql
SELECT 
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmados,
    COUNT(CASE WHEN status = 'canceled' THEN 1 END) as cancelados,
    COUNT(CASE WHEN status = 'reserved' THEN 1 END) as pendentes,
    ROUND(
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) * 100.0 / 
        COUNT(*), 
        2
    ) as taxa_conversao
FROM redemption_intents
WHERE DATE(created_at) = CURDATE();
```

### Tempo médio de confirmação
```sql
SELECT 
    AVG(TIMESTAMPDIFF(SECOND, reserved_at, confirmed_at)) as tempo_medio_segundos
FROM redemption_intents
WHERE status = 'confirmed'
  AND DATE(created_at) = CURDATE();
```

---

## ✅ Validações de Teste

**Arquivo:** `test_redemption.php`

**Testes executados:**
1. ✅ Solicitar resgate - 200 pontos reservados
2. ✅ Confirmar resgate - 200 pontos debitados
3. ✅ Cancelar resgate - 150 pontos liberados
4. ✅ Estornar resgate - 200 pontos creditados de volta
5. ✅ Processar expirados - 0 encontrados (todos dentro do prazo)
6. ✅ Auditoria ledger - válido, sem discrepâncias

**Saldo final:** 500 pontos (mesmo saldo inicial)

---

## 📈 Impacto

**Antes:**
- ❌ Sem controle de reserva de pontos
- ❌ Risco de usar pontos já comprometidos
- ❌ Sem rastreamento de resgates PDV
- ❌ Impossível estornar resgates

**Depois:**
- ✅ Reserva temporária de pontos (anti-duplo uso)
- ✅ Expiração automática (15 min default)
- ✅ Rastreamento completo (operador + terminal)
- ✅ Estorno admin com auditoria
- ✅ 3 tipos de resgate (produto, desconto, cashback)
- ✅ Histórico completo por cliente/empresa
- ✅ Rate limiting em todas as rotas
- ✅ Integração com ledger imutável

---

## 🎯 Próximo passo

**Item 6:** Observabilidade produção (alertas/tracing/métricas)
