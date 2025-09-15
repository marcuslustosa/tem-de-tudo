# Projeto Tem de Tudo

## Visão Geral
Plataforma de fidelidade e vantagens para clientes e empresas, com cadastro, aprovação de empresas, sistema de pontos, recompensas, integração com pagamentos, geração de QR Codes, avaliações, notificações e dashboards.

## Tecnologias
- Backend: Node.js com Express
- Banco de Dados: SQLite com Sequelize ORM
- Frontend: HTML, CSS, JavaScript
- APIs REST para comunicação frontend-backend

## Estrutura do Projeto
- backend/
  - controllers/
  - models/
  - routes/
  - services/
  - app.js
- frontend/
  - css/
  - js/
  - index.html
- database/
  - database.sqlite
- config/
  - config.js

## Funcionalidades
- Cadastro e autenticação de clientes
- Cadastro e aprovação de empresas via perfil master
- Sistema de pagamento para liberação de empresas anunciantes
- Catálogo de empresas e serviços
- Sistema de pontos e recompensas
- Integração com Mercado Pago e PagSeguro (configuração para inserir tokens)
- Geração e leitura de QR Codes para fidelidade
- Avaliações e reviews
- Notificações para clientes e empresas
- Dashboards para clientes, empresas e perfil master
- Controle administrativo para aprovações e pagamentos

## Como rodar localmente
1. Configurar tokens e credenciais em `config/config.js`
2. Rodar `npm install` no backend
3. Rodar `node backend/app.js` para iniciar o servidor
4. Abrir `frontend/index.html` no navegador

## Próximos passos
- Desenvolvimento das funcionalidades backend
- Desenvolvimento do frontend com base no design i9Plus/Vipus
- Testes completos das funcionalidades
