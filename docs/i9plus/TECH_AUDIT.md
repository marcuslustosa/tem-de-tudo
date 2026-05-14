# Auditoria tecnica

## Escopo e criterio desta auditoria

Esta auditoria foi preenchida a partir do codigo real do repositorio e atualizada apos a execucao da Fase 0 tecnica de estabilizacao.

Regras aplicadas:

- Laravel e a base canonica.
- `backend/api` Node/Express/Prisma permanece fora do caminho principal.
- Quando algo nao existe de forma confiavel, ele e marcado como `nao identificado`, `nao existente` ou `legado`.
- Reaproveitamento da base atual tem prioridade sobre reescrita.

## Atualizacao Fase 0 - 2026-05-07

### Decisao canonica de backend

- Backend canonico: Laravel.
- Novas funcionalidades devem partir de `backend/app`, `backend/routes`, `backend/database` e `backend/public`.
- `backend/api` permanece intacto, mas explicitamente fora do caminho principal nesta etapa.

### Decisao canonica de QR

- QR do cliente:
  - canonico em `ClienteQrCodeService`;
  - assinado;
  - com expiracao;
  - nao depende da tabela `qr_codes`.
- QR da empresa:
  - canonico na tabela `qr_codes`;
  - persistido em `qr_codes.code`;
  - token opaco;
  - geracao normalizada no Laravel com prefixo `COMPANY_V1_`.

### Decisao canonica de status de empresa

- Status operacional canonico definido em `empresas.status`:
  - `pending`
  - `active`
  - `suspended`
  - `rejected`
- `ativo` foi preservado para compatibilidade.
- Visibilidade publica estabilizada:
  - empresa so aparece publicamente quando `ativo = true` e `status = active`.

### Ajustes minimos realizados na Fase 0

- migration incremental para `empresas.status` quando ausente;
- normalizacao do model `Empresa`;
- normalizacao do model `QRCode`;
- normalizacao de `QRCodeService` para assumir `qr_codes` como QR da empresa;
- alinhamento de `QRCodeController` ao QR assinado do cliente;
- filtros publicos atualizados em endpoints de empresa;
- `manifest.json` corrigido para apontar apenas para paginas existentes;
- testes de contrato adicionados para QR da empresa, status publico e manifest.

### Validacao executada nesta sessao

- lint PHP dos arquivos alterados;
- parse do `manifest.json`;
- validacao de existencia das paginas apontadas pelo manifest;
- `git diff --check`.

### Limitacao desta validacao

- Nao foi possivel executar `php artisan test` neste ambiente porque `backend/vendor` nao estava presente e `composer` nao estava disponivel no PATH local.

## Atualizacao Fase 1 - 2026-05-07

### Fluxo canonico implementado para empresa

- Cadastro publico de empresa reutiliza `POST /api/auth/register`.
- O formulario reutilizado em `backend/public/criar_conta.html` passou a aceitar:
  - responsavel;
  - nome fantasia;
  - email;
  - telefone;
  - WhatsApp;
  - categoria;
  - endereco;
  - senha;
  - CNPJ.
- No cadastro publico:
  - `users.perfil = empresa`;
  - `users.status = pendente`;
  - `empresas.status = pending`;
  - `empresas.ativo = false`;
  - QR da empresa ainda nao e gerado.

### Fluxo canonico de aprovacao admin

- Listagem administrativa de empresas:
  - `GET /api/admin/empresas`
- Aprovacao:
  - `POST /api/admin/empresas/{id}/approve`
- Rejeicao:
  - `POST /api/admin/empresas/{id}/reject`
- Suspensao:
  - `POST /api/admin/empresas/{id}/suspend`
- Compatibilidade legada preservada:
  - `PATCH /api/admin/empresas/{id}/toggle-status`

### Efeito de cada transicao

- `approve`:
  - `empresas.status = active`
  - `empresas.ativo = true`
  - `users.status = ativo`
  - QR da empresa garantido via `QRCodeService`
- `reject`:
  - `empresas.status = rejected`
  - `empresas.ativo = false`
  - `users.status = inativo`
- `suspend`:
  - `empresas.status = suspended`
  - `empresas.ativo = false`
  - `users.status = bloqueado`

### Protecao operacional aplicada

- `CheckCompanySubscription` agora bloqueia rotas de empresa quando o estabelecimento:
  - esta `pending`;
  - esta `rejected`;
  - esta `suspended`;
  - ou nao esta com `ativo = true`.
- Resultado pratico:
  - empresa pendente nao opera painel/rotas de empresa;
  - empresa rejeitada ou suspensa tambem nao opera;
  - visibilidade publica continua restrita a `active + ativo = true`.

### Arquivos alterados na Fase 1

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

### Models e services efetivamente impactados

- Models alterados nesta fase:
  - `Empresa`
  - `User` por efeito de status operacional sincronizado, sem alteracao estrutural no model
- Services reutilizados:
  - `QRCodeService` como gerador canonico de QR de empresa
- Base Node paralela:
  - nenhuma alteracao

### Migrations desta fase

- Nenhuma migration nova foi necessaria para fechar a Fase 1.
- A fase reaproveitou `empresas.status` introduzido na Fase 0 e o vinculo existente `empresas.owner_id`.

### Como testar a Fase 1

- Cadastro publico:
  - abrir `/criar_conta.html`;
  - selecionar `Estabelecimento`;
  - enviar formulario;
  - validar em banco/API que a empresa entrou `pending` e nao aparece em `/api/empresas`.
- Aprovacao admin:
  - autenticar admin;
  - abrir `/gest_o_de_estabelecimentos.html`;
  - filtrar `Pendente`;
  - aprovar empresa;
  - validar que aparece em `/api/empresas` e que existe QR em `qr_codes`.
- Bloqueio operacional:
  - tentar operar rotas `/api/empresa/*` com empresa `pending`, `rejected` ou `suspended`;
  - esperar `403 company_status_blocked`.

### Riscos remanescentes apos a Fase 1

- o HTML de cadastro ainda carrega um script inline legado com gate antigo neutralizado por `if (false)`, devendo ser removido numa limpeza futura segura;
- a tela admin continua em HTML/JS legado e ainda nao tem UX dedicada para revisao detalhada da empresa;
- ainda nao existe trilha auditavel especifica de aprovacao/rejeicao com `approved_at`, `approved_by` e equivalentes;
- o bundle `public/dist/stitch-app.min.js` depende de processo de build/manual minificacao para refletir a fonte `public/js/stitch-app.js`.

## Atualizacao Fase 2 - 2026-05-07

### Fluxo canonico final de QR da empresa

- QR da empresa continua canonico em `qr_codes.code` via `QRCodeService`.
- O payload final do QR da empresa agora e a URL publica:
  - `/vincular_empresa.html?code={qr_codes.code}`
- O token persistido continua opaco:
  - prefixo `COMPANY_V1_`
  - sem exposicao de ID sequencial puro
- Se a empresa ja tiver QR, ele e reaproveitado.
- Se nao tiver QR:
  - ele pode ser gerado na aprovacao admin;
  - ou no acesso da propria empresa ao endpoint de QR;
  - ou no acesso admin de visualizacao do QR.

### Fluxo canonico final de QR do cliente

- QR do cliente permanece canonico em `ClienteQrCodeService`.
- O endpoint principal do cliente para obter seu QR e:
  - `GET /api/cliente/meu-qrcode`
- O retorno inclui:
  - `codigo`
  - `versao`
  - `expira_em`
  - `qrcode_svg`
- O QR do cliente segue:
  - assinado;
  - temporario;
  - sem expor ID sequencial puro.

### Fluxo canonico final de vinculo cliente/empresa

- Entidade canonica de vinculo:
  - `inscricoes_empresa`
  - model `InscricaoEmpresa`
- Endpoint canonico de vinculo:
  - `POST /api/cliente/vincular-empresa-qrcode`
- Regras implementadas:
  - aceita tanto `qr_codes.code` puro quanto a URL publica completa do QR da empresa;
  - resolve o QR da empresa via `QRCodeService`;
  - so aceita empresa `active` + `ativo = true`;
  - nao duplica vinculo;
  - usa `firstOrCreate` em `inscricoes_empresa`;
  - incrementa uso do QR da empresa quando aplicavel.

### Endpoints criados ou alterados na Fase 2

- Criado:
  - `GET /api/qrcode/empresa/{code}`
    - resolve QR publico de empresa ativa
    - retorna empresa serializada e `scan_url`
- Criado:
  - `POST /api/cliente/vincular-empresa-qrcode`
    - vincula cliente autenticado a empresa ativa
- Reutilizado e adaptado:
  - `GET /api/cliente/dashboard`
    - agora retorna:
      - `empresas_vinculadas`
      - `empresas_destaque`
      - `acoes_rapidas`
- Reutilizado:
  - `GET /api/cliente/meu-qrcode`
- Reutilizado e adaptado:
  - `GET /api/empresa/qrcodes`
    - agora garante QR canonico da empresa
    - retorna `code`, `scan_url`, `qr_url`, `qr_image`, `public_page_url`
- Criado:
  - `GET /api/admin/empresas/{id}/qrcode`
    - permite ao admin visualizar o QR canonico da empresa
- Reutilizado e reforcado:
  - `GET /api/empresas`
  - `GET /api/empresas/{id}`
    - continuam restritos a empresas publicamente visiveis

### Telas e fluxos frontend efetivamente adaptados

- `backend/public/meus_pontos.html`
  - passou a operar como home do cliente da Fase 2
  - inclui:
    - saudacao
    - `Ler QR Code`
    - `Meu QR Code`
    - empresas vinculadas
    - empresas em destaque
