# Acessos de Demonstração e Guia Rápido (Cliente)

## URL de produção
- `https://tem-de-tudo.up.railway.app`

## Acessos por perfil
- **Admin**
  - Email: `admin.demo@temdetudo.com`
  - Senha: `DemoAdmin@2026!`
  - Uso: gestão geral (usuários, empresas, relatórios, conteúdo)
- **Empresa**
  - Email: `empresa.demo@temdetudo.com`
  - Senha: `DemoEmpresa@2026!`
  - Uso: operação do parceiro (ofertas, validação, clientes)
- **Cliente**
  - Email: `cliente.demo@temdetudo.com`
  - Senha: `DemoCliente@2026!`
  - Uso: saldo, histórico, recompensas e resgates

## Fluxo de validação recomendado (passo a passo)
1. Acesse `https://tem-de-tudo.up.railway.app/entrar.html`.
2. Faça login como **Cliente** e valide:
   - `Meus Pontos`
   - `Recompensas`
   - `Histórico`
3. Faça login como **Empresa** e valide:
   - `Clientes fidelizados`
   - `Minhas campanhas`
   - `Validar resgate`
4. Faça login como **Admin** e valide:
   - `Gestão de usuários`
   - `Gestão de estabelecimentos`
   - `Relatórios`
   - `Banners e categorias`

## Observações operacionais
- Esses acessos são garantidos automaticamente no deploy via comando:
  - `php artisan app:ensure-demo-access --sync-passwords`
- A validação visual de assets críticos também roda automaticamente:
  - `php artisan app:verify-frontend-assets --fix`
- Se quiser parar de sincronizar esses usuários em produção, ajustar no ambiente Railway:
  - `ENSURE_DEMO_ACCESS_ON_START=false`
  - ou `DEMO_ACCESS_ENABLED=false`

