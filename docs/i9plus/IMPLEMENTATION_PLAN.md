# Plano de implementacao

## Diretriz geral

O plano parte da base real encontrada e da estabilizacao executada na Fase 0:

- Laravel e o backend canonico.
- `backend/api` fica fora do caminho principal.
- QR canonico:
  - cliente via `ClienteQrCodeService`
  - empresa via `qr_codes.code`
- Status operacional canonico de empresa:
  - `pending`
  - `active`
  - `suspended`
  - `rejected`

Regra de execucao:

- adaptar incrementalmente a base atual;
- evitar nova aplicacao paralela;
- preferir estender endpoints, models e telas existentes;
- validar cada fase antes de abrir a proxima.

## Fase 0 - Estabilizacao tecnica

### Objetivo

Reduzir risco tecnico antes da Fase 1 sem abrir ainda funcionalidades novas.

### Ajustes executados

- Laravel mantido como base canonica.
- `backend/api` mantido, mas fora do caminho principal.
- `empresas.status` estabilizado.
- filtros publicos de empresa alinhados a `status = active`.
- QR da empresa normalizado como token opaco persistido.
- QR do cliente mantido como token assinado e temporario.
- `manifest.json` corrigido para paginas existentes.
- testes de contrato adicionados para QR da empresa, status publico e manifest.

### Arquivos tocados

