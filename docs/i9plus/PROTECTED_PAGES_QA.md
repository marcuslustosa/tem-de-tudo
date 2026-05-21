# Protected Pages QA

## Resumo

Esta rodada validou o comportamento das páginas principais com foco em:

- evitar `pisca e sai` em páginas protegidas;
- diferenciar `401` de `403`;
- manter empresa ativa na tela;
- mostrar bloqueio claro para empresa `pending` e `suspended`;
- impedir que erro secundário de ferramenta derrube a página inteira.

## Causa raiz consolidada

O problema não estava só em `gest_o_de_ofertas_parceiro.html`.

As causas compartilhadas eram:

1. `auth.ensure()` tratava falhas de autenticação e autorização de forma agressiva demais, redirecionando para login sem preservar retorno nem diferenciar `401` de `403`.
2. `auth.guard()` não deixava mensagem contextual quando o perfil estava errado.
3. Páginas de empresa dependiam de endpoints protegidos por `subscription.check` e não tratavam `403` de empresa `pending`/`suspended`.
4. Falhas em endpoints secundários (`/empresa/promocoes`, `/empresa/qrcodes`, `/empresa/clientes`, `/empresa/relatorios/resumo`) podiam derrubar a experiência inteira em vez de isolar o erro no card/ferramenta.

## Tabela de QA

| Página | Perfil esperado | Sem login | Perfil errado | Perfil certo | Endpoints principais | Erros encontrados | Correção aplicada | Status final |
|---|---|---|---|---|---|---|---|---|
| `dashboard_parceiro.html` | `empresa` | HTML responde `200`, JS redireciona para login com `next` preservado | cliente/admin recebem aviso e saem da rota protegida | empresa ativa permanece na página | `/api/auth/me`, `/api/empresa/promocoes`, `/api/empresa/relatorios/resumo`, `/api/empresa/qrcodes` | redirecionamento genérico e perda de contexto em `403` | `auth.ensure`, `auth.guard` e `handleCompanyAccessFailure` | `ok` |
| `gest_o_de_ofertas_parceiro.html` | `empresa` | HTML responde `200`, JS envia para login com retorno | cliente/admin recebem restrição clara | empresa ativa permanece e carrega promoções/ofertas | `/api/auth/me`, `/api/empresa/promocoes`, `/api/empresa/bonus-adesao`, `/api/empresa/cartao-fidelidade`, `/api/empresa/bonus-aniversario`, `/api/empresa/lembrete-retorno`, `/api/empresa/promocoes/{id}/enviar` | era a tela mais sensível ao `pisca e sai`; falhas de carga podiam derrubar tudo | guarda por perfil, tratamento de `403` de empresa, feedback inline e resumo operacional de push | `ok` |
| `validar_resgate.html` | `empresa` ou `cliente` conforme modo | HTML responde `200`, JS pede login quando necessário | perfil errado recebe fluxo compatível ou aviso | empresa ativa permanece no modo operacional; cliente permanece no modo QR próprio/vínculo | `/api/auth/me`, `/api/empresa/qrcodes`, `/api/empresa/clientes/qrcode/consultar`, `/api/cliente/meu-qrcode` e endpoints de validação | QR/consulta podia falhar e contaminar a tela | tratamento defensivo por modo e fallback por bloco | `ok` |
| `clientes_fidelizados_loja.html` | `empresa` | HTML responde `200`, JS redireciona com retorno | cliente/admin recebem aviso | empresa ativa permanece | `/api/auth/me`, `/api/empresa/clientes` | lista dependia de empresa operacional | mesmo guard compartilhado + tratamento de acesso operacional | `ok` |
| `meus_pontos.html` | `cliente` | HTML responde `200`, JS redireciona para login | empresa/admin recebem aviso e são levados ao destino correto | cliente permanece e carrega dashboard | `/api/auth/me`, `/api/cliente/dashboard`, `/api/cliente/meu-qrcode`, `/api/push/public-key`, `/api/push/subscribe`, `/api/push/unsubscribe` | textos quebrados no card de push e possível perda de retorno | copy corrigida + card de push visível + redirecionamento com `next` | `ok` |
| `meu_perfil.html` | `cliente` | HTML responde `200`, JS redireciona para login | empresa/admin recebem aviso | cliente permanece | `/api/auth/me`, `/api/perfil`, `/api/push/public-key`, `/api/push/subscribe`, `/api/push/unsubscribe` | card de push precisava permanecer resiliente | guard compartilhado + status claros de push | `ok` |
| `parceiros_tem_de_tudo.html` | público / cliente | pode abrir sem login | não se aplica como bloqueio rígido | cliente usa normalmente | `/api/empresas` e fluxo de navegação para detalhe/vínculo | sem bloqueio crítico | mantida como rota pública, sem transformar área protegida em pública | `ok` |
| `detalhe_do_parceiro.html` | público / cliente | pode abrir sem login | não se aplica como bloqueio rígido | cliente vê benefícios condicionados ao vínculo | `/api/empresas/{id}`, `/api/empresas/{id}/avaliacoes`, endpoints cliente quando autenticado | dependia de fallbacks robustos para dados demo | mantidos fallbacks e sem auto-resgate | `ok` |
| `dashboard_admin_master.html` | `admin` | HTML responde `200`, JS redireciona para login | cliente/empresa recebem aviso e saem da rota | admin permanece | `/api/auth/me`, `/api/admin/dashboard-stats`, `/api/admin/recent-activity`, `/api/admin/relatorios/resumo` | antigo fluxo não distinguia bem perfil errado | `auth.guard` contextualizado | `ok` |
| `gest_o_de_estabelecimentos.html` | `admin` | HTML responde `200`, JS redireciona para login | cliente/empresa recebem aviso e saem da rota | admin permanece | `/api/auth/me`, `/api/admin/empresas` | dependia de comportamento consistente de admin | `auth.guard` contextualizado | `ok` |
| `relat_rios_gerais_master.html` | `admin` | HTML responde `200`, JS redireciona para login | cliente/empresa recebem aviso e saem da rota | admin permanece | `/api/auth/me`, `/api/admin/relatorios/resumo`, relatórios auxiliares | risco de retorno para login sem contexto | guard compartilhado com redirecionamento correto | `ok` |

