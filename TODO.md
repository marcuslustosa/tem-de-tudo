# TODO - Projeto Tem de Tudo

## Backend
- [x] Configuração do Express e Sequelize (app.js, config.js)
- [x] Modelos Sequelize para User, Company, Point, Review, Notification, Payment
- [x] Controladores e rotas para:
  - [x] Autenticação (registro, login)
  - [x] Gerenciamento de empresas (cadastro, aprovação, pagamento)
  - [x] Sistema de pontos (adicionar, resgatar, consultar)
  - [x] Avaliações e reviews
  - [x] Notificações para clientes e empresas
  - [x] Pagamentos de empresas anunciantes
  - [x] Geração e validação de QR Codes
- [ ] Integração real com Mercado Pago e PagSeguro (configuração e testes)
- [ ] Testes automatizados backend (unitários e integração)

## Frontend
- [x] Páginas principais:
  - [x] index.html (landing page)
  - [x] login.html (login de clientes)
  - [x] register.html (cadastro de clientes)
  - [x] profile-client.html (dashboard cliente)
  - [x] profile-company.html (dashboard empresa)
- [ ] Páginas adicionais:
  - [ ] estabelecimentos.html (catálogo de empresas e serviços)
  - [ ] contato.html (formulário de contato)
- [ ] Adaptação visual completa para esquema branco e roxo escuro conforme referências
- [ ] Implementação de chamadas AJAX para integração com backend
- [ ] Testes funcionais frontend

## Geral
- [ ] Configuração para rodar localmente com tokens e credenciais
- [ ] Documentação detalhada para deploy e uso
- [ ] Testes completos de todas funcionalidades