- `backend/app/Models/Empresa.php`
- `backend/app/Models/QRCode.php`
- `backend/app/Services/QRCodeService.php`
- `backend/app/Http/Controllers/QRCodeController.php`
- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Http/Controllers/API/ClienteAPIController.php`
- `backend/app/Http/Controllers/API/EmpresaController.php`
- `backend/database/factories/EmpresaFactory.php`
- `backend/database/migrations/2026_05_07_000000_add_operational_status_to_empresas_table.php`
- `backend/public/manifest.json`

### Como testar

- `php artisan migrate`
- `php artisan test tests/Feature/ClienteQrCodeServiceTest.php`
- `php artisan test tests/Feature/CompanyQrCodeServiceTest.php`
- `php artisan test tests/Feature/CompanyOperationalStatusTest.php`
- `php artisan test tests/Feature/ManifestContractsTest.php`
- `php artisan test tests/Feature/PainelSmokeTest.php`

### Riscos remanescentes

- ainda existem rotas/endpoints legados de QR coexistindo com o caminho canonico;
- o fluxo de aprovacao de empresa ainda nao foi implementado;
- o cadastro publico do cliente ainda nao coleta `data_nascimento`;
- o PWA segue sem estrategia offline robusta.

### Criterios de saida

- base pronta para iniciar a Fase 1 sem segunda arquitetura;
- empresa nao publica se nao estiver `active`;
- QR do cliente e QR da empresa com caminho canonico definido;
- manifest sem referencias quebradas obvias.

## Fase 1 - Base, roles e aprovacao de empresa

### Objetivo

Fechar a base de autorizacao e governanca para o novo produto:

- consolidar `customer`, `company` e `admin` sobre `cliente`, `empresa` e `admin`;
- permitir cadastro de empresa em estado pendente;
- impedir exposicao publica e acesso operacional completo antes de aprovacao;
- explicitar protecao de rotas por papel e por status.

### Status de execucao

- Executada em cima da base Laravel canonica.
- Nenhum fluxo Node/Express foi usado.
- Nenhuma migration nova foi criada nesta fase.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Http/Middleware/CheckCompanySubscription.php`
- `backend/routes/api.php`
- `backend/public/criar_conta.html`
- `backend/public/gest_o_de_estabelecimentos.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `backend/tests/Feature/PublicCompanyRegistrationTest.php`
- `backend/tests/Feature/AdminCompanyApprovalFlowTest.php`

### Migrations da fase

- Nenhuma.
- A fase reutilizou:
  - `empresas.status`
  - `empresas.owner_id`
  - `users.status`
  - `qr_codes`

### Endpoints criados ou alterados

- Alterado:
  - `POST /api/auth/register`
    - empresa publica agora entra como `pending`
- Criados:
  - `GET /api/admin/empresas`
  - `POST /api/admin/empresas/{id}/approve`
  - `POST /api/admin/empresas/{id}/reject`
  - `POST /api/admin/empresas/{id}/suspend`
- Preservado por compatibilidade:
  - `PATCH /api/admin/empresas/{id}/toggle-status`
- Protegido operacionalmente:
  - rotas `/api/empresa/*` com `role.permission:empresa` + `subscription.check`
  - agora tambem bloqueadas por status de empresa nao ativa

### Componentes/telas efetivamente adaptados

- `criar_conta.html`
  - agora aceita solicitacao publica de empresa
  - reaproveitando a mesma tela de cadastro existente
- `gest_o_de_estabelecimentos.html`
  - agora opera com pendente, ativa, suspensa e rejeitada
- `stitch-app.js`
  - passou a chamar os endpoints admin canonicos da fase

### Riscos

- ainda existe script inline legado em `criar_conta.html` apenas neutralizado, nao removido;
- nao ha metadados ricos de aprovacao/rejeicao por empresa nesta fase;
- o painel admin continua funcional, mas ainda sem fluxo visual dedicado de revisao detalhada;
- a minificacao do bundle publico exige cuidado para manter `public/dist/stitch-app.min.js` sincronizado.

### Criterios de aceite

- empresa consegue se cadastrar e entra como `pending`;
- empresa `pending` nao aparece publicamente;
- admin consegue aprovar, rejeitar e suspender;
- rotas de empresa exigem papel correto e status valido.

### Como testar

- cadastrar empresa publica em `/criar_conta.html`;
- validar `users.status = pendente`, `empresas.status = pending`, `empresas.ativo = false`;
- confirmar que a empresa nao aparece em `/api/empresas`;
- autenticar admin e aprovar via `/gest_o_de_estabelecimentos.html` ou `POST /api/admin/empresas/{id}/approve`;
- validar `users.status = ativo`, `empresas.status = active`, `empresas.ativo = true`;
- validar que a empresa passa a aparecer publicamente;
- suspender ou rejeitar e confirmar nova ocultacao publica;
- testar acesso a `/api/empresa/dashboard` com empresa nao ativa e esperar `403`.

### Dependencias

- Fase 0.

## Fase 2 - QR Codes e vinculo

### Objetivo

Tornar o QR Code o eixo central do produto:

- QR seguro da empresa;
- QR seguro do cliente;
- leitura do QR da empresa pelo cliente;
- vinculo cliente/empresa;
- pagina publica da empresa;
- home do cliente orientada por QR e empresas vinculadas.

### Status de execucao

- Executada em cima do Laravel canonico.
- Nenhuma migration nova foi criada.
- Nenhum fluxo Node/Express foi usado.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/API/ClienteAPIController.php`
- `backend/app/Http/Controllers/API/EmpresaAPIController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Services/QRCodeService.php`
- `backend/routes/api.php`
- `backend/public/js/stitch-app.js`
- `backend/public/validar_resgate.html`
- `backend/public/entrar.html`
- `backend/public/meus_pontos.html`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/vincular_empresa.html`
- `backend/tests/Feature/Phase2QrCompanyLinkFlowTest.php`

### Migrations da fase

- Nenhuma.
- A fase reutilizou:
  - `qr_codes`
  - `inscricoes_empresa`
  - `empresas.status`
  - `empresas.ativo`

### Endpoints criados ou alterados

- Criado:
  - `GET /api/qrcode/empresa/{code}`
- Criado:
  - `POST /api/cliente/vincular-empresa-qrcode`
- Adaptado:
  - `GET /api/cliente/dashboard`
- Reutilizado:
  - `GET /api/cliente/meu-qrcode`
- Adaptado:
  - `GET /api/empresa/qrcodes`
- Criado:
  - `GET /api/admin/empresas/{id}/qrcode`
- Mantidos com filtro publico:
  - `GET /api/empresas`
  - `GET /api/empresas/{id}`

### Models e services efetivamente usados

- Models:
  - `Empresa`
  - `QRCode`
  - `InscricaoEmpresa`
- Services:
  - `ClienteQrCodeService`
  - `QRCodeService`

### Componentes frontend efetivamente adaptados

- Home do cliente:
  - `meus_pontos.html`
  - saudacao
  - `Ler QR Code`
  - `Meu QR Code`
  - empresas vinculadas
  - empresas em destaque
- Pagina publica da empresa:
  - `detalhe_do_parceiro.html`
- Pagina de aterrissagem do QR da empresa:
  - `vincular_empresa.html`
- Scanner compartilhado:
  - `validar_resgate.html`
- Redirecionamento pos-login:
  - `entrar.html`

### Riscos

- coexistencia de rotas legadas de QR ainda nao removidas;
- scanner compartilhado entre fluxos antigos e novos;
- `stitch-app.js` segue concentrando muito comportamento legado;
- pagina publica e home cliente ainda nao usam todo o contrato visual final de tokens/CSS.

### Criterios de aceite

- empresa ativa possui QR seguro e reutilizavel;
- QR da empresa aponta para `/vincular_empresa.html?code={token}`;
- cliente possui QR proprio via `ClienteQrCodeService`;
- cliente autenticado consegue se vincular sem duplicidade;
- vinculo fica persistido em `inscricoes_empresa`;
- home do cliente mostra empresas vinculadas;
- pagina publica da empresa respeita `active + ativo = true`.

### Como testar

- autenticar empresa e validar retorno de `GET /api/empresa/qrcodes`;
- autenticar admin e validar `GET /api/admin/empresas/{id}/qrcode`;
- autenticar cliente e validar `GET /api/cliente/meu-qrcode`;
- autenticar cliente e executar `POST /api/cliente/vincular-empresa-qrcode` com:
  - token puro;
  - URL publica completa do QR;
- validar `GET /api/cliente/dashboard`;
- validar bloqueio publico para empresas nao ativas.

### Dependencias

- Fase 0
- Fase 1

## Fase 3 - Bonus de adesao e validacao

### Objetivo

Entregar bonus de adesao com uso unico e validacao obrigatoria pela empresa.

### Status de execucao

- Executada em cima do Laravel canonico.
- Nenhum fluxo Node/Express foi usado.
- O resgate pelo cliente foi explicitamente bloqueado.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/BonusAdesaoController.php`
- `backend/app/Models/BonusAdesao.php`
- `backend/app/Models/BonusAdesaoResgate.php`
- `backend/app/Services/BonusAdesaoService.php`
- `backend/database/migrations/2026_05_07_100000_add_phase3_fields_to_bonus_adesao_table.php`
- `backend/database/migrations/2026_05_07_100100_normalize_bonus_adesaos_as_bonus_adesao_resgates.php`
- `backend/routes/api.php`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `backend/tests/Feature/BonusAdesaoPhase3FlowTest.php`

### Migrations da fase

- Criada:
  - `2026_05_07_100000_add_phase3_fields_to_bonus_adesao_table.php`
- Criada:
  - `2026_05_07_100100_normalize_bonus_adesaos_as_bonus_adesao_resgates.php`
- Criada:
  - `2026_05_07_130000_rename_bonus_adesaos_to_bonus_adesao_resgates_table.php`

Objetivo real das migrations:

- estender `bonus_adesao` sem quebrar o schema legado;
- renomear e normalizar `bonus_adesao_resgates` como trilha canonica de resgate/validacao;
- garantir unicidade de resgate por `bonus_id + user_id`.

### Endpoints criados ou alterados

- Empresa:
  - `GET /api/empresa/bonus-adesao`
  - `POST /api/empresa/bonus-adesao`
  - `GET /api/empresa/bonus-adesao/{id}`
  - `PUT /api/empresa/bonus-adesao/{id}`
  - `DELETE /api/empresa/bonus-adesao/{id}`
  - `PATCH /api/empresa/bonus-adesao/{id}/toggle`
  - `POST /api/empresa/clientes/qrcode/consultar`
  - `POST /api/empresa/bonus-adesao/{id}/validar`
- Cliente:
  - `GET /api/cliente/bonus-adesao/disponivel/{empresa_id}`
  - `POST /api/cliente/bonus-adesao/resgatar/{empresa_id}`
    - adaptado para bloquear auto-resgate

### Models e services efetivamente usados

- Models:
  - `BonusAdesao`
  - `BonusAdesaoResgate`
  - `InscricaoEmpresa`
  - `Empresa`
  - `User`
- Services:
  - `BonusAdesaoService`
  - `ClienteQrCodeService`

### Decisao final de modelagem da Fase 3

- `bonus_adesao`
  - representa apenas configuracao do bonus da empresa;
  - nao guarda historico de resgate por cliente.
- `bonus_adesao_resgates`
  - representa o historico/controle canonico de resgate por cliente;
  - um resgate validado gera exatamente uma linha por `bonus_id + user_id`.
- `inscricoes_empresa.bonus_adesao_resgatado`
  - foi mantido apenas como compatibilidade legada;
  - nao deve ser tratado como unica fonte de verdade quando houver multiplas configuracoes de bonus no futuro.

### Componentes frontend efetivamente adaptados

- `detalhe_do_parceiro.html`
  - card de bonus de adesao;
  - status `disponivel`, `ja utilizado`, `expirado`, `indisponivel`, `exige vinculo`;
  - CTA para apresentar QR do cliente, sem concluir resgate.
- `gest_o_de_ofertas_parceiro.html`
  - painel de configuracao do bonus de adesao;
  - preview do card;
  - historico simples;
  - atalho para leitura do QR do cliente.
- `validar_resgate.html`
  - modo novo `bonus-adesao`;
  - consulta do cliente por QR;
  - botao de validacao pela empresa.
- `stitch-app.js`
  - orquestra:
    - consulta de bonus do cliente;
    - formulario da empresa;
    - painel de validacao por QR.

### Riscos

- `validar_resgate.html` continua sendo tela compartilhada entre bonus novo e fluxo legado;
- `QRCodeController` ainda guarda comportamento antigo de bonus por primeira visita e precisa ser tratado numa limpeza futura segura;
- a UX da empresa ainda vive na mesma pagina de ofertas, o que e pragmatico para esta fase, mas nao e o layout final do produto;
- `stitch-app.js` permanece centralizado e grande;
- a coexistencia temporaria do flag `inscricoes_empresa.bonus_adesao_resgatado` com a tabela canonica de resgates exige disciplina para nao reintroduzir logica ambigua em fases futuras.

### Criterios de aceite

- empresa ativa cria, edita e ativa/desativa bonus de adesao;
- empresa nao ativa nao consegue operar endpoints de bonus;
- cliente vinculado ve o bonus disponivel na pagina da empresa;
- cliente nao conclui resgate sozinho;
- empresa consulta o cliente pelo QR canonico;
- empresa valida o bonus uma unica vez;
- segunda validacao falha com erro claro;
- empresa nao valida bonus de outra empresa;
- empresa nao valida bonus de cliente nao vinculado.

### Como testar

- `php artisan migrate`
- empresa ativa:
  - abrir `/gest_o_de_ofertas_parceiro.html`;
  - criar bonus;
  - editar e alternar ativo/inativo.
- cliente:
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar estado do bonus;
  - tentar `POST /api/cliente/bonus-adesao/resgatar/{empresa}` e confirmar bloqueio.
- empresa:
  - abrir `/validar_resgate.html?modo=bonus-adesao`;
  - escanear QR do cliente;
  - validar bonus;
  - tentar validar novamente e confirmar erro;
  - validar em banco que o registro foi salvo em `bonus_adesao_resgates`, nao em `bonus_adesao`.

### Dependencias

- Fase 0
- Fase 1
- Fase 2

## Fase 3.1 - Saneamento tecnico do QR legado

### Objetivo

Isolar o fluxo legado de QR que ainda tratava `primeira_visita` e `bonus_adesao_resgatado`, para impedir conflito com a trilha canonica do bonus de adesao e preparar a Fase 4.

### Status de execucao

- Executada em cima do Laravel canonico.
- Nenhum fluxo Node/Express foi usado.
- Nenhuma feature nova de negocio foi aberta.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/QRCodeController.php`
- `backend/tests/Feature/LegacyQRCodeControllerSanitizationTest.php`
- `docs/i9plus/TECH_AUDIT.md`
- `docs/i9plus/IMPLEMENTATION_PLAN.md`

### Migrations da fase

- Nenhuma.

### Metodos legados encontrados

- `QRCodeController::escanearEmpresa`
  - fazia vinculo/check-in legado;
  - criava cupom de bonus de adesao;
  - marcava `bonus_adesao_resgatado`.
- `QRCodeController::escanearCliente`
  - fazia leitura legada do QR do cliente;
  - nao era a trilha canonica de bonus da Fase 3.

### Rotas afetadas

- Nenhuma rota ativa da Fase 2/3 precisou ser trocada.
- Confirmado em `routes/api.php`:
  - `QRCodeController::escanearEmpresa` nao possui rota ativa.
  - `QRCodeController::escanearCliente` nao possui rota ativa.
- Fluxos canonicos mantidos:
  - `POST /api/cliente/vincular-empresa-qrcode`
  - `POST /api/empresa/clientes/qrcode/consultar`
  - `POST /api/empresa/bonus-adesao/{id}/validar`
  - `POST /api/empresa/escanear-cliente`

### Isolamento aplicado

- `QRCodeController::escanearEmpresa`
  - marcado como deprecated;
  - nao gera mais `Cupom`;
  - nao grava mais resgate nem mexe em `bonus_adesao_resgatado` fora do `BonusAdesaoService`;
  - apenas cria/atualiza o vinculo legado e consulta o status canonico do bonus.
- `QRCodeController::escanearCliente`
  - marcado como deprecated;
  - exposto apenas como leitura legada do QR do cliente;
  - nao opera bonus de adesao.

### Criterios de aceite

- `QRCodeController` nao consegue mais concluir bonus de adesao por conta propria;
- `BonusAdesaoService` segue como unica trilha de validacao do bonus;
- `bonus_adesao_resgates` segue como fonte de verdade do historico;
- `bonus_adesao_resgatado` permanece apenas como compatibilidade/fallback;
- a Fase 3 continua funcional.

### Como testar

- executar `php artisan migrate`;
- executar o teste de regressao do legado de QR;
- validar manualmente que o uso legado de `QRCodeController::escanearEmpresa`:
  - nao grava em `bonus_adesao_resgates`;
  - nao muda `inscricoes_empresa.bonus_adesao_resgatado` para `true`;
  - apenas devolve status do bonus com metadados de fluxo depreciado.

### Dependencias

- Fase 0
- Fase 1
- Fase 2
- Fase 3

## Fase 4 - Cartao fidelidade

### Objetivo

Evoluir fidelidade baseada em visitas/pontos por empresa com progresso e recompensa validados pela empresa.

### Status de execucao

- Executada em cima do Laravel canonico.
- Nenhum fluxo Node/Express foi usado.
- `QRCodeController` permaneceu fora do caminho canonico.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/CartaoFidelidadeController.php`
- `backend/app/Http/Controllers/BonusAdesaoController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Models/CartaoFidelidade.php`
- `backend/app/Models/CartaoFidelidadeProgresso.php`
- `backend/app/Models/CartaoFidelidadeMovimento.php`
- `backend/app/Services/CartaoFidelidadeService.php`
- `backend/database/migrations/2026_05_07_140000_extend_cartoes_fidelidade_for_phase4.php`
- `backend/database/migrations/2026_05_07_140100_create_cartoes_fidelidade_movimentos_table.php`
- `backend/routes/api.php`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `backend/tests/Feature/CartaoFidelidadePhase4FlowTest.php`

### Migrations da fase

- Criada:
  - `2026_05_07_140000_extend_cartoes_fidelidade_for_phase4.php`
- Criada:
  - `2026_05_07_140100_create_cartoes_fidelidade_movimentos_table.php`

Objetivo real das migrations:

- adaptar `cartoes_fidelidade` ao contrato canonico de regra, pontos e recompensa;
- criar historico proprio de fidelidade em `cartoes_fidelidade_movimentos`.

### Endpoints criados ou alterados

- Empresa:
  - `GET /api/empresa/cartao-fidelidade`
  - `POST /api/empresa/cartao-fidelidade`
  - `GET /api/empresa/cartao-fidelidade/{id}`
  - `PUT /api/empresa/cartao-fidelidade/{id}`
  - `PATCH /api/empresa/cartao-fidelidade/{id}/toggle`
  - `POST /api/empresa/cartao-fidelidade/{id}/clientes/{cliente_id}/adicionar-ponto`
  - `POST /api/empresa/cartao-fidelidade/{id}/clientes/{cliente_id}/resgatar`
- Cliente:
  - `GET /api/cliente/cartao-fidelidade/progresso/{empresa_id}`
- Fluxo compartilhado por QR:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - enriquecido com snapshot do cartao fidelidade
- Publico:
  - `GET /api/empresas/{id}`
    - enriquecido com o cartao fidelidade ativo/mais recente da empresa

### Models e services efetivamente usados

- Models:
  - `CartaoFidelidade`
  - `CartaoFidelidadeProgresso`
  - `CartaoFidelidadeMovimento`
  - `Empresa`
  - `InscricaoEmpresa`
  - `User`
- Services:
  - `CartaoFidelidadeService`
  - `ClienteQrCodeService`
  - `BonusAdesaoService` para enriquecer a consulta canonica do QR do cliente

### Componentes frontend efetivamente adaptados

- `detalhe_do_parceiro.html`
  - card do cartao fidelidade;
  - regra geral;
  - recompensa;
  - progresso individual quando o cliente esta vinculado.
- `gest_o_de_ofertas_parceiro.html`
  - painel de configuracao do cartao fidelidade;
  - preview do cartao;
  - lista de cartoes cadastrados;
  - CTA para leitura do QR do cliente.
- `validar_resgate.html`
  - modo `fidelidade`;
  - painel unificado de bonus + fidelidade por cliente;
  - botoes para adicionar ponto e resgatar recompensa.
- `stitch-app.js`
  - passou a orquestrar configuracao do cartao;
  - consulta do cliente por QR;
  - progress bar;
  - historico resumido;
  - validacao operacional da recompensa.

### Riscos

- a tela `validar_resgate.html` ainda concentra fluxo legado e fluxo canonico por modos;
- `POST /api/empresa/escanear-cliente` continua legado e fora do caminho principal do cartao fidelidade;
- existe tabela legada `cartao_fidelidades` fora do caminho canonico;
- a trava simples de 15 segundos contra dupla pontuacao cobre erro operacional basico, mas nao substitui idempotencia formal para integracao futura com PDV.

### Criterios de aceite

- empresa ativa cria, edita e ativa/desativa cartao;
- cliente vinculado ve progresso por empresa;
- cliente nao adiciona ponto nem resgata sozinho;
- empresa consulta o cliente pelo QR canonico;
- empresa adiciona pontos por visita;
- empresa resgata recompensa apenas com saldo suficiente;
- saldo excedente permanece apos resgate.

### Como testar

- `php artisan migrate`
- empresa:
  - abrir `/gest_o_de_ofertas_parceiro.html`;
  - criar/editar/ativar cartao fidelidade;
  - validar `GET /api/empresa/cartao-fidelidade`.
- cliente:
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar regra geral e progresso individual;
  - validar `GET /api/cliente/cartao-fidelidade/progresso/{empresa}`.
- empresa:
  - abrir `/validar_resgate.html?modo=fidelidade`;
  - escanear QR do cliente;
  - adicionar ponto;
  - validar historico em `cartoes_fidelidade_movimentos`;
  - continuar ate liberar recompensa;
  - resgatar e validar debito do saldo.

### Dependencias

- Fase 0
- Fase 1
- Fase 2
- Fase 3
- Fase 3.1

## Fase 5 - Promocoes e notificacoes

### Objetivo

Entregar promocoes instantaneas com imagem obrigatoria, push segmentado e limite por empresa.

### Status de execucao

- Executada em cima do Laravel canonico.
- Nenhum fluxo Node/Express foi usado.
- Push foi implementado reaproveitando `push_subscriptions`, `notificacoes_push`, `sw-push.js` e `minishlink/web-push`.

### Arquivos alterados na execucao

- `backend/app/Http/Controllers/API/ClienteAPIController.php`
- `backend/app/Http/Controllers/BonusAdesaoController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Http/Controllers/PromocaoController.php`
- `backend/app/Models/NotificacaoPush.php`
- `backend/app/Models/Promocao.php`
- `backend/app/Models/PromocaoResgate.php`
- `backend/app/Services/PromocaoInstantaneaService.php`
- `backend/database/migrations/2026_05_08_000000_add_phase5_fields_to_promocoes_table.php`
- `backend/database/migrations/2026_05_08_000100_create_promocao_resgates_table.php`
- `backend/database/migrations/2026_05_08_000200_extend_notificacoes_push_for_phase5.php`
- `backend/routes/api.php`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `backend/tests/Feature/PromocaoInstantaneaPhase5FlowTest.php`

### Migrations da fase

- `2026_05_08_000000_add_phase5_fields_to_promocoes_table.php`
- `2026_05_08_000100_create_promocao_resgates_table.php`
- `2026_05_08_000200_extend_notificacoes_push_for_phase5.php`

### Endpoints criados ou alterados

- Empresa:
  - `GET /api/empresa/promocoes`
  - `POST /api/empresa/promocoes`
  - `GET /api/empresa/promocoes/{id}`
  - `PUT /api/empresa/promocoes/{id}`
  - `DELETE /api/empresa/promocoes/{id}`
  - `PATCH /api/empresa/promocoes/{id}/toggle`
  - `PATCH /api/empresa/promocoes/{id}/ativar`
  - `PATCH /api/empresa/promocoes/{id}/pausar`
  - `POST /api/empresa/promocoes/{id}/enviar`
  - `POST /api/empresa/promocoes/{id}/validar`
- Cliente:
  - `GET /api/cliente/promocoes`
  - `POST /api/cliente/promocoes/{id}/resgatar`
    - bloqueado para impedir auto-resgate
- Publico:
  - `GET /api/empresas/{id}/promocoes`
- Fluxo de leitura do QR do cliente:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - enriquecido com snapshot de promocoes

### Componentes frontend efetivamente adaptados

- `gest_o_de_ofertas_parceiro.html`
  - formulario com imagem obrigatoria, validade e textos de push;
  - lista de promocoes;
  - status de envio;
  - limite semanal restante.
- `detalhe_do_parceiro.html`
  - secao de promocoes instantaneas;
  - estados:
    - disponivel
    - ja utilizada
    - exige vinculo
    - expirada
- `validar_resgate.html`
  - painel da empresa passou a listar promocoes elegiveis por cliente;
  - botao `Validar promocao`.
- `stitch-app.js`
  - orquestra CRUD, envio push, exibicao publica e validacao presencial.

### Riscos

- coexistencia temporaria entre promocoes canonicas e fluxos antigos de campanhas/cupons;
- `stitch-app.js` continua grande e centralizado;
- `validar_resgate.html` concentra bonus, fidelidade e promocao no mesmo shell;
- push depende de VAPID corretamente configurado e da subscription ativa do cliente;
- o limite semanal atual e simples:
  - conta `data_envio` em 7 dias;
  - nao possui ainda politica por plano/comercial.

### Criterios de aceite

- promocao exige imagem;
- titulo/descricao/notificacao respeitam limites;
- promocao publica aparece apenas se ativa e valida;
- cliente nao conclui resgate sozinho;
- empresa valida promocao pelo QR do cliente;
- historico fica em `promocao_resgates`;
- push vai apenas para clientes vinculados;
- envio falho por cliente nao interrompe o envio geral;
- limite semanal de 2 envios por empresa e respeitado.

### Como testar

- `php artisan migrate`
- empresa:
  - criar promocao em `/gest_o_de_ofertas_parceiro.html`;
  - editar, pausar/ativar e enviar push;
  - validar limite semanal.
- cliente:
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar exibicao e estados;
  - tentar `POST /api/cliente/promocoes/{id}/resgatar` e confirmar bloqueio.
- empresa:
  - abrir `/validar_resgate.html?modo=beneficios`;
  - escanear QR do cliente;
  - validar promocao;
  - repetir e confirmar bloqueio de duplicidade.
- push:
  - registrar subscription;
  - enviar promocao;
  - inspecionar `notificacoes_push`.

### Dependencias

- Fase 0
- Fase 1
- Fase 2
- Fase 3
- Fase 4

## Fase 6 - Bonus aniversario e lembrete de retorno

### Status

Implementada no caminho canonico Laravel em `2026-05-13`, sem reativar `QRCodeController` e sem usar `backend/api`.

### Objetivo entregue

Entregar:

- bonus aniversario configurado pela empresa;
- elegibilidade baseada em `users.data_nascimento`;
- validacao presencial pela empresa lendo o QR do cliente;
- push de aniversario apenas para clientes vinculados e elegiveis;
- lembrete de retorno por inatividade com historico para evitar reenvio no mesmo ciclo.

Avaliacoes ficaram fora do escopo real desta entrega.

### Arquivos principais

- `backend/app/Http/Controllers/BonusAniversarioController.php`
- `backend/app/Http/Controllers/LembreteRetornoController.php`
- `backend/app/Http/Controllers/BonusAdesaoController.php`
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Models/BonusAniversario.php`
- `backend/app/Models/BonusAniversarioResgate.php`
- `backend/app/Models/LembreteAusencia.php`
- `backend/app/Models/LembreteEnvio.php`
- `backend/app/Models/NotificacaoPush.php`
- `backend/app/Services/BonusAniversarioService.php`
- `backend/app/Services/LembreteRetornoService.php`
- `backend/app/Services/WebPushDeliveryService.php`
- `backend/routes/api.php`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/js/stitch-app.js`

### Migrations da Fase 6

- `2026_05_13_000000_extend_bonus_aniversario_for_phase6.php`
  - adiciona `dias_validade`, `notification_title`, `notification_body`
- `2026_05_13_000100_create_bonus_aniversario_resgates_table.php`
  - cria `bonus_aniversario_resgates`
  - unicidade por `bonus_aniversario_id + user_id + ano`
  - unicidade operacional adicional por `empresa_id + user_id + ano`
- `2026_05_13_000200_extend_lembretes_ausencia_for_phase6.php`
  - adiciona `dias_sem_visita`
- `2026_05_13_000300_create_lembrete_envios_table.php`
  - cria historico `lembrete_envios`
  - unicidade por `lembrete_id + user_id + reference_last_visit_at`
- `2026_05_13_000400_extend_notificacoes_push_for_phase6.php`
  - adiciona `bonus_aniversario_id` e `lembrete_id` em `notificacoes_push`

### Endpoints entregues

- Empresa:
  - `GET /api/empresa/bonus-aniversario`
  - `POST /api/empresa/bonus-aniversario`
  - `GET /api/empresa/bonus-aniversario/{id}`
  - `PUT /api/empresa/bonus-aniversario/{id}`
  - `PATCH /api/empresa/bonus-aniversario/{id}/toggle`
  - `POST /api/empresa/bonus-aniversario/{id}/validar`
  - `POST /api/empresa/bonus-aniversario/{id}/enviar-elegiveis`
  - `GET /api/empresa/lembrete-retorno`
  - `POST /api/empresa/lembrete-retorno`
  - `GET /api/empresa/lembrete-retorno/{id}`
  - `PUT /api/empresa/lembrete-retorno/{id}`
  - `PATCH /api/empresa/lembrete-retorno/{id}/toggle`
  - `POST /api/empresa/lembrete-retorno/enviar-elegiveis`
- Cliente:
  - `GET /api/cliente/bonus-aniversario/disponiveis`
- Fluxo compartilhado de QR:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - agora devolve `bonus_adesao`, `bonus_aniversario`, `cartao_fidelidade` e `promocoes`

### Regras funcionais

- bonus aniversario:
  - cliente precisa estar vinculado a empresa;
  - cliente precisa ter `data_nascimento`;
  - se `dias_validade` estiver vazio, a elegibilidade vale durante o mes do aniversario;
  - se `dias_validade` estiver preenchido, a elegibilidade vale da data de aniversario ate `N` dias;
  - resgate acontece uma vez por ano por empresa;
  - cliente nao conclui resgate sozinho;
  - empresa valida lendo o QR do cliente.
- lembrete de retorno:
  - usa `inscricoes_empresa.ultima_visita` como fonte principal;
  - usa historicos canonicos de fidelidade, bonus de adesao, promocao e bonus aniversario como fallback;
  - so envia para clientes vinculados com inatividade maior ou igual ao limite configurado;
  - nao reenfileira para a mesma `reference_last_visit_at`.

### Frontend entregue

- `detalhe_do_parceiro.html`
  - card real de bonus aniversario;
  - bloco explicativo de lembrete de retorno;
  - consulta individual do cliente via `GET /api/cliente/bonus-aniversario/disponiveis`
- `gest_o_de_ofertas_parceiro.html`
  - configuracao de bonus aniversario;
  - configuracao de lembrete;
  - botoes de envio manual para elegiveis
- `validar_resgate.html`
  - lookup do cliente agora inclui bonus aniversario;
  - botao de validacao presencial do bonus aniversario

### Riscos remanescentes

- `data_nascimento` continua fora do cadastro publico atual; clientes antigos sem esse dado nao ficam elegiveis;
- o envio automatico ainda depende de disparo manual; scheduler/cron ficou documentado como pendencia;
- `stitch-app.js` continua concentrando muitas jornadas;
- push web continua dependente de VAPID/subscription valida no navegador do cliente.

### Como testar

- empresa:
  - criar/editar/ativar/desativar bonus aniversario em `/gest_o_de_ofertas_parceiro.html`;
  - criar/editar/ativar/desativar lembrete de retorno;
  - enviar push manual de bonus aniversario e lembrete.
- cliente:
  - manter `data_nascimento` preenchida;
  - vincular-se a empresa;
  - abrir `detalhe_do_parceiro.html?id={empresa}` e conferir o card de aniversario.
- validacao presencial:
  - abrir `/validar_resgate.html?modo=beneficios`;
  - escanear QR dinamico do cliente;
  - validar bonus aniversario;
  - repetir no mesmo ano e confirmar bloqueio.
- lembrete:
  - ajustar `ultima_visita` ou historico equivalente;
  - disparar `POST /api/empresa/lembrete-retorno/enviar-elegiveis`;
  - confirmar gravacao em `lembrete_envios` e `notificacoes_push`.

### Dependencias

- Fase 0
- Fase 1
- Fase 2
- Fase 3
- Fase 4
- Fase 5

## Fase 7 - Relatorios e admin

### Objetivo

Fechar a camada de operacao e acompanhamento para empresa e administracao, incluindo o fechamento do cadastro publico com `data_nascimento` e as avaliacoes canonicas por empresa.

### Status de execucao

- Executada e estabilizada sobre o backend Laravel.
- Nenhuma arquitetura paralela foi criada.
- Nenhuma funcionalidade da Fase 8 foi iniciada.
- Nenhuma migration nova foi necessaria para concluir o escopo.

### Arquivos efetivamente impactados

- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Controllers/AvaliacaoController.php`
- `backend/app/Http/Controllers/API/EmpresaAPIController.php`
- `backend/app/Http/Controllers/AdminReportController.php`
- `backend/app/Services/AvaliacaoService.php`
- `backend/app/Services/RelatorioOperacionalService.php`
- `backend/routes/api.php`
- `backend/public/criar_conta.html`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/dashboard_parceiro.html`
- `backend/public/dashboard_admin_master.html`
- `backend/public/relat_rios_gerais_master.html`
- `backend/public/clientes_fidelizados_loja.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `docs/i9plus/TECH_AUDIT.md`
- `docs/i9plus/IMPLEMENTATION_PLAN.md`

### Services canonicos desta fase

- `AvaliacaoService`
  - centraliza criacao, edicao, listagem publica, listagem da empresa e sumario das avaliacoes.
- `RelatorioOperacionalService`
  - centraliza resumo operacional da empresa, lista de clientes vinculados e resumo administrativo.

### Migrations da Fase 7

- Nenhuma nova.
- Reaproveitadas:
  - coluna `users.data_nascimento`;
  - tabela `avaliacoes` com constraint unica por `user_id + empresa_id`;
  - tabelas operacionais ja existentes de bonus, fidelidade, promocoes, notificacoes e vinculos.

### Endpoints finais da Fase 7

- Cadastro:
  - `POST /api/auth/register`
    - cliente agora exige e persiste `data_nascimento`;
    - empresa continua sem receber esse campo no payload final.
- Avaliacoes publicas e do cliente:
  - `GET /api/empresas/{id}/avaliacoes`
  - `POST /api/cliente/avaliar`
  - `GET /api/cliente/avaliacoes`
  - `POST /api/empresas/{id}/avaliacoes`
  - `PUT /api/empresas/{id}/avaliacoes/minha`
- Operacao da empresa:
  - `GET /api/empresa/clientes`
  - `GET /api/empresa/avaliacoes`
  - `GET /api/empresa/relatorios/resumo`
- Administrativo:
  - `GET /api/admin/relatorios/resumo`

### Regras canonicas fechadas

- Cadastro publico:
  - `data_nascimento` e obrigatorio apenas para `cliente`;
  - o fluxo de `empresa` remove esse campo antes do envio final do payload no frontend e nao o persiste no backend.
- Avaliacoes:
  - apenas cliente autenticado e vinculado pode avaliar;
  - nota obrigatoria entre `1` e `5`;
  - comentario opcional com limite de `500` caracteres;
  - uma avaliacao por cliente por empresa;
  - `POST` cria a avaliacao;
  - `PUT /minha` edita a avaliacao existente;
  - a pagina publica da empresa expoe media, total, distribuicao e avaliacoes recentes;
  - a empresa autenticada lista apenas avaliacoes recebidas pela propria empresa.
- Relatorios da empresa:
  - a empresa so acessa dados da propria base vinculada;
  - o resumo agrega clientes, aniversariantes, inativos, bonus, pontos, recompensas, promocoes, notificacoes e avaliacoes;
  - a lista de clientes retorna nome, email, telefone, data de nascimento, data de vinculo, ultima visita, pontos atuais e status de inatividade;
  - quando nao existir configuracao de lembrete, o calculo de inatividade usa fallback operacional de `30` dias para manter o relatorio consistente.
- Relatorios admin:
  - totaliza empresas por status, clientes, vinculos, promocoes, resgates, notificacoes e media geral de avaliacoes;
  - inclui rankings simples de empresas por clientes e por resgates.

### Frontend efetivamente fechado

- `criar_conta.html`
  - manteve o formulario existente e o campo `data_nascimento` do cliente.
- `detalhe_do_parceiro.html`
  - consome as avaliacoes publicas e permite criar/editar avaliacao do proprio cliente vinculado.
- `dashboard_parceiro.html`
  - consome o resumo operacional da empresa ja no endpoint canonico.
- `clientes_fidelizados_loja.html`
  - consome a lista canonica de clientes vinculados com dados de inatividade e aniversario.
- `dashboard_admin_master.html`
  - passou a aceitar o resumo admin consolidado como fonte preferencial para KPIs.
- `relat_rios_gerais_master.html`
  - passou a renderizar resumo admin, status de empresas e rankings usando `GET /api/admin/relatorios/resumo`.
- `stitch-app.js`
  - recebeu apenas ajustes funcionais e de fallback, sem refatoracao visual.

### Riscos remanescentes

- o bundle publico ainda depende de minificacao manual de `stitch-app.js` para manter `public/dist/stitch-app.min.js` sincronizado;
- os relatorios usam agregacao sobre tabelas legadas e canonicas coexistentes, entao a confiabilidade final continua dependente da qualidade dos dados historicos;
- a suite `php artisan test` continua condicionada a `backend/vendor` estar presente no ambiente local.

### Criterios de aceite atendidos

- admin visualiza empresas por status e rankings simples;
- empresa visualiza clientes, aniversariantes, inativos, resgates, pontos e avaliacoes;
- o cadastro publico do cliente persiste `data_nascimento` sem contaminar o fluxo de empresa;
- as avaliacoes seguem a regra de uma por cliente por empresa com edicao explicita;
- os relatorios refletem o dominio canonico estabilizado nas fases anteriores.

### Dependencias

- Fase 0
- Fase 1
- Fase 2
- Fase 3
- Fase 4
- Fase 5
- Fase 6

## Fase 8 - Refinamento visual

### Objetivo

Aplicar o contrato visual i9Plus sobre a base funcional resultante, respeitando mobile app-like e desktop painel/site.

### Arquivos provaveis

- `backend/public/*.html` das telas priorizadas
- `backend/public/js/stitch-app.js`
- assets CSS compartilhados da camada publica
- possiveis assets Vite se a equipe optar por consolidar estilo sem criar segunda aplicacao

### Migrations provaveis

- nenhuma.

### Endpoints provaveis

- nenhum endpoint novo obrigatorio;
- apenas ajustes pequenos de payload quando a tela refinada precisar.

### Componentes frontend provaveis

- bottom nav mobile padronizada;
- cards e banners sob tokens novos;
- header escuro da pagina da empresa;
- grid de categorias;
- home do cliente com QR central;
- layouts desktop com sidebar/header e tabelas.

### Riscos

- tentar refinar visual antes de estabilizar o dominio;
- substituir toda a camada visual de uma vez;
- manter PWA e rotas reais desalinhados.

### Criterios de aceite

- mobile parece app/PWA;
- desktop parece site/painel;
- cliente nao usa layout de dashboard comprimido;
- admin e empresa nao usam layout de celular esticado;
- principais telas batem com o contrato visual documentado.

### Dependencias

- Fase 0 a Fase 7.

## Arquivos provaveis

- Backend:
  - `backend/app/Http/Controllers/AuthController.php`
  - `backend/app/Http/Controllers/API/ClienteAPIController.php`
  - `backend/app/Http/Controllers/API/EmpresaAPIController.php`
  - controllers admin existentes
  - `backend/app/Models/User.php`
  - `backend/app/Models/Empresa.php`
  - `backend/app/Models/QRCode.php`
  - `backend/app/Models/InscricaoEmpresa.php`
  - `backend/app/Models/BonusAdesao.php`
  - `backend/app/Models/CartaoFidelidade.php`
  - `backend/app/Models/CartaoFidelidadeProgresso.php`
  - `backend/app/Models/Promocao.php`
  - `backend/app/Models/BonusAniversario.php`
  - `backend/app/Models/LembreteAusencia.php`
  - `backend/app/Models/Avaliacao.php`
  - `backend/app/Services/ClienteQrCodeService.php`
  - `backend/app/Services/QRCodeService.php`
  - `backend/app/Services/LoyaltyProgramService.php`
  - `backend/app/Services/NotificationService.php`
  - `backend/routes/api.php`
- Frontend:
  - `backend/public/meus_pontos.html`
  - `backend/public/parceiros_tem_de_tudo.html`
  - `backend/public/detalhe_do_parceiro.html`
  - `backend/public/dashboard_parceiro.html`
  - `backend/public/validar_resgate.html`
  - `backend/public/gest_o_de_estabelecimentos.html`
  - `backend/public/gest_o_de_ofertas_parceiro.html`
  - `backend/public/minhas_campanhas_loja.html`
  - `backend/public/relat_rios_gerais_master.html`
  - `backend/public/criar_conta.html`
  - `backend/public/js/stitch-app.js`
  - `backend/public/manifest.json`
  - `backend/public/sw-push.js`

## Migrations provaveis

- metadados de aprovacao/rejeicao/suspensao de empresa;
- eventuais ajustes de vinculo cliente/empresa;
- rastreio de bonus/resgates;
- historico de lembretes/notificacoes;
- ajustes funcionais de fidelidade e aniversario, se exigidos pela implementacao.

## Endpoints provaveis

- adaptacao de `/api/auth/register`;
- adaptacao de `/api/empresas` e `/api/empresas/{id}`;
- adaptacao de `/api/cliente/meu-qrcode`;
- adaptacao de `/api/cliente/escanear-qrcode`;
- adaptacao de `/api/empresa/escanear-cliente`;
- adaptacao de endpoints de bonus, fidelidade, promocoes, avaliacoes e relatorios;
- novos endpoints admin de aprovacao/suspensao, somente se os atuais nao cobrirem o fluxo.

## Componentes frontend provaveis

- home cliente;
- pagina publica da empresa;
- scanner da empresa;
- meu QR Code;
- bonus de adesao;
- cartao fidelidade;
- promocoes instantaneas;
- bonus aniversario;
- tela admin de aprovacao;
- estados de notificacao e historico.

## Riscos

- rotas legadas de QR coexistindo com o caminho canonico;
- `backend/api` paralelo gerar ambiguidade tecnica;
- mistura do dominio antigo de pontos com o novo dominio de fidelizacao por empresa;
- tentativa de redesenhar tudo de uma vez.

## Criterios de aceite

- cada fase reaproveita explicitamente a base atual;
- nenhum resgate e concluido apenas pelo cliente;
- empresa pendente nao aparece publicamente;
- QR de empresa e QR de cliente seguem contrato seguro e canonico;
- push respeita vinculo cliente/empresa;
- mobile e desktop convergem para o contrato visual sem segunda aplicacao paralela.