## Validação de API feita nesta rodada

### Empresa ativa (`malagueta@demo.local`)

- `GET /api/empresa/promocoes` -> `200`
- `GET /api/empresa/dashboard` -> `200`
- `GET /api/empresa/qrcodes` -> `200`
- `GET /api/empresa/clientes` -> `200`

### Perfil errado

- cliente em `/api/empresa/promocoes` -> `403` `Acesso negado. Perfil insuficiente.`
- admin em `/api/empresa/promocoes` -> `403` `Acesso negado. Perfil insuficiente.`
- empresa em `/api/cliente/dashboard` -> `403` `Acesso negado. Perfil insuficiente.`

### Sem login

- sem token em `/api/empresa/promocoes` -> `401` `Unauthenticated.`

### Empresa pendente

- `GET /api/empresa/promocoes` -> `403` `Cadastro da empresa pendente de aprovacao administrativa.`
- `GET /api/empresa/dashboard` -> `403` `Cadastro da empresa pendente de aprovacao administrativa.`
- `GET /api/empresa/qrcodes` -> `403` `Cadastro da empresa pendente de aprovacao administrativa.`
- `GET /api/empresa/clientes` -> `403` `Cadastro da empresa pendente de aprovacao administrativa.`

### Empresa suspensa

- `GET /api/empresa/promocoes` -> `403` `Empresa suspensa. Regularize a situacao para voltar a operar.`
- `GET /api/empresa/dashboard` -> `403` `Empresa suspensa. Regularize a situacao para voltar a operar.`
- `GET /api/empresa/qrcodes` -> `403` `Empresa suspensa. Regularize a situacao para voltar a operar.`
- `GET /api/empresa/clientes` -> `403` `Empresa suspensa. Regularize a situacao para voltar a operar.`

## Observações finais

- As páginas protegidas continuam sendo shells HTML públicos, mas o acesso operacional real acontece pelo guard do frontend e pelos endpoints protegidos.
- O comportamento corrigido agora é:
  - `401` -> login;
  - `403` por perfil -> aviso de acesso restrito;
  - `403` por status da empresa -> mensagem operacional específica;
  - falha secundária de ferramenta -> erro localizado no card/área da ferramenta.
