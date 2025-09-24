# Tem de Tudo - Programa de Fidelidade 🎯# Plataforma Tem de Tudo



Sistema moderno de programa de fidelidade com design app-like, desenvolvido em Laravel + PostgreSQL para deploy no Render.com.Uma plataforma completa para fidelidade de clientes e gestão de empresas, com backend em Node.js/Express, frontend estático e banco de dados PostgreSQL.



## 🎨 Design System## Funcionalidades

- **Paleta**: Roxo (#6366f1) + Dourado (#f59e0b) + Branco

- **Interface**: Moderna, responsiva, tipo aplicativo- Cadastro e login de clientes e empresas

- **Animações**: Fluidas e profissionais- Sistema de pontos de fidelidade

- **UX**: Otimizada para conversão- Avaliações e comentários

- Notificações push

## ⚡ Funcionalidades- Painel administrativo para gestão

- Integração com Mercado Pago e PagSeguro

### Sistema de Pontos

- **R$ 1,00 = 1 ponto** (base)## Tecnologias

- **Níveis VIP** com multiplicadores:

  - Bronze: 1x (padrão)- **Backend:** Node.js, Express, Sequelize, PostgreSQL

  - Prata: 1.5x (1000+ pontos)- **Frontend:** HTML, CSS, JavaScript

  - Ouro: 2x (5000+ pontos) - **Deploy:** Render (web service)

  - Diamante: 3x (10000+ pontos)

## Instalação e Execução Local

### Bônus e Vantagens

- **100 pontos** de boas-vindas no cadastro1. Clone o repositório:

- **Descontos progressivos** por nível   ```bash

- **Ofertas exclusivas** para membros VIP   git clone https://github.com/seu-usuario/tem-de-tudo.git

- **Dashboard personalizado** por perfil   cd tem-de-tudo

   ```

## 🚀 Deploy Automatizado

2. Instale as dependências:

### Render.com   ```bash

```bash   npm install

# 1. Push para GitHub   ```

git push origin main

3. Configure as variáveis de ambiente:

# 2. No Render, conecte o repo   Crie um arquivo `.env` na raiz do projeto com:

# 3. render.yaml detectado automaticamente   ```

# 4. Deploy completo em minutos   DB_DIALECT=postgres

```   DB_HOST=localhost

   DB_PORT=5432

### Configurações Prontas   DB_NAME=temdetudo

- ✅ Docker multi-stage otimizado   DB_USER=seu_usuario_postgres

- ✅ PostgreSQL configurado     DB_PASSWORD=sua_senha_postgres

- ✅ Variáveis de ambiente   JWT_SECRET=sua_chave_secreta_jwt

- ✅ Migrations automáticas   MERCADO_PAGO_TOKEN=seu_token_mercado_pago

- ✅ Cache de produção   PAG_SEGURO_TOKEN=seu_token_pag_seguro

- ✅ SSL/HTTPS habilitado   ```



## 👥 Contas Demo4. Execute o projeto:

   ```bash

### Cliente Teste   npm run dev

- **Email**: cliente@temdetudo.com   ```

- **Senha**: cliente123

- **Nível**: Bronze com 250 pontos   O servidor estará rodando em `http://localhost:3000`.



### Empresa Admin  ## Deploy no Render.com

- **Email**: empresa@temdetudo.com

- **Senha**: empresa1231. Faça push do código para um repositório no GitHub.

- **Acesso**: Painel administrativo completo

2. Conecte o repositório ao Render.com:

## 🛠️ Stack Tecnológica   - Acesse [render.com](https://render.com) e faça login.

   - Clique em "New" > "Web Service" e importe o repositório do GitHub.

- **Backend**: Laravel 11 + PHP 8.2

- **Database**: PostgreSQL (produção) / SQLite (dev)3. Configure o serviço:

- **Auth**: Laravel Sanctum (JWT)   - **Runtime:** Node

- **Frontend**: HTML5 + CSS3 + Vanilla JS   - **Build Command:** npm install

- **Deploy**: Docker + Render.com   - **Start Command:** npm start

- **CI/CD**: Automated via render.yaml   - **Environment:** Production



## 📱 URLs de Demonstração4. Configure o banco de dados PostgreSQL no Render.com:

   - Crie um novo "PostgreSQL" database no painel do Render.

- **Home**: https://tem-de-tudo.onrender.com   - Copie a "Internal Database URL" ou "External Database URL".

- **Login**: https://tem-de-tudo.onrender.com/login.html

- **Cadastro**: https://tem-de-tudo.onrender.com/register.html5. Configure as variáveis de ambiente no painel do Render:

- **API**: https://tem-de-tudo.onrender.com/api   - Vá para "Environment" no serviço web.

   - Adicione as seguintes variáveis (use valores reais para produção):

## 🎯 Ideal Para     - `DATABASE_URL`: URL do banco PostgreSQL (ex: postgresql://user:password@host:port/database)

     - `JWT_SECRET`: chave secreta para JWT

- **Pequenos negócios** buscando fidelização     - `MERCADO_PAGO_TOKEN`: token do Mercado Pago

- **Redes de estabelecimentos**      - `PAG_SEGURO_TOKEN`: token do PagSeguro

- **Demonstrações comerciais**

- **MVPs de fidelidade**6. Implante:

- **Sistemas white-label**   - O Render.com implantará automaticamente o projeto.

   - O frontend será servido estáticamente pelo Express, e o backend rodará no servidor.

---



**Status**: ✅ **PRONTO PARA PRODUÇÃO**  

**Última atualização**: Setembro 2025  ## Estrutura do Projeto

**Versão**: 2.0 - Modern App Design
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