- `backend/public/detalhe_do_parceiro.html`
  - passou a operar como pagina publica da empresa
- `backend/public/vincular_empresa.html`
  - nova pagina de aterrissagem para QR da empresa
- `backend/public/validar_resgate.html`
  - reaproveitado como scanner para:
    - empresa lendo QR do cliente
    - cliente lendo QR da empresa
- `backend/public/entrar.html`
  - ajustado para preservar origem de QR e concluir o fluxo apos login
- `backend/public/js/stitch-app.js`
  - centraliza a orquestracao de:
    - home cliente
    - pagina publica da empresa
    - leitura/vinculo
    - redirecionamento pos-login

### Decisoes de reaproveitamento confirmadas

- Laravel mantido como backend canonico.
- `ClienteQrCodeService` mantido como trilha unica do QR do cliente.
- `QRCodeService` mantido como trilha unica do QR da empresa.
- `InscricaoEmpresa` reaproveitada como entidade canonica de vinculo.
- `validar_resgate.html` reaproveitado como scanner compartilhado.
- `meus_pontos.html` reaproveitado em vez de criar segunda home paralela.
- `detalhe_do_parceiro.html` reaproveitado como pagina publica da empresa.

### Ajustes minimos realizados na Fase 2

- normalizacao do payload do QR da empresa para URL publica de vinculo;
- normalizacao de `QRCodeService` para gerar imagem/URL coerentes com esse payload;
- normalizacao do endpoint de vinculo para aceitar token puro ou URL escaneada;
- serializacao publica de empresa consolidada em `EmpresaController`;
- retorno do dashboard do cliente adaptado para o produto i9Plus;
- retorno do QR da empresa adaptado para consumo do painel da empresa e do admin.

### Migrations da Fase 2

- nenhuma migration nova foi criada;
- a fase reaproveitou:
  - `qr_codes`
  - `inscricoes_empresa`
  - `empresas`
  - `users`

### Arquivos tecnicos alterados na Fase 2

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

### Como testar a Fase 2

- Empresa:
  - autenticar empresa `active`;
  - abrir rota/tela que consome `GET /api/empresa/qrcodes`;
  - validar `code`, `scan_url`, `qr_image` e `public_page_url`.
- Admin:
  - autenticar admin;
  - chamar `GET /api/admin/empresas/{id}/qrcode`;
  - validar que enxerga o mesmo QR canonico.
- Cliente:
  - autenticar cliente;
  - abrir `/meus_pontos.html`;
  - validar botoes `Ler QR Code` e `Meu QR Code`;
  - validar exibicao do proprio QR.
- Vinculo:
  - ler o QR de uma empresa `active`;
  - confirmar criacao unica em `inscricoes_empresa`;
  - reler o mesmo QR;
  - confirmar ausencia de duplicidade.
- Publico:
  - abrir `/detalhe_do_parceiro.html?id={empresa_id}` para empresa `active`;
  - confirmar abertura publica;
  - repetir para empresa `pending`, `suspended` ou `rejected`;
  - confirmar indisponibilidade.

### Riscos remanescentes apos a Fase 2

- o scanner legado ainda e uma tela compartilhada entre fluxos antigos e novos;
- `/api/cliente/escanear-qrcode` continua existindo como trilha antiga de pontos e precisa ser tratado com cuidado na Fase 3+ para evitar confusao operacional;
- `stitch-app.js` ainda carrega bastante logica legado no mesmo arquivo, mesmo com a trilha canonica da Fase 2 estabilizada;
- a base visual ainda nao usa diretamente `docs/i9plus/i9plus-theme.css` dentro do produto;
- `backend/vendor` continua ausente neste ambiente, entao a validacao automatica ficou limitada a lint e testes adicionados sem execucao local.

## Atualizacao Fase 3 - 2026-05-07

### Decisao canonica para bonus de adesao

- Configuracao do bonus por empresa:
  - tabela `bonus_adesao`
  - model `BonusAdesao`
- Rastreio de uso unico e validacao:
  - tabela `bonus_adesao_resgates`
  - model `BonusAdesaoResgate`
- Regra canonica de resgate:
  - o cliente pode visualizar o bonus;
  - o cliente nao conclui o resgate sozinho;
  - a validacao real e feita pela empresa lendo o QR do cliente.

### Estruturas reaproveitadas e normalizadas

- `bonus_adesao`
  - reaproveitada como tabela canonica de configuracao do bonus de adesao da empresa;
  - recebeu campos incrementais para:
    - `data_expiracao`
    - `limite_por_cliente`
    - `tipo`
    - `ordem`
    - `termos`
- `bonus_adesao_resgates`
  - passa a ser a tabela canonica de rastreio de validacao/resgate por cliente;
  - nasce por renomeacao incremental da tabela legada `bonus_adesaos`;
  - normalizada para suportar:
    - `bonus_id`
    - `empresa_id`
    - `user_id`
    - `status`
    - `validated_by`
    - `redeemed_at`
  - possui unicidade canonica em:
    - `bonus_id + user_id`
- `inscricoes_empresa`
  - permanece como vinculo cliente/empresa;
  - `bonus_adesao_resgatado` foi preservado apenas como flag de compatibilidade;
  - nao e mais tratado como fonte de verdade unica quando houver mais de uma configuracao de bonus no futuro.

### Novo service canonico

- `backend/app/Services/BonusAdesaoService.php`

Responsabilidades:

- listar bonus por empresa;
- salvar/editar bonus mantendo uma versao ativa por vez;
- avaliar disponibilidade do bonus por cliente e empresa;
- consultar cliente via `ClienteQrCodeService`;
- validar o bonus da forma canonica;
- sincronizar o resgate em:
  - `bonus_adesao_resgates`
  - `inscricoes_empresa.bonus_adesao_resgatado`

### Endpoints criados ou alterados na Fase 3

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
    - agora retorna status operacional do bonus por cliente
  - `POST /api/cliente/bonus-adesao/resgatar/{empresa_id}`
    - nao conclui mais o resgate
    - responde com bloqueio orientando apresentacao do QR no estabelecimento

### Fluxo funcional final da Fase 3

#### 1. Configuracao pela empresa

- Empresa `active + ativo = true` autentica no Laravel/Sanctum.
- Gerencia seu proprio bonus de adesao em `gest_o_de_ofertas_parceiro.html`.
- Pode:
  - criar;
  - editar;
  - ativar/desativar;
  - consultar historico simples.

#### 2. Exibicao para o cliente

- Cliente autenticado e vinculado consulta:
  - `GET /api/cliente/bonus-adesao/disponivel/{empresa_id}`
- A pagina publica `detalhe_do_parceiro.html` passou a renderizar:
  - status `disponivel`;
  - `ja utilizado`;
  - `expirado`;
  - `indisponivel`;
  - `exige vinculo`
- Nenhum botao finaliza o resgate pelo cliente.

#### 3. Validacao pela empresa lendo QR do cliente

- Empresa abre:
  - `/validar_resgate.html?modo=bonus-adesao`
- O scanner ou o campo manual chama:
  - `POST /api/empresa/clientes/qrcode/consultar`
- O backend:
  - identifica o cliente por `ClienteQrCodeService`;
  - confirma vinculo com a empresa da sessao;
  - avalia disponibilidade do bonus;
  - retorna dados do cliente + status do bonus.
- Se houver bonus disponivel:
  - empresa chama `POST /api/empresa/bonus-adesao/{id}/validar`
  - o backend grava resgate em `bonus_adesao_resgates`
  - sincroniza `inscricoes_empresa.bonus_adesao_resgatado = true` apenas como compatibilidade legada
  - retorna o cliente com status `redeemed`

### Arquivos tecnicos alterados na Fase 3

