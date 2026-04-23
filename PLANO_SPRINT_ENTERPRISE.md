# 🎯 PLANO ENTERPRISE - 2 SPRINTS

## 📋 SPRINT 1: FUNDAÇÃO COMERCIAL + AUDITORIA (5-7 dias)

### ✅ Item 2: Ledger Unificado de Pontos (CRÍTICO)
**Status:** 🚧 EM IMPLEMENTAÇÃO

**Problema atual:**
- 3 sistemas de pontos diferentes (tabelas `pontos`, `ponto_transacoes`, campo `users.pontos`)
- Risco de inconsistência e divergência de saldo
- Sem idempotência (risco de duplicação em retry)
- Sem auditoria completa

**Solução:**
- ✅ Tabela `ledger` única e imutável
- ✅ Idempotency key para prevenir duplicação
- ✅ Balance snapshot em cada transação
- ✅ LedgerService centralizado para TODAS operações
- ✅ Migration de consolidação de dados antigos
- ✅ Triggers/observers para manter consistência

**Arquivos:**
- `database/migrations/2026_04_22_create_ledger_table.php`
- `app/Models/Ledger.php`
- `app/Services/LedgerService.php`
- `app/Observers/LedgerObserver.php`

---

### 🔄 Item 1: Sistema de Cobrança de Empresas
**Status:** ⏳ PRÓXIMO

**Implementação:**
- Tabela `subscriptions` (empresa_id, plan, status, started_at, expires_at, billing_day)
- Tabela `invoices` (subscription_id, amount, status, due_date, paid_at, payment_url)
- Tabela `subscription_plans` (name, monthly_price, features_json, max_users, max_transactions)
- Service de cobrança integrado com MercadoPago/Stripe
- Bloqueio automático de empresas inadimplentes (middleware)
- Webhook de pagamento atualiza invoice e desbloqueia
- Relatório de inadimplência no admin

**Fluxo:**
1. Empresa cadastrada inicia trial 14 dias (plano básico)
2. Após trial, gera fatura automática no dia do vencimento
3. Se não pagar em 7 dias, bloqueia acesso (exceto consulta)
4. Notificação por email/push nos dias -3, -1, 0, +3, +7
5. Admin pode conceder extensão manual

---

### 🔒 Item 7: Segurança Operacional
**Status:** ⏳ PENDENTE

**Ações:**
- ✅ Rotacionar DB password (usar env + secrets manager)
- ✅ Rotacionar JWT secret (invalidar tokens antigos)
- ✅ Remover SETUP_TOKEN hardcoded
- ✅ Usar bcrypt para API keys
- ✅ Rate limiting por IP/endpoint
- ✅ CORS configurado para produção
- ✅ Helmet.js / Security headers

---

## 📋 SPRINT 2: OPERAÇÃO + PROTEÇÃO (5-7 dias)

### 🛡️ Item 3: Anti-Fraude Robusto
**Status:** ⏳ PENDENTE

**Regras:**
- Limite de transações por device_id/IP/hora (ex: 10 acúmulos/hora)
- Geofencing: validar lat/long da empresa vs device
- Blacklist de devices/IPs suspeitos
- Análise de comportamento: velocidade de uso, padrões atípicos
- Aprovação manual acima de X pontos
- Reversão segura com ledger_reversal

**Tabelas:**
- `fraud_rules` (rule_type, threshold, action)
- `fraud_alerts` (user_id, rule_triggered, metadata, resolved_at)
- `device_fingerprints` (user_id, device_id, ip, user_agent, trusted)

---

### 💰 Item 4: Operação de Resgate PDV
**Status:** ⏳ PENDENTE

**Fluxo completo:**
1. Cliente solicita resgate (cria `redemption_intent` com status=pending)
2. Sistema **reserva** pontos (ledger com type=reserved, balance não muda ainda)
3. Caixa/PDV valida e confirma (ledger type=redeem, balance atualiza)
4. Ou cancela (ledger type=released, desfaz reserva)
5. Estorno até X dias (ledger type=reversal, com motivo)
6. Conciliação financeira: custo real da campanha vs pontos usados

**Tabelas:**
- `redemption_intents` (id, user_id, promo_id, points, status, expires_at)
- Ledger já suporta type=reserved/released/redeem/reversal

---

### 📊 Item 5: Observabilidade de Produção
**Status:** ⏳ PENDENTE

**Ferramentas:**
- Sentry/Rollbar para exception tracking
- CloudWatch/DataDog para métricas (latency, throughput, error rate)
- Queue monitoring (Redis/SQS pending jobs)
- APM tracing (identificar gargalos)
- Alertas no Slack/PagerDuty para:
  - Error rate > 1%
  - P95 latency > 2s
  - Queue depth > 1000
  - Failed jobs > 50/min

---

### 🧪 Item 6: Testes E2E Automáticos
**Status:** ⏳ PENDENTE

**Cobertura:**
- Fluxo cliente: registro → acúmulo → resgate → consulta extrato
- Fluxo loja: login → scan QR → creditar pontos → validar resgate
- Fluxo admin: criar campanha → aprovar ticket → ajustar pontos
- Cypress/Playwright para testes de interface
- PHPUnit/Pest para testes de API
- CI/CD rodando testes antes de deploy (GitHub Actions)

---

## 🎯 ORDEM DE IMPLEMENTAÇÃO

**Agora (próximas 2h):**
1. ✅ Criar tabela ledger + migration
2. ✅ LedgerService com idempotência
3. ✅ Migrar WalletController para usar Ledger
4. ✅ Consolidar dados antigos

**Hoje (próximas 4h):**
5. Sistema de cobrança (subscriptions + invoices)
6. Middleware de bloqueio de inadimplentes
7. Rotacionar segredos expostos

**Amanhã:**
8. Anti-fraude básico (rate limit + device tracking)
9. Resgate PDV com reserva
10. Observabilidade inicial (Sentry)

**Sprint 2:**
11. Testes E2E automatizados
12. Conciliação financeira
13. Dashboard de métricas

---

## ✅ CRITÉRIOS DE SUCESSO

**Ledger:**
- Zero inconsistências em auditoria de 1000 transações
- Idempotência testada (retry não duplica)
- Performance: <100ms para creditar/debitar

**Cobrança:**
- 100% das empresas com assinatura ativa ou bloqueada
- Faturas geradas automaticamente
- Notificações funcionando

**Anti-fraude:**
- Detectar e bloquear 1 device malicioso em teste
- Rate limit funcionando (429 após limite)

**Produção:**
- Zero senhas/tokens hardcoded
- Alertas configurados e testados
- 80% coverage de testes E2E em fluxos críticos

---

**Início:** 22/04/2026  
**Previsão de conclusão:** 05/05/2026  
**Responsável:** Sistema Tem de Tudo - Time de Produto
