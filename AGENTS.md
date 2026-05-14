# AGENTS.md

## Contexto do projeto

Este repositorio ja possui uma base existente com PWA, frontend, backend/API e deploy funcional.

A tarefa principal NAO e criar um sistema novo do zero.

A tarefa principal e adaptar a base existente para atender uma demanda de aplicativo/PWA de fidelizacao inspirado no modelo i9Plus, reaproveitando ao maximo:

- autenticacao existente;
- estrutura de backend/API existente;
- modelos/tabelas ja disponiveis;
- componentes reutilizaveis;
- configuracao PWA;
- service worker;
- deploy;
- estrutura de uploads;
- estilos reaproveitaveis;
- rotas existentes;
- layout quando fizer sentido.

Antes de criar novos arquivos, novas tabelas, novas rotas ou nova arquitetura, sempre verificar se ja existe algo equivalente no projeto.

## Regra principal de arquitetura

Priorize adaptacao incremental e segura.

Nao reescreva o projeto inteiro.
Nao crie uma segunda aplicacao paralela.
Nao substitua a stack atual sem necessidade.
Nao remova funcionalidades existentes antes de entender se serao reaproveitadas.
Nao implemente pagamento online neste MVP.
Nao implemente app nativo neste momento.

## Experiencia por dispositivo

O sistema deve funcionar como:

- No mobile: experiencia de app/PWA instalado, mobile-first, com navegacao inferior, telas compactas, botoes grandes, cards e QR Code como acao central.
- No desktop: experiencia de site/painel web responsivo, com layout mais amplo, navegacao lateral ou superior quando fizer sentido, mantendo a mesma identidade visual.

Nao faca o desktop parecer apenas um celular gigante centralizado se a tela for administrativa.
Nao faca o mobile parecer dashboard desktop espremido.

## Perfis do sistema

A aplicacao deve usar o mesmo PWA/base para tres perfis:

- customer: cliente final;
- company: estabelecimento/empresa;
- admin: administrador geral.

Cada perfil deve ter rotas, permissoes e experiencias diferentes.

## Reaproveitamento

Antes de implementar, gerar auditoria tecnica.
A auditoria deve identificar:

- stack atual;
- estrutura de pastas;
- autenticacao atual;
- roles/permissoes existentes;
- tabelas/models existentes;
- APIs existentes;
- telas existentes;
- configuracao PWA existente;
- service worker;
- sistema de upload;
- notificacoes existentes, se houver;
- o que sera reaproveitado;
- o que sera adaptado;
- o que sera criado;
- riscos de quebra.

## Regra critica de validacao

Resgates nao podem ser concluidos somente pelo cliente.

A empresa deve validar bonus, promocao, recompensa e aniversario lendo o QR Code do cliente.

O cliente pode visualizar o beneficio, mas a confirmacao real deve ser feita pela empresa.

## Pagamento

Nao implementar pagamento online no MVP.

Fluxo comercial:
- empresa se cadastra;
- fica pendente;
- pagamento ocorre fora do sistema;
- admin aprova manualmente;
- empresa ativa ganha acesso.

## Notificacoes

Promocoes instantaneas e lembretes devem ser enviados apenas para clientes vinculados a empresa.

Implementar limite basico de envio por empresa para evitar spam.

## Ordem de trabalho

Sempre trabalhar em fases:

1. Preparacao de documentacao.
2. Auditoria tecnica e visual.
3. Plano de implementacao.
4. Base de roles, empresa e QR Codes.
5. Vinculo cliente/empresa e paginas principais.
6. Bonus e fidelidade.
7. Promocoes e push.
8. Aniversario, lembrete e avaliacoes.
9. Relatorios.
10. Refinamento visual.
11. Testes.

Nunca implementar todas as fases de uma vez sem plano.

## Entrega esperada por tarefa

Ao final de cada tarefa, informar:

- arquivos criados;
- arquivos alterados;
- migrations criadas;
- endpoints criados/alterados;
- componentes criados/alterados;
- comandos executados;
- testes realizados;
- pendencias reais.
