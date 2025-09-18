# Plataforma Tem de Tudo

Uma plataforma completa para fidelidade de clientes e gestão de empresas, com backend em Node.js/Express, frontend estático e banco de dados PostgreSQL.

## Funcionalidades

- Cadastro e login de clientes e empresas
- Sistema de pontos de fidelidade
- Avaliações e comentários
- Notificações push
- Painel administrativo para gestão
- Integração com Mercado Pago e PagSeguro

## Tecnologias

- **Backend:** Node.js, Express, Sequelize, PostgreSQL
- **Frontend:** HTML, CSS, JavaScript
- **Deploy:** Render (web service)

## Instalação e Execução Local

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/tem-de-tudo.git
   cd tem-de-tudo
   ```

2. Instale as dependências:
   ```bash
   npm install
   ```

3. Configure as variáveis de ambiente:
   Crie um arquivo `.env` na raiz do projeto com:
   ```
   DB_DIALECT=postgres
   DB_HOST=localhost
   DB_PORT=5432
   DB_NAME=temdetudo
   DB_USER=seu_usuario_postgres
   DB_PASSWORD=sua_senha_postgres
   JWT_SECRET=sua_chave_secreta_jwt
   MERCADO_PAGO_TOKEN=seu_token_mercado_pago
   PAG_SEGURO_TOKEN=seu_token_pag_seguro
   ```

4. Execute o projeto:
   ```bash
   npm run dev
   ```

   O servidor estará rodando em `http://localhost:3000`.

## Deploy no Render.com

1. Faça push do código para um repositório no GitHub.

2. Conecte o repositório ao Render.com:
   - Acesse [render.com](https://render.com) e faça login.
   - Clique em "New" > "Web Service" e importe o repositório do GitHub.

3. Configure o serviço:
   - **Runtime:** Node
   - **Build Command:** npm install
   - **Start Command:** npm start
   - **Environment:** Production

4. Configure o banco de dados PostgreSQL no Render.com:
   - Crie um novo "PostgreSQL" database no painel do Render.
   - Copie a "Internal Database URL" ou "External Database URL".

5. Configure as variáveis de ambiente no painel do Render:
   - Vá para "Environment" no serviço web.
   - Adicione as seguintes variáveis (use valores reais para produção):
     - `DATABASE_URL`: URL do banco PostgreSQL (ex: postgresql://user:password@host:port/database)
     - `JWT_SECRET`: chave secreta para JWT
     - `MERCADO_PAGO_TOKEN`: token do Mercado Pago
     - `PAG_SEGURO_TOKEN`: token do PagSeguro

6. Implante:
   - O Render.com implantará automaticamente o projeto.
   - O frontend será servido estáticamente pelo Express, e o backend rodará no servidor.



## Estrutura do Projeto

- `backend/`: Código do servidor Express
- `frontend/`: Arquivos estáticos (HTML, CSS, JS)
- `backend/models/`: Modelos do Sequelize
- `backend/routes/`: Rotas da API
- `backend/controllers/`: Controladores da API
- `backend/middlewares/`: Middlewares (autenticação, etc.)
- `tests/`: Testes automatizados

## Scripts Disponíveis

- `npm start`: Inicia o servidor em produção
- `npm run dev`: Inicia o servidor em modo desenvolvimento (com nodemon)
- `npm test`: Executa os testes

## Contribuição

1. Faça fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## Autor

Marcus - Desenvolvedor PHP/Fullstack
