# Plano Fechado de Fidelidade (Aplicado)

## Objetivo
Consolidar o fluxo de fidelidade com politica por empresa, onboarding operacional mensuravel e endpoints claros para operacao em producao.

## Escopo P0 executado
- Motor de politica efetiva por empresa (override + fallback global).
- Persistencia de configuracao (`company_loyalty_configs`).
- Endpoints para empresa configurar politica e acompanhar onboarding.
- Endpoints admin para gerenciar politica de qualquer empresa.
- Integracao da politica no calculo de pontos e custo de resgate.
- Suite de testes de integracao para os novos fluxos.

## Endpoints novos
- `GET /api/empresa/fidelidade/config`
- `PUT /api/empresa/fidelidade/config`
- `GET /api/empresa/fidelidade/onboarding`
- `GET /api/admin/empresas/{companyId}/fidelidade/config`
- `PUT /api/admin/empresas/{companyId}/fidelidade/config`
- `GET /api/admin/empresas/{companyId}/fidelidade/onboarding`

## Regras aplicadas
- Politica global continua valida por padrao.
- Se a empresa tiver override ativo, a politica da empresa prevalece.
- Custo de resgate respeita:
  - `pontos_necessarios` / `custo_pontos` da promocao quando existir.
  - Fallback em `desconto * redeem_points_per_currency`.
  - Piso minimo em `min_redeem_points`.
- Checkout de onboarding passa a ter checklist objetivo com progresso.

## Checklist de aceite tecnico
- [x] Config por empresa persistida e versionada por migration.
- [x] Endpoints protegidos por perfil (`empresa` / `admin`).
- [x] `/api/fidelidade/programa?empresa_id=` reflete override da empresa.
- [x] Onboarding com `is_ready`, `progress_percent`, `checks`, `next_actions`.
- [x] Testes automatizados cobrindo os cenarios principais.