- `backend/app/Http/Controllers/BonusAdesaoController.php`
- `backend/app/Models/BonusAdesao.php`
- `backend/app/Models/BonusAdesaoResgate.php`
- `backend/app/Services/BonusAdesaoService.php`
- `backend/database/migrations/2026_05_07_100000_add_phase3_fields_to_bonus_adesao_table.php`
- `backend/database/migrations/2026_05_07_100100_normalize_bonus_adesaos_as_bonus_adesao_resgates.php`
- `backend/database/migrations/2026_05_07_130000_rename_bonus_adesaos_to_bonus_adesao_resgates_table.php`
- `backend/routes/api.php`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/js/stitch-app.js`
- `backend/tests/Feature/BonusAdesaoPhase3FlowTest.php`

### Como testar a Fase 3

- Executar migrations novas:
  - `php artisan migrate`
- Empresa ativa:
  - autenticar empresa;
  - criar bonus em `/gest_o_de_ofertas_parceiro.html`;
  - validar `GET /api/empresa/bonus-adesao`.
- Cliente vinculado:
  - autenticar cliente;
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar status do bonus via `/api/cliente/bonus-adesao/disponivel/{empresa}`.
- Bloqueio de auto-resgate:
  - chamar `POST /api/cliente/bonus-adesao/resgatar/{empresa}`;
  - esperar bloqueio orientando apresentacao do QR.
- Validacao pela empresa:
  - abrir `/validar_resgate.html?modo=bonus-adesao`;
  - escanear QR do cliente;
  - validar bonus;
  - confirmar gravacao em `bonus_adesao_resgates`;
  - confirmar unicidade por `bonus_id + user_id`;
  - confirmar `inscricoes_empresa.bonus_adesao_resgatado = true` apenas como espelho de compatibilidade;
  - repetir validacao e esperar erro claro.

### Riscos remanescentes apos a Fase 3

- `validar_resgate.html` continua multiplexando fluxo legado e fluxo novo de bonus;
- `stitch-app.js` segue centralizando logica de varias jornadas e ainda merece modularizacao futura;
- `QRCodeController` legado continua carregando caminho antigo de bonus por primeira visita, embora o fluxo canonico atual esteja nos endpoints de `BonusAdesaoController`;
- o flag `inscricoes_empresa.bonus_adesao_resgatado` ainda existe por compatibilidade e deve ser tratado como espelho, nao como historico canonico;
- enquanto `backend/vendor` permanecer ausente localmente, a base segue validada por lint e testes adicionados, mas sem execucao local de `php artisan test`.

## Atualizacao Fase 3.1 - 2026-05-07

### Auditoria do `QRCodeController`

Metodos legados encontrados:

- `QRCodeController::escanearEmpresa(Request $request)`
  - tratava `primeira_visita`;
  - criava `InscricaoEmpresa`;
  - criava `Cupom` de bonus de adesao;
  - marcava `inscricoes_empresa.bonus_adesao_resgatado = true`;
  - portanto conflitaria com a trilha canonica da Fase 3.
- `QRCodeController::escanearCliente(Request $request)`
  - nao validava bonus;
  - nao gravava `bonus_adesao_resgates`;
  - fazia apenas leitura do QR do cliente e atualizacao de `ultima_visita`;
  - permanecia como compatibilidade antiga, fora da trilha atual de bonus.

### Rotas e dependencias reais encontradas

- Nenhuma rota ativa em `backend/routes/api.php` aponta hoje para:
  - `QRCodeController::escanearEmpresa`
  - `QRCodeController::escanearCliente`
- As rotas ativas da Fase 2/3 estao em controllers canonicos:
  - `POST /api/cliente/vincular-empresa-qrcode` -> `ClienteAPIController::vincularEmpresaViaQr`
  - `POST /api/empresa/clientes/qrcode/consultar` -> `BonusAdesaoController::consultarClienteQr`
  - `POST /api/empresa/bonus-adesao/{id}/validar` -> `BonusAdesaoController::validar`
  - `POST /api/empresa/escanear-cliente` -> `EmpresaAPIController::escanearCliente`
- Conclusao:
  - a Fase 2/3 nao depende do `QRCodeController` para vinculo ou validacao de bonus.

### Isolamento tecnico aplicado

- `QRCodeController::escanearEmpresa`
  - foi marcado como legado/deprecated;
  - passou a registrar uso legado em log;
  - deixou de criar `Cupom`;
  - deixou de marcar `inscricoes_empresa.bonus_adesao_resgatado = true`;
  - deixou de concluir qualquer liberacao/resgate de bonus;
  - passou a delegar apenas a avaliacao de status para `BonusAdesaoService`;
  - quando acionado, retorna metadados com o caminho canonico.
- `QRCodeController::escanearCliente`
  - foi marcado como legado/deprecated;
  - passou a registrar uso legado em log;
  - continua apenas como leitura defensiva do QR do cliente, sem operar bonus de adesao;
  - quando acionado, retorna metadados com o caminho canonico.

### Caminho canonico confirmado apos a Fase 3.1

- Configuracao do bonus:
  - `bonus_adesao`
- Historico/controle de resgate:
  - `bonus_adesao_resgates`
- Avaliacao do bonus:
  - `BonusAdesaoService::evaluateCustomerBonus(...)`
- Validacao do bonus:
  - `BonusAdesaoService::validateBonus(...)`
  - empresa autenticada
  - QR do cliente via `ClienteQrCodeService`

### Como a Fase 4 deve ler o QR do cliente

- Para bonus de adesao:
  - manter `POST /api/empresa/clientes/qrcode/consultar`
  - manter `POST /api/empresa/bonus-adesao/{id}/validar`
- Para cartao fidelidade/pontos por visita:
  - usar o endpoint autenticado da empresa `POST /api/empresa/escanear-cliente`
  - ou evoluir esse fluxo sobre `ClienteQrCodeService`
  - sem reutilizar `QRCodeController::escanearCliente`

### Riscos remanescentes apos a Fase 3.1

- `QRCodeController` ainda existe no repositorio e deve continuar tratado como legado;
- a rota ativa `POST /api/cliente/escanear-qrcode` continua sendo um fluxo antigo de pontos/check-in em `ClienteAPIController`, separado do vinculo canonico da Fase 2;
- `POST /api/empresa/escanear-cliente` ainda reflete um fluxo antigo de pontos/check-in e precisara ser alinhado com a modelagem da Fase 4 sem misturar bonus de adesao;
- permanecem rotas antigas no namespace `/api/qrcode/*` fora do caminho canonico do produto i9Plus e elas ainda merecem consolidacao posterior.

## Atualizacao Fase 4 - 2026-05-07

### Decisao canonica para cartao fidelidade

- Configuracao do cartao por empresa:
  - tabela `cartoes_fidelidade`
  - model `CartaoFidelidade`
- Progresso individual do cliente:
  - tabela `cartoes_fidelidade_progresso`
  - model `CartaoFidelidadeProgresso`
- Historico canonico de pontuacao e resgate:
  - tabela `cartoes_fidelidade_movimentos`
  - model `CartaoFidelidadeMovimento`
- Fluxo de consulta do cliente por QR:
  - continua canonico em `POST /api/empresa/clientes/qrcode/consultar`
  - QR do cliente continua em `ClienteQrCodeService`
- `QRCodeController` permanece fora do caminho canonico.

### Estruturas reaproveitadas e adaptadas

- `cartoes_fidelidade`
  - reaproveitada como configuracao do cartao da empresa;
  - recebeu campos incrementais:
    - `regra_ganho`
    - `pontos_por_visita`
    - `pontos_necessarios`
    - `recompensa_descricao`
    - `data_expiracao`
  - manteve sincronismo de compatibilidade com:
    - `meta_pontos`
    - `recompensa`
- `cartoes_fidelidade_progresso`
  - reaproveitada como saldo/progresso do cliente por cartao;
  - deixou de ser tratada como ponto de auto-reset implicito;
  - resgate da recompensa passou a ser acao explicita da empresa.
- `cartoes_fidelidade_movimentos`
  - nova tabela canonica de historico;
  - registra:
    - `earned`
    - `redeemed`
    - `adjusted`
- `cartao_fidelidades`
  - continua existindo como legado paralelo;
  - nao foi usada no caminho principal da Fase 4.

### Novo service canonico

- `backend/app/Services/CartaoFidelidadeService.php`

Responsabilidades:

- listar cartoes por empresa;
- salvar/editar cartao mantendo um ativo por vez;
- serializar cartao e progresso;
- consultar snapshot de fidelidade por cliente e empresa;
- adicionar ponto por visita;
- validar resgate da recompensa;
- gravar historico em `cartoes_fidelidade_movimentos`;
- impedir duplicidade imediata de pontuacao com guarda simples por tempo.

### Endpoints criados ou alterados na Fase 4

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
- Fluxo compartilhado de consulta por QR:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - passou a devolver:
      - bonus de adesao
      - snapshot do cartao fidelidade
      - progresso
      - historico resumido
- Publico:
  - `GET /api/empresas/{id}`
    - passou a devolver configuracao geral do cartao fidelidade ativo/mais recente da empresa

### Fluxo funcional final da Fase 4

#### 1. Configuracao pela empresa

- Empresa `active + ativo = true` autentica no Laravel/Sanctum.
- Gerencia seu cartao fidelidade em `gest_o_de_ofertas_parceiro.html`.
- Pode:
  - criar;
  - editar;
  - ativar/desativar;
  - consultar cartoes ja cadastrados.

#### 2. Exibicao publica e progresso do cliente

- A pagina publica `detalhe_do_parceiro.html` passou a renderizar:
  - regra geral do cartao;
  - recompensa;
  - pontos por visita;
  - meta;
  - validade;
  - progresso individual, quando o cliente autenticado esta vinculado.
- Cliente nao vinculado:
  - ve a regra geral;
  - nao ve progresso pessoal canonicamente liberado.

#### 3. Consulta da empresa lendo o QR do cliente

- Empresa abre:
  - `/validar_resgate.html?modo=fidelidade`
  - ou `/validar_resgate.html?modo=bonus-adesao`, que agora tambem enriquece o painel com fidelidade
- O scanner/manual chama:
  - `POST /api/empresa/clientes/qrcode/consultar`
- O backend:
  - identifica o cliente por `ClienteQrCodeService`;
  - confirma vinculo em `inscricoes_empresa`;
  - devolve:
    - bonus de adesao;
    - cartao fidelidade;
    - pontos atuais;
    - meta;
    - recompensa;
    - historico resumido.

#### 4. Pontuacao por visita

- Empresa valida a visita do cliente chamando:
  - `POST /api/empresa/cartao-fidelidade/{id}/clientes/{cliente_id}/adicionar-ponto`
- O backend:
  - confirma empresa dona do cartao;
  - confirma cartao ativo;
  - confirma cliente vinculado;
  - incrementa `cartoes_fidelidade_progresso`;
  - grava `earned` em `cartoes_fidelidade_movimentos`;
  - atualiza `inscricoes_empresa.ultima_visita`.

#### 5. Resgate da recompensa

- Empresa valida o resgate chamando:
  - `POST /api/empresa/cartao-fidelidade/{id}/clientes/{cliente_id}/resgatar`
- O backend:
  - confirma saldo suficiente;
  - debita `pontos_necessarios` do progresso;
  - incrementa `vezes_resgatado`;
  - grava `redeemed` em `cartoes_fidelidade_movimentos`;
  - preserva saldo excedente.

### Arquivos tecnicos alterados na Fase 4

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
- `backend/tests/Feature/CartaoFidelidadePhase4FlowTest.php`

### Como testar a Fase 4

- Executar migrations novas:
  - `php artisan migrate`
- Empresa ativa:
  - abrir `/gest_o_de_ofertas_parceiro.html`;
  - criar/editar/ativar cartao fidelidade;
  - validar `GET /api/empresa/cartao-fidelidade`.
- Cliente vinculado:
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar regra geral do cartao;
  - validar progresso individual via `GET /api/cliente/cartao-fidelidade/progresso/{empresa}`.
- Empresa:
  - abrir `/validar_resgate.html?modo=fidelidade`;
  - escanear QR do cliente;
  - adicionar ponto;
  - confirmar historico em `cartoes_fidelidade_movimentos`;
  - continuar pontuando ate liberar recompensa;
  - resgatar;
  - confirmar debito de pontos e saldo restante.

### Riscos remanescentes apos a Fase 4

- a tela `validar_resgate.html` continua compartilhando fluxo legado com fluxo canonico novo, ainda que agora diferenciada por modo;
- a rota antiga `POST /api/empresa/escanear-cliente` continua existindo para compatibilidade e segue fora do caminho canonico do cartao fidelidade;
- `cartao_fidelidades` continua como sobra legada no schema e merece isolamento/documentacao futura mais forte;
- a guarda simples de 15 segundos para evitar dupla pontuacao imediata reduz erro operacional, mas ainda nao substitui idempotencia mais robusta se o fluxo crescer para PDV integrado.

## Atualizacao Fase 5 - 2026-05-08

### Auditoria real de promocao/push reaproveitada

- Estrutura de promocoes ja existente e reaproveitada:
  - tabela/model `promocoes` via `Promocao`
  - endpoints legados em `EmpresaAPIController` e `ClienteAPIController`
  - endpoint publico `GET /api/empresas/{id}/promocoes`
- Estrutura de push reaproveitada:
  - tabela `push_subscriptions`
  - model `PushSubscription`
  - controller `PushSubscriptionController`
  - endpoint `GET /api/push/public-key`
  - endpoints autenticados:
    - `POST /api/push/subscribe`
    - `DELETE /api/push/unsubscribe`
    - `POST /api/push/test`
  - service worker `backend/public/sw-push.js`
  - biblioteca `minishlink/web-push`
  - configuracao VAPID em `config/services.php`
- Estrutura de log reaproveitada e estendida:
  - tabela `notificacoes_push`
  - model `NotificacaoPush`

### Decisao canonica de promocao instantanea

- Configuracao da promocao:
  - tabela `promocoes`
  - model `Promocao`
- Historico de validacao por cliente:
  - tabela `promocao_resgates`
  - model `PromocaoResgate`
- Trilha canonica de negocio:
  - `backend/app/Services/PromocaoInstantaneaService.php`
- Controller canonico da area da empresa:
  - `backend/app/Http/Controllers/PromocaoController.php`
- Regras canonicas consolidadas:
  - promocao exige imagem;
  - cliente nao conclui resgate sozinho;
  - validacao passa pela empresa autenticada;
  - resgate fica em `promocao_resgates`;
  - push vai apenas para clientes vinculados;
  - envio semanal limitado por empresa.

### Ajustes minimos realizados na Fase 5

- CRUD canonico de promocoes da empresa movido para `PromocaoController`;
- `ClienteAPIController` passou a bloquear auto-resgate de promocao e orientar uso presencial;
- `EmpresaController::getEmpresaPromocoes()` passou a expor apenas promocoes publicas validas da empresa publica;
- `BonusAdesaoController::consultarClienteQr()` passou a devolver snapshot de promocoes no mesmo lookup de bonus/fidelidade;
- `stitch-app.js` passou a orquestrar:
  - gestao de promocoes da empresa;
  - exibicao publica na pagina da empresa;
  - validacao da promocao pela empresa no scanner;
  - orientacao correta ao cliente sem auto-resgate;
- `sw-push.js` foi preservado como infra minima de recepcao de push, sem recriacao.

### Tabelas usadas/criadas na Fase 5

- Reaproveitadas:
  - `promocoes`
  - `push_subscriptions`
  - `notificacoes_push`
  - `inscricoes_empresa`
- Criadas/estendidas:
  - `2026_05_08_000000_add_phase5_fields_to_promocoes_table.php`
    - adiciona `notification_title` e `notification_body`
  - `2026_05_08_000100_create_promocao_resgates_table.php`
    - cria `promocao_resgates`
    - unicidade por `promocao_id + user_id`
  - `2026_05_08_000200_extend_notificacoes_push_for_phase5.php`
    - estende `notificacoes_push` com `promocao_id`, `status`, `erro`

### Endpoints criados ou alterados na Fase 5

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
    - agora usa o snapshot canonico por cliente/vinculo
  - `POST /api/cliente/promocoes/{id}/resgatar`
    - agora bloqueia auto-resgate e orienta uso presencial
- Publico:
  - `GET /api/empresas/{id}/promocoes`
    - agora retorna apenas promocoes `active + validas + publicas`
- Fluxo compartilhado de leitura do QR do cliente:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - agora devolve bonus + fidelidade + promocoes

### Fluxo funcional final da Fase 5

#### 1. Configuracao pela empresa

- Empresa `active + ativo = true` gerencia promocoes em `gest_o_de_ofertas_parceiro.html`.
- Pode:
  - criar;
  - editar;
  - ativar/desativar;
  - enviar push;
  - consultar limite semanal restante.

#### 2. Exibicao publica e elegibilidade do cliente

- `detalhe_do_parceiro.html` mostra promocoes publicas ativas e validas.
- Cliente vinculado autenticado ve status individual:
  - `available`
  - `redeemed`
  - `not_linked`
- Cliente nao conclui resgate pelo frontend.

#### 3. Validacao pela empresa lendo QR do cliente

- Empresa abre `validar_resgate.html?modo=beneficios` ou modos reaproveitados de bonus/fidelidade.
- O lookup `POST /api/empresa/clientes/qrcode/consultar` passa a devolver `promocoes`.
- Para cada promocao elegivel:
  - empresa chama `POST /api/empresa/promocoes/{id}/validar`
  - o backend grava em `promocao_resgates`
  - a promocao deixa de aparecer como disponivel para aquele cliente.

#### 4. Push notification segmentado

- A promocao e disparada por `POST /api/empresa/promocoes/{id}/enviar`.
- O envio:
  - considera apenas clientes vinculados em `inscricoes_empresa`;
  - busca subscriptions em `push_subscriptions`;
  - registra resultado por cliente em `notificacoes_push`;
  - continua mesmo quando um cliente falha ou nao possui subscription.

#### 5. Limite semanal

- Regra MVP canonica:
  - maximo de 2 envios por empresa em janela de 7 dias
- Fonte de contagem:
  - promocoes com `data_envio` nos ultimos 7 dias
- Bloqueio ocorre antes do disparo real.

### Arquivos tecnicos alterados na Fase 5

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
- `backend/tests/Feature/PromocaoInstantaneaPhase5FlowTest.php`

### Como testar a Fase 5

- Executar migrations novas:
  - `php artisan migrate`
- Empresa ativa:
  - abrir `/gest_o_de_ofertas_parceiro.html`;
  - criar promocao com imagem, validade e textos de push;
  - editar e alternar status;
  - enviar push e conferir retorno de limite semanal.
- Cliente:
  - vincular-se a empresa;
  - abrir `detalhe_do_parceiro.html?id={empresa}`;
  - validar status individual da promocao;
  - tentar `POST /api/cliente/promocoes/{id}/resgatar` e confirmar bloqueio.
- Empresa:
  - abrir `/validar_resgate.html?modo=beneficios`;
  - escanear QR do cliente;
  - validar promocao;
  - repetir validacao e confirmar erro claro;
  - validar gravacao em `promocao_resgates`.
- Push:
  - garantir VAPID configurado;
  - registrar subscription do cliente;
  - enviar promocao;
  - conferir `notificacoes_push` com statuses `sent`, `failed` ou `no_subscription`.

### Riscos remanescentes apos a Fase 5

- `stitch-app.js` continua centralizando muitas jornadas e segue como ponto de manutencao delicado;
- `validar_resgate.html` continua multiplexando bonus, fidelidade e promocao no mesmo shell;
- existe coexistencia temporaria entre o caminho canonico novo de promocao e rotas antigas de campanhas/cupons ainda presentes no repositorio;
- o envio push continua sensivel a configuracao real de VAPID e disponibilidade do browser/OS do cliente;
- `backend/vendor` continua ausente neste ambiente, entao a fase foi validada por lint/build e testes adicionados sem execucao local da suite Laravel.

## Atualizacao Fase 6 - 2026-05-13

### Decisao canonica

- bonus aniversario:
  - configuracao permanece em `bonus_aniversario`
  - validacao anual por cliente/empresa foi separada em `bonus_aniversario_resgates`
  - trilha canonica de negocio em `BonusAniversarioService`
  - controller canonico em `BonusAniversarioController`
- lembrete de retorno:
  - configuracao permanece em `lembretes_ausencia`
  - historico operacional foi separado em `lembrete_envios`
  - trilha canonica de negocio em `LembreteRetornoService`
  - controller canonico em `LembreteRetornoController`
- push:
  - reaproveita `push_subscriptions`, `notificacoes_push`, VAPID e `minishlink/web-push`
  - helper novo `WebPushDeliveryService` para consolidar entrega por subscription
- QR:
  - o lookup canonico continua em `POST /api/empresa/clientes/qrcode/consultar`
  - `QRCodeController` legado nao foi reativado

### Tabelas usadas/criadas na Fase 6

- Reaproveitadas:
  - `bonus_aniversario`
  - `lembretes_ausencia`
  - `inscricoes_empresa`
  - `push_subscriptions`
  - `notificacoes_push`
- Criadas/estendidas:
  - `2026_05_13_000000_extend_bonus_aniversario_for_phase6.php`
    - adiciona `dias_validade`, `notification_title`, `notification_body`
  - `2026_05_13_000100_create_bonus_aniversario_resgates_table.php`
    - cria `bonus_aniversario_resgates`
    - unicidade por `bonus_aniversario_id + user_id + ano`
    - unicidade operacional adicional por `empresa_id + user_id + ano`
  - `2026_05_13_000200_extend_lembretes_ausencia_for_phase6.php`
    - adiciona `dias_sem_visita`
  - `2026_05_13_000300_create_lembrete_envios_table.php`
    - cria `lembrete_envios`
    - unicidade por `lembrete_id + user_id + reference_last_visit_at`
  - `2026_05_13_000400_extend_notificacoes_push_for_phase6.php`
    - adiciona `bonus_aniversario_id` e `lembrete_id`

### Endpoints criados ou alterados na Fase 6

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
- Publico:
  - `GET /api/empresas/{id}`
    - agora tambem pode expor preview do bonus aniversario configurado
- Fluxo compartilhado de QR:
  - `POST /api/empresa/clientes/qrcode/consultar`
    - agora devolve snapshot de `bonus_aniversario`

### Fluxo funcional final da Fase 6

#### 1. Configuracao pela empresa

- Empresa `active + ativo = true` gerencia bonus aniversario e lembrete em `gest_o_de_ofertas_parceiro.html`.
- Empresa `pending`, `suspended` ou `rejected` continua bloqueada pelo middleware `subscription.check`.

#### 2. Elegibilidade de aniversario

- Fonte de dado: `users.data_nascimento`.
- Regra MVP:
  - sem `dias_validade`, cliente elegivel durante o mes do aniversario;
  - com `dias_validade`, cliente elegivel da data de aniversario ate `N` dias.
- Controle de uso:
  - uma vez por ano por empresa.

#### 3. Validacao presencial via QR do cliente

- Empresa abre `/validar_resgate.html?modo=beneficios`.
- O lookup canonico do QR passa a devolver:
  - `bonus_adesao`
  - `bonus_aniversario`
  - `cartao_fidelidade`
  - `promocoes`
- Se o bonus aniversario estiver elegivel:
  - empresa chama `POST /api/empresa/bonus-aniversario/{id}/validar`
  - backend grava em `bonus_aniversario_resgates`
  - atualiza `inscricoes_empresa.ultima_visita`
  - bloqueia segundo uso no mesmo ano.

#### 4. Push de aniversario

- Disparo manual por `POST /api/empresa/bonus-aniversario/{id}/enviar-elegiveis`.
- O envio:
  - considera apenas clientes vinculados e elegiveis;
  - ignora clientes ja resgatados no ano;
  - busca subscriptions em `push_subscriptions`;
  - registra resultado em `notificacoes_push`;
  - continua mesmo se um cliente falhar ou nao tiver subscription.

#### 5. Lembrete de retorno

- Configuracao por empresa com `dias_sem_visita`, titulo, mensagem e status.
- Inatividade calculada por:
  - `inscricoes_empresa.ultima_visita` como fonte principal;
  - fallback para historicos canonicos de fidelidade, bonus de adesao, promocao e bonus aniversario.
- Disparo manual por `POST /api/empresa/lembrete-retorno/enviar-elegiveis`.
- Anti-spam MVP:
  - nao reenviar para o mesmo cliente enquanto `reference_last_visit_at` nao mudar.

### Frontend alterado na Fase 6

- `detalhe_do_parceiro.html`
  - card de bonus aniversario com estado individual do cliente quando autenticado
  - bloco explicativo de lembrete de retorno
- `gest_o_de_ofertas_parceiro.html`
  - formularios de bonus aniversario e lembrete
  - envio manual para elegiveis
- `validar_resgate.html`
  - bloco dedicado ao bonus aniversario dentro do scanner multiproduto
- `stitch-app.js`
  - integra empresa, cliente e scanner sem criar novo shell paralelo

### Como testar a Fase 6

- Empresa:
  - criar/editar/ativar/desativar bonus aniversario;
  - criar/editar/ativar/desativar lembrete;
  - disparar envios manuais para elegiveis.
- Cliente:
  - manter `data_nascimento` preenchida;
  - vincular-se a empresa;
  - abrir `detalhe_do_parceiro.html?id={empresa}` e conferir o card.
- Scanner:
  - abrir `/validar_resgate.html?modo=beneficios`;
  - escanear o QR dinamico do cliente;
  - validar bonus aniversario;
  - repetir e confirmar bloqueio anual.
- Reminder:
  - ajustar `ultima_visita` ou historico equivalente;
  - disparar envio;
  - confirmar `lembrete_envios` e `notificacoes_push`.

### Riscos remanescentes apos a Fase 6

- `data_nascimento` ainda nao e coletada no cadastro publico atual;
- o scheduler/cron de envio automatico nao foi implementado nesta fase;
- `stitch-app.js` continua como concentrador de jornadas e merece modularizacao futura;
- push web segue dependente de VAPID/subscription real;
- `backend/vendor` continua ausente neste ambiente, entao a validacao local ficou em lint, build do bundle e testes adicionados sem execucao da suite Laravel.

## Atualizacao Fase 7 - 2026-05-14

### Escopo efetivamente fechado

- cadastro publico de cliente com `data_nascimento`;
- avaliacoes canonicas por cliente vinculado e por empresa;
- resumo operacional da empresa;
- lista canonica de clientes vinculados da empresa;
- resumo administrativo com rankings simples;
- preservacao das Fases 0 a 6 sem reabrir os fluxos funcionais anteriores.

### Arquivos auditados e/ou estabilizados na Fase 7

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
- `backend/public/clientes_fidelizados_loja.html`
- `backend/public/relat_rios_gerais_master.html`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`

### Verificacoes estruturais executadas

- `git diff --stat`
- `git diff --check`
- `php -l` em todos os PHP alterados/untracked do working tree
- `node --check backend/public/js/stitch-app.js`
- auditoria de rotas em `backend/routes/api.php`
- busca de classes/services duplicados para `AvaliacaoService` e `RelatorioOperacionalService`
- conferencia de imports e namespaces dos controllers afetados
- conferencia de referencias de services nos controllers afetados
- conferencia de protecao por role nas rotas novas/alteradas

### Resultado da auditoria estrutural

- `routes/api.php` permaneceu sintaticamente consistente.
- Nao foram encontrados arquivos duplicados para:
  - `AvaliacaoService`
  - `RelatorioOperacionalService`
- `AvaliacaoController`, `EmpresaAPIController` e `AdminReportController` referenciam services existentes.
- As rotas de empresa permanecem protegidas por:
  - `auth:sanctum`
  - `role.permission:empresa`
  - `subscription.check`
- As rotas de admin permanecem protegidas por:
  - `auth:sanctum`
  - `role.permission:admin`
- As rotas de avaliacao do cliente permanecem protegidas por:
  - `auth:sanctum`
  - `role.permission:cliente`

### Regras finais de cadastro publico

- `criar_conta.html` envia `data_nascimento` apenas quando `perfil = cliente`.
- `stitch-app.js` remove `data_nascimento` do payload quando o cadastro e de `empresa`.
- `AuthController`:
  - valida `data_nascimento` como obrigatorio, data valida e anterior a hoje apenas para `cliente`;
  - persiste `data_nascimento` apenas no fluxo de `cliente`;
  - preserva o fluxo de `empresa` sem contaminacao desse campo.

### Regras finais de avaliacao

- cliente precisa estar autenticado e vinculado a empresa para avaliar;
- empresa precisa estar publicamente apta para receber avaliacao;
- nota obrigatoria entre `1` e `5`;
- comentario opcional;
- apenas uma avaliacao por cliente por empresa;
- `POST` cria a avaliacao inicial;
- `PUT /empresas/{id}/avaliacoes/minha` edita a avaliacao ja existente;
- `GET /api/empresas/{id}/avaliacoes` expoe:
  - media;
  - total;
  - distribuicao;
  - avaliacoes recentes;
- `GET /api/empresa/avaliacoes` lista apenas avaliacoes recebidas pela propria empresa.

### Regras finais de relatorio da empresa

- endpoint canonico:
  - `GET /api/empresa/relatorios/resumo`
- lista canonica de clientes:
  - `GET /api/empresa/clientes`
- o resumo agrega:
  - total de clientes vinculados;
  - novos clientes no mes;
  - aniversariantes do mes;
  - clientes inativos;
  - bonus de adesao resgatados;
  - pontos distribuidos;
  - recompensas resgatadas;
  - promocoes criadas;
  - promocoes resgatadas;
  - notificacoes enviadas;
  - media de avaliacoes;
  - total de avaliacoes.
- a lista de clientes retorna:
  - nome;
  - email;
  - telefone;
  - data_nascimento;
  - data_vinculo;
  - ultima_visita;
  - pontos_atuais;
  - dias_inatividade;
  - status_inatividade.
- quando nao houver lembrete configurado, a inatividade passa a usar fallback operacional de `30` dias para nao zerar artificialmente o relatorio.

### Regras finais de relatorio admin

- endpoint canonico:
  - `GET /api/admin/relatorios/resumo`
- retorno consolidado inclui:
  - total de empresas;
  - empresas `pending`;
  - empresas `active`;
  - empresas `suspended`;
  - empresas `rejected`;
  - total de clientes;
  - total de vinculos cliente x empresa;
  - total de promocoes;
  - total de resgates;
  - total de notificacoes;
  - media geral de avaliacoes;
  - ranking simples de empresas por clientes;
  - ranking simples de empresas por resgates.

### Ajustes frontend realmente necessarios

- `dashboard_parceiro.html` e `clientes_fidelizados_loja.html` continuam consumindo os endpoints canonicos da empresa sem mudanca visual relevante.
- `dashboard_admin_master.html` passou a aceitar `GET /api/admin/relatorios/resumo` como fonte preferencial para KPIs consolidados.
- `relat_rios_gerais_master.html` passou a renderizar:
  - empresas por status;
  - top empresas por clientes;
  - top empresas por resgates;
  - metricas administrativas consolidadas.
- `stitch-app.js` recebeu apenas correcoes funcionais e tratamento de fallback para manter a pagina operacional quando um endpoint falha.

### Validacao executada nesta sessao

- `git diff --check`
- `php -l` nos PHP alterados
- `node --check backend/public/js/stitch-app.js`
- rebuild do bundle minificado quando `stitch-app.js` foi alterado

### Limitacoes desta validacao

- `php artisan test` nao pode ser executado neste ambiente enquanto `backend/vendor` permanecer ausente.
- `composer` tambem nao estava disponivel no PATH local na tentativa de regenerar autoload.

### Como testar manualmente a Fase 7

- Cadastro publico de cliente:
  - abrir `/criar_conta.html`;
  - selecionar `cliente`;
  - enviar `data_nascimento`;
  - validar no retorno/API que o usuario foi criado com o campo persistido.
- Cadastro de empresa:
  - abrir `/criar_conta.html?tipo=empresa`;
  - enviar cadastro de empresa;
  - validar que `data_nascimento` nao foi enviado nem persistido.
- Avaliacao:
  - autenticar cliente vinculado;
  - abrir `/detalhe_do_parceiro.html?id={empresa}`;
  - criar avaliacao com nota `1..5`;
  - repetir `POST` e confirmar bloqueio de duplicidade;
  - editar via formulario e confirmar sucesso no `PUT`.
- Relatorio da empresa:
  - autenticar empresa ativa;
  - abrir `/dashboard_parceiro.html`;
  - validar cards do resumo operacional;
  - abrir `/clientes_fidelizados_loja.html`;
  - validar data de nascimento, ultima visita, pontos e status de inatividade.
- Relatorio admin:
  - autenticar admin;
  - abrir `/dashboard_admin_master.html` e `/relat_rios_gerais_master.html`;
  - confirmar status de empresas, totais gerais e rankings simples.

### Riscos remanescentes apos a Fase 7

- os relatorios dependem da consistencia historica entre tabelas legadas e tabelas canonicas reaproveitadas;
- `stitch-app.js` continua concentrando muitas jornadas e ainda merece modularizacao futura;
- a minificacao do bundle publico continua sendo um passo operacional separado;
- a validacao automatizada completa em Laravel segue bloqueada neste ambiente por ausencia de dependencias instaladas.

## Stack identificada

### Aplicacao principal identificada

- Backend principal: PHP 8.2+ com Laravel 11.
- API principal: Laravel REST em `backend/routes/api.php`.
- Frontend ativo: HTML em `backend/public/*.html` com Tailwind via CDN e integracao JS em `backend/public/js/stitch-app.js`.
- Build frontend auxiliar: Vite em `backend/package.json` com `resources/css/app.css` e `resources/js/app.js`.
- Autenticacao de API: Laravel Sanctum.
- Suporte adicional instalado: `tymon/jwt-auth`.
- Banco padrao: PostgreSQL.
- Cache/fila opcionais: Redis.
- QR Code: `simplesoftwareio/simple-qrcode`.
- Push web: `minishlink/web-push`.

### Base paralela identificada

- Existe uma API Node.js/Express/Prisma em `backend/api/`.
- Essa base tem schema, auth e endpoints proprios.
- Nao ha evidencia de que ela seja o deploy principal atual.
- Decisao da Fase 0: manter como legado paralelo fora do caminho principal.

## Estrutura de pastas

- `backend/app`: controllers, models, services, jobs, middleware e regras de dominio.
- `backend/routes`: `web.php`, `api.php`, `console.php`.
- `backend/database/migrations`: schema incremental.
- `backend/public`: paginas HTML, assets, `manifest.json`, `sw-push.js`.
- `backend/resources`: assets compilaveis via Vite e views auxiliares.
- `backend/config`: auth, database, queue, cache, privacy e afins.
- `backend/storage`: uploads e artefatos publicos.
- `backend/tests`: testes de feature, unidade e smoke.
- `backend/openapi`: `v1.yaml` e `v2.yaml`.
- `backend/api`: backend paralelo legado.

Classificacao:

- Estrutura Laravel principal: `reaproveitar`.
- HTML + `stitch-app.js`: `reaproveitar/adaptar`.
- `backend/api`: `depreciar do fluxo principal`.

## Linguagem, backend e frontend usados

- Linguagem principal: PHP.
- Framework backend principal: Laravel.
- Padrao dominante: API REST JSON.
- Frontend funcional atual:
  - HTML server/static pages;
  - Tailwind via CDN;
  - JavaScript vanilla centralizado em `stitch-app.js`.
- Nao foi identificado React, Vue ou Next.js como base publica dominante.

Classificacao:

- Laravel + API atual: `reaproveitar`.
- HTML + `stitch-app.js`: `reaproveitar/adaptar`.
- Reescrita em segunda aplicacao frontend: `nao recomendada`.

## Autenticacao atual

### Guards e middleware

- `web` por sessao.
- `api` por Sanctum.
- Middlewares de permissao, role, resposta JSON forcada, saneamento e throttling.

### Fluxos observados

- Registro publico: `/api/auth/register`
- Login: `/api/auth/login`
- Recuperacao de senha: `/api/auth/forgot-password`, `/api/auth/reset-password`
- Sessao autenticada: `/api/me`, `/api/auth/me`, `/api/perfil`, `/api/logout`

### Como usuarios sao cadastrados hoje

- Cliente:
  - cadastro publico em `criar_conta.html` e `/api/auth/register`;
  - campos reais observados no formulario atual:
    - `name`
    - `email`
    - `telefone`
    - `password`
    - `password_confirmation`
    - aceite de termos
    - codigo de indicacao opcional
- Empresa:
  - o fluxo publico atual informa que o cadastro de estabelecimento depende do admin apos pagamento externo;
  - `AuthController` bloqueia criacao publica de empresa;
  - hoje a empresa e criada por admin/master.
- Admin:
  - login e criacao interna existentes.

Classificacao:

- Login, logout e reset: `reaproveitar`.
- Cadastro publico de cliente: `reaproveitar`.
- Cadastro publico de empresa pendente: `nao implementado ainda`.

## Roles/permissoes existentes

- Roles identificadas:
  - `cliente`
  - `empresa`
  - `admin`
- Evidencias:
  - validacao em `AuthController`;
  - `RolePermissionMiddleware`;
  - `AdminPermissionMiddleware`;
  - `User::hasPermission()`.

Classificacao:

- Modelo base de roles: `reaproveitar`.
- Politica funcional de aprovacao de empresa: `adaptar/criar`.

## Models/tabelas existentes

### Entidades principais identificadas

- Usuarios e acesso:
  - `User`
  - `Admin`
  - `PersonalAccessToken`
  - `DeviceFingerprint`
  - `DataPrivacyRequest`
- Empresas e relacionamento:
  - `Empresa`
  - `InscricaoEmpresa`
  - `QRCode`
  - `CompanySubscription`
  - `SubscriptionPlan`
  - `CompanyLoyaltyConfig`
- Fidelidade e recompensas:
  - `Ponto`, `PontoTransacao`, `Ledger`
  - `BonusAdesao`
  - `CartaoFidelidade`
  - `CartaoFidelidadeProgresso`
  - `CartaoFidelidadeMovimento`
  - `Promocao`
  - `RedemptionIntent`
  - `CheckIn`
- Engajamento:
  - `BonusAniversario`
  - `LembreteAusencia`
  - `Avaliacao`
  - `Notification`
  - `PushSubscription`
  - `Desafio`
  - `DesafioProgresso`
  - `NpsResposta`
- Catalogo e conteudo:
  - `Produto`
  - `Banner`
  - `Categoria`
  - `Segmento`
- Financeiro/operacao:
  - `Invoice`
  - `BillingEvent`
  - `BillingNotification`
  - `FraudRule`
  - `FraudAlert`
  - `WebhookSaida`
  - `WebhookLog`

### Observacoes relevantes

- `Empresa` ja cobre perfil comercial, contatos e agregados de avaliacao.
- `InscricaoEmpresa` ja representa bem o vinculo cliente/empresa.
- `Promocao`, `Avaliacao`, `BonusAdesao`, `LembreteAusencia` e `CompanyLoyaltyConfig` ja cobrem boa parte do dominio futuro.
- `BonusAniversario` e `CartaoFidelidade` ainda precisam revisao funcional posterior.

### QR Code: leitura correta do schema

- No fluxo real de migrations do Laravel, `qr_codes` nasce primeiro em 2024 com perfil de QR da empresa:
  - `empresa_id`
  - `code`
  - `active`
- A migration posterior de 2025 para `qr_codes` usa `if (!Schema::hasTable(...))` e nao reestrutura a tabela em bases novas que ja passaram pela migration de 2024.
- Conclusao canonica:
  - `qr_codes` deve ser tratado como repositrio de QR da empresa;
  - QR do cliente deve permanecer fora da tabela, via `ClienteQrCodeService`.

Classificacao:

- `User`, `Empresa`, `InscricaoEmpresa`, `Promocao`, `Avaliacao`, `PushSubscription`, `CompanyLoyaltyConfig`: `reaproveitar`.
- `QRCode`, `BonusAniversario`, `CartaoFidelidade`: `adaptar`.

## Migrations existentes

### Estado geral

- A base cobre usuarios, empresas, fidelidade, promocoes, QR codes, inscricoes, avaliacoes, push, billing, notificacoes, fraud e privacy.

### Grupos principais observados

- Base Laravel: `users`, `cache`, `sessions`, `jobs`, `failed_jobs`, `personal_access_tokens`.
- Estrutura principal: `empresas`, `pontos`, `produtos`, `categorias`, `banners`.
- Fidelidade: `qr_codes`, `inscricoes_empresa`, `bonus_adesao`, `cartoes_fidelidade`, `cartoes_fidelidade_progresso`, `promocoes`, `bonus_aniversario`, `lembretes_ausencia`, `avaliacoes`.
- Push/notificacoes: `push_subscriptions`, `notifications`, `notificacoes_push`.
- Billing/operacao: `subscriptions`, `invoices`, `billing_notifications`, `billing_events`, `redemption_intents`, `ledger`.
- Privacidade/seguranca: consentimentos, fingerprint e controles de privacidade.

### Ajuste novo da Fase 0

- Migration adicionada:
  - `2026_05_07_000000_add_operational_status_to_empresas_table.php`

Finalidade:

- garantir `empresas.status` quando ausente;
- normalizar valores legados para:
  - `pending`
  - `active`
  - `suspended`
  - `rejected`

Classificacao:

- Base de migrations atual: `reaproveitar`.
- Ajuste de status de empresa: `executado na Fase 0`.
- Novas migrations paralelas para QR ou empresa: `nao recomendadas`.

## Endpoints/API existentes

### Publicos confirmados

- Saude:
  - `/api/ping`
  - `/api/health`
  - `/api/metrics`
- Documentacao:
  - `/api/docs/openapi`
  - `/api/docs/openapi/{version}`
- Auth:
  - `/api/auth/register`
  - `/api/auth/login`
  - `/api/auth/forgot-password`
  - `/api/auth/reset-password`
- Conteudo:
  - `/api/empresas`
  - `/api/empresas/{id}`
  - `/api/empresas/{id}/promocoes`
  - `/api/banners`
  - `/api/categorias`
  - `/api/empresas/{empresaId}/produtos`

### Autenticados relevantes

- Comuns:
  - `/api/me`
  - `/api/auth/me`
  - `/api/perfil`
  - `/api/logout`
  - `/api/notifications*`
  - `/api/push/subscribe`
  - `/api/push/unsubscribe`
- Cliente:
  - `/api/cliente/meu-qrcode`
  - `/api/cliente/dashboard`
  - `/api/cliente/empresas`
  - `/api/cliente/empresas/{id}`
  - `/api/cliente/escanear-qrcode`
  - `/api/cliente/promocoes`
  - `/api/cliente/promocoes/{id}/resgatar`
  - `/api/cliente/avaliar`
  - `/api/cliente/historico-pontos`
  - `/api/cliente/ranking-pontos`
- Empresa:
  - `/api/empresa/escanear-cliente`
  - `/api/empresa/dashboard`
  - `/api/empresa/perfil`
  - `/api/empresa/fidelidade/config`
  - `/api/empresa/clientes`
  - `/api/empresa/promocoes`
  - `/api/empresa/resgates`
  - `/api/empresa/qrcodes`
  - `/api/empresa/avaliacoes`
  - `/api/empresa/relatorio-pontos`
- Admin:
  - `/api/admin/dashboard-stats`
  - `/api/admin/recent-activity`
  - `/api/admin/users-report`
  - `/api/admin/reports/export`
  - `/api/admin/content/*`
  - `/api/admin/settings`
  - `/api/admin/campanhas`
  - `/api/admin/tickets*`

### Endpoints canonicos de QR apos a Fase 0

- QR do cliente:
  - `/api/cliente/meu-qrcode`
  - `WalletController::validarQRCode(...)`
  - `/api/empresa/escanear-cliente`
- QR da empresa:
  - `/api/empresa/qrcodes`
  - `/api/cliente/escanear-qrcode`
  - geracao via `QRCodeService`

### Risco remanescente de endpoints

- Ainda existem rotas/controladores legados de QR coexistindo com o caminho canonico.
- Esses fluxos devem ser consolidados antes de a Fase 2 crescer demais.

## Controllers/services existentes

### Controllers relevantes

- `AuthController`
- `EmpresaController`
- `QRCodeController`
- `PushSubscriptionController`
- `NotificationController`
- `AdminContentController`
- `AdminSettingsController`
- `ClienteAPIController`
- `EmpresaAPIController`
- `WalletController`

### Services relevantes

- `ClienteQrCodeService`
- `QRCodeService`
- `LoyaltyProgramService`
- `RedemptionService`
- `BillingService`
- `AnalyticsService`
- `FraudDetectionService`
- `LeaderboardService`
- `NotificationService`
- `FirebaseNotificationService`
- `WebhookService`

Classificacao:

- Services e controllers por perfil: `reaproveitar`.
- Rotas/paths legados de QR: `adaptar/consolidar`.

## Telas existentes

### Paginas HTML identificadas em `backend/public`

- Publicas/auth:
  - `index.html`
  - `entrar.html`
  - `acessar_conta.html`
  - `criar_conta.html`
  - `forgot_password.html`
  - `reset_password.html`
  - `escolher-tipo.html`
- Cliente:
  - `meus_pontos.html`
  - `parceiros_tem_de_tudo.html`
  - `detalhe_do_parceiro.html`
  - `recompensas.html`
  - `hist_rico_de_uso.html`
  - `meu_perfil.html`
  - `configuracoes_cliente.html`
- Empresa:
  - `dashboard_parceiro.html`
  - `clientes_fidelizados_loja.html`
  - `gest_o_de_ofertas_parceiro.html`
  - `minhas_campanhas_loja.html`
  - `validar_resgate.html`
  - `dashboard-empresa.html`
- Admin:
  - `dashboard_admin_master.html`
  - `gest_o_de_estabelecimentos.html`
  - `gest_o_de_clientes_master.html`
  - `gest_o_de_usu_rios_master.html`
  - `relat_rios_gerais_master.html`
  - `configuracoes_admin.html`
  - `banners_e_categorias_master.html`
  - `tickets_admin_master.html`
  - `dashboard-admin.html`

## Estado atual do PWA

### O que existe

- `backend/public/manifest.json`
- `backend/public/sw-push.js`
- registro de service worker em `stitch-app.js`
- icones em `backend/public/img`

### Manifest apos a Fase 0

- `display: standalone`
- `orientation: portrait-primary`
- `start_url: /index.html`
- atalhos:
  - `/meus_pontos.html`
  - `/parceiros_tem_de_tudo.html`
  - `/meu_perfil.html`

### Estado do service worker

- `sw-push.js` permanece focado em:
  - `push`
  - `notificationclick`
- Nao existe ainda estrategia offline robusta.

Classificacao:

- Manifest: `estabilizado minimamente`.
- Service worker para push: `reaproveitar`.
- Offline/PWA completo: `adaptar no futuro`.

## Push notification

- Infra identificada:
  - `PushSubscriptionController`
  - `SendWebPushJob`
  - `PushSubscription`
  - `Notification`
  - endpoint `/api/push/public-key`
- Legado paralelo:
  - `FirebaseNotificationService`
  - `fcm_token` em `users`

Classificacao:

- Web Push para PWA: `reaproveitar`.
- Duplicidade Web Push x FCM: `adaptar/consolidar`.

## Upload de imagens

- Uploads por `Storage::disk('public')`.
- Uso em banners, promocoes, bonus e QR.
- `QRCodeService` salva PNGs em `storage/app/public/qrcodes/...`.
- O startup/deploy cria `storage:link`.

Classificacao:

- Pipeline de upload/storage: `reaproveitar`.

## Deploy/configuracao

- Artefatos:
  - `Dockerfile`
  - `Procfile`
  - `railway.json`
  - `.github/workflows/backend-ci.yml`
- Leitura pratica:
  - deploy atual sobe Laravel/PHP;
  - `Procfile` aponta para `backend/public/index.php`;
  - CI instala composer, gera chave e roda testes.

Classificacao:

- Pipeline Laravel atual: `reaproveitar`.
- Backend Node como deploy principal: `nao`.

## Variaveis de ambiente necessarias

Confirmadas em `backend/.env.example`:

- aplicacao: `APP_*`
- banco: `DB_*`, `DATABASE_URL`
- sessao/cache/fila: `SESSION_*`, `CACHE_*`, `QUEUE_*`, `REDIS_*`
- e-mail: `MAIL_*`
- auth/JWT: `JWT_SECRET`
- push web: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`
- firebase/fcm: variaveis relacionadas
- storage/cloud: `AWS_*`
- billing/webhook: variaveis relacionadas
- OpenAI: variaveis relacionadas
- observabilidade: `SENTRY_*`
- startup/operacao: flags de migracao, scheduler, worker e smoke

## Banco de dados usado

- Banco padrao: PostgreSQL.
- Suporte adicional previsto: SQLite, MySQL/MariaDB, SQL Server.
- Testes automatizados da base usam SQLite em memoria.

Classificacao:

- PostgreSQL como principal: `reaproveitar`.
- Redis como complemento: `reaproveitar quando necessario`.

## Dependencias principais

### PHP/Composer

- `laravel/framework`
- `laravel/sanctum`
- `tymon/jwt-auth`
- `simplesoftwareio/simple-qrcode`
- `minishlink/web-push`

### Node do backend Laravel

- `vite`
- `tailwindcss`
- `axios`
- `laravel-vite-plugin`

### Base paralela `backend/api`

- `express`
- `prisma`
- `jsonwebtoken`
- `bcryptjs`
- `cors`
- `cookie-parser`

## O que pode ser reaproveitado

- autenticacao Laravel com Sanctum;
- middleware de role/permissao;
- `User`, `Empresa`, `InscricaoEmpresa`;
- `ClienteQrCodeService`;
- `QRCodeService` ja alinhado ao QR da empresa;
- base de promocoes, avaliacoes, bonus, loyalty config e relatorios;
- upload/storage publico;
- Web Push;
- dashboards HTML existentes e `stitch-app.js`;
- deploy atual em Laravel.

## O que precisa ser adaptado

- fluxo funcional de cadastro/aprovacao de empresa;
- consolidacao final de endpoints legados de QR;
- coleta publica de `data_nascimento`;
- experiencia PWA completa;
- segmentacao e limite de notificacoes;
- modelagem final de aniversario e fidelidade.

## O que precisa ser criado

- fila admin de aprovacao de empresa;
- metadados de aprovacao/rejeicao em empresa, se faltarem;
- home do cliente aderente ao contrato i9Plus;
- pagina publica de empresa aderente ao contrato novo;
- fluxo funcional de vinculo cliente/empresa sobre o QR canonico;
- historico operacional de lembretes/notificacoes quando a fase correspondente chegar.

## O que pode ser removido ou depreciado

- evolucao paralela de `backend/api` para o mesmo produto;
- rotas/fluxos legados de QR fora do caminho canonico, apos consolidacao segura;
- referencias antigas de PWA ja removidas do manifest.

## Riscos tecnicos

- Maior risco remanescente: coexistencia de endpoints e fluxos legados de QR alem do caminho canonico.
- Segundo maior risco: existencia de duas bases backend no mesmo repositorio.
- Risco de regressao em cadastro de empresa:
  - configuracao administrativa sugere uma coisa;
  - `AuthController` ainda bloqueia auto cadastro publico.
- Risco de migracoes/tabelas legadas de fidelidade gerarem nova camada paralela se a Fase 1/2 nao mantiverem disciplina.
- Risco de push duplicado por coexistencia de Web Push e FCM legado.

## Como testar

- Executar `php artisan migrate`.
- Executar:
  - `php artisan test tests/Feature/ClienteQrCodeServiceTest.php`
  - `php artisan test tests/Feature/CompanyQrCodeServiceTest.php`
  - `php artisan test tests/Feature/CompanyOperationalStatusTest.php`
  - `php artisan test tests/Feature/ManifestContractsTest.php`
  - `php artisan test tests/Feature/PainelSmokeTest.php`
- Validar manualmente:
  - `GET /api/empresas` so lista empresas `active`;
  - `GET /api/empresas/{id}` retorna `404` para empresa nao publica;
  - `GET /api/cliente/meu-qrcode` continua gerando QR assinado;
  - QR da empresa continua acessivel via fluxos Laravel existentes;
  - `manifest.json` aponta apenas para arquivos reais.

## Pendencias

- Consolidar definitivamente rotas/endpoints legados de QR.
- Confirmar quais endpoints/admin ja cobrem aprovacao, rejeicao e suspensao de empresa.
- Manter `backend/api` fora do caminho principal nas fases seguintes.
- Validar todos os fluxos de resgate para garantir que nenhum permita conclusao apenas pelo cliente.
- Mapear em fase funcional quais tabelas legadas de fidelidade seguem canonicas e quais sao sobra historica.

## Atualizacao Fase 8 - 2026-05-14

### Escopo executado

- auditoria final do working tree antes das mudancas;
- refinamento visual incremental mobile-first e desktop-first sem criar feature nova;
- limpeza tecnica segura de codigo morto e labels quebrados;
- marcacao explicita de rotas legadas de QR e compatibilidade admin;
- atualizacao da documentacao final de fechamento.

### Arquivos alterados na Fase 8

- `backend/app/Http/Controllers/AuthController.php`
- `backend/routes/api.php`
- `backend/public/css/i9plus-phase8.css`
- `backend/public/criar_conta.html`
- `backend/public/entrar.html`
- `backend/public/meus_pontos.html`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/validar_resgate.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/dashboard_parceiro.html`
- `backend/public/clientes_fidelizados_loja.html`
- `backend/public/dashboard_admin_master.html`
- `backend/public/relat_rios_gerais_master.html`
- `backend/public/gest_o_de_estabelecimentos.html`
- `docs/i9plus/TECH_AUDIT.md`
- `docs/i9plus/VISUAL_AUDIT.md`
- `docs/i9plus/IMPLEMENTATION_PLAN.md`

### Arquivos criados na Fase 8

- `backend/public/css/i9plus-phase8.css`

### Endpoints e rotas

- nenhum endpoint novo foi criado;
- nenhuma regra de negocio foi alterada;
- rotas legadas continuaram expostas apenas por compatibilidade;
- `QRCodeController` permaneceu legado e nao foi reativado como caminho canonico.

### Limpeza tecnica segura concluida

- remocao do gate morto `if (false)` em `AuthController.php`;
- remocao do gate morto inline em `criar_conta.html`;
- normalizacao do fechamento de `gest_o_de_ofertas_parceiro.html`;
- labels administrativos/operacionais ajustados em telas que exibiam nomes incoerentes com o fluxo real;
- rotas legadas receberam comentarios deprecando expansao futura.

### Riscos remanescentes apos a Fase 8

- `backend/vendor` continua ausente neste ambiente, entao a validacao Laravel completa segue bloqueada;
- `composer` nao estava disponivel no PATH deste ambiente, entao `composer install`, `php artisan migrate --pretend`, `php artisan route:list` e `php artisan test` nao puderam ser executados aqui;
- `stitch-app.js` continua monolitico e concentrando muito comportamento cliente/empresa/admin;
- as rotas legadas de `QRCodeController` permanecem publicadas por compatibilidade e devem ser removidas so com plano de migracao seguro;
- os relatorios continuam dependentes da consistencia historica entre tabelas legadas e canonicas;
- scheduler/cron para disparos automatizados continua pendente; os envios elegiveis seguem manuais por endpoint.
- `backend/public/manifest.json` foi saneado nesta etapa, mas a experiencia PWA continua dependente da curadoria futura de screenshots reais caso a operacao queira enriquecer instalacao e marketplace listing;

### Fechamento atual das fases

- Fases 0 a 7: preservadas.
- Fase 8: concluida no escopo funcional e visual definido.
- Validacao automatizada: parcial no ambiente, condicionada a restaurar `backend/vendor`.

### Instrucoes de deploy e pre-subida

- `composer install`
- `php artisan migrate`
- `php artisan test`

Comandos adicionais quando houver pipeline frontend real:

- rebuild do bundle somente se `backend/public/js/stitch-app.js` for alterado;
- publicar assets estaticos conforme processo do ambiente.

### Variaveis importantes para operacao

- `APP_*`
- `DB_*`
- `SESSION_*`, `CACHE_*`, `QUEUE_*`, `REDIS_*`
- `MAIL_*`
- `JWT_SECRET`
- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `VAPID_SUBJECT`
- credenciais de push/FCM existentes, se usadas no ambiente

### Observacao operacional

- `backend/api` Node/Express/Prisma segue fora do caminho principal;
- `QRCodeController` segue deprecated/legado;
- o manifest PWA ficou consistente para commit seletivo, mas ainda pode receber screenshots reais em etapa futura de acabamento;
- scheduler/cron ainda depende de configuracao futura se a operacao quiser disparos automativos de aniversario ou retorno sem acao manual.

## Saneamento Pre-Commit - 2026-05-14

### Manifest

- `backend/public/manifest.json` foi saneado sem gerar asset novo;
- a chave opcional `screenshots` foi removida temporariamente porque `/img/screenshot-mobile.png` nao existe neste checkout;
- o atalho `Meu Perfil` passou a apontar para `/img/icon-96.png`, que existe em `backend/public/img`;
- status atual:
  - JSON valido;
  - assets atualmente referenciados existem;
  - `start_url` e os targets de `shortcuts` continuam presentes no `public/`.

### Arquivos sensiveis e historicos

- `backend/.env.local`
  - `tracked`
  - mantido fora deste commit seletivo;
  - acao segura recomendada, somente com confirmacao humana:
    - `git rm --cached backend/.env.local`
- `ITEM_3_SEGURANCA_CONCLUIDO.md`
  - `tracked`
  - expunha `JWT_SECRET` real em texto;
  - saneado nesta etapa com placeholder:
    - `JWT_SECRET=[DEFINIR_APENAS_NO_AMBIENTE]`
- `*.sql`
  - atualmente ha varios arquivos `tracked` no repositorio;
  - devem ficar fora do commit atual;
  - nao remover automaticamente sem revisao humana.
- `backend/node_modules`
  - `untracked`
  - manter fora do commit;
  - coberto por `.gitignore`.

### Regras para commit seletivo

- nao usar `git add .` enquanto existirem artefatos sensiveis/historicos tracked no repositorio;
- usar `git add` seletivo apenas para arquivos funcionais/documentais auditados;
- manter fora do staging:
  - `.env*`
  - `*.sql`
  - `node_modules/`
  - `vendor/`
  - dumps, logs e backups locais

### Comandos obrigatorios em ambiente com Composer

```bash
cd backend
composer install
php artisan migrate --pretend
php artisan route:list
php artisan test
```
