# API Tem de Tudo (Resumo Operacional)

## Autenticacao
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/logout` (auth:sanctum)
- `GET /api/me` (auth:sanctum)

## Regras Gerais
- Rotas de escrita criticas usam `X-Idempotency-Key`.
  - Em `APP_ENV=production`, a chave e obrigatoria quando `IDEMPOTENCY_REQUIRE_IN_PRODUCTION=true`.
- Rotas de empresa usam `subscription.check` para governanca de assinatura.
- Todas as rotas privadas exigem token Sanctum.

## Fidelidade (Wallet)
- `GET /api/fidelidade/cartao`
- `GET /api/fidelidade/historico`
- `POST /api/fidelidade/resgatar` (idempotente)
- `POST /api/fidelidade/adicionar-pontos` (idempotente)
- `POST /api/fidelidade/validar-qrcode`

## Resgate PDV
- `POST /api/redemption/request` (idempotente)
- `POST /api/redemption/confirm` (idempotente)
- `POST /api/redemption/cancel` (idempotente)
- `POST /api/redemption/reverse` (idempotente, admin)
- `GET /api/redemption/{intentId}`
- `GET /api/redemption/user/{userId}`
- `GET /api/redemption/company/{companyId}/pending`

## Billing
- Rotina scheduler: `billing:process`
- Entidades principais:
  - `subscription_plans`
  - `subscriptions`
  - `invoices`
  - `billing_notifications`
  - `billing_events`
- Rotinas adicionais:
  - retry/dunning (`processPaymentRetries`)
  - reconciliacao (`reconcilePendingInvoices`)

## Privacidade (LGPD)
- `GET /api/privacy/status`
- `PUT /api/privacy/consent`
- `POST /api/privacy/export`
- `GET /api/privacy/export/{privacyRequestId}/download`
- `POST /api/privacy/delete-account`

## OpenAPI Versionado
- `GET /api/docs/openapi`
- `GET /api/docs/openapi/v1`
- `GET /api/docs/openapi/v2`

## Observabilidade
- `GET /api/ping`
- `GET /api/health`
- `GET /api/metrics` (rate limit)

## Notas de seguranca
- CORS restrito para `api/*` e `sanctum/csrf-cookie`.
- Logs de request com `request_id` e sem dump de payload sensivel.
- Excecoes com captura opcional via Sentry (`SENTRY_LARAVEL_DSN`).
