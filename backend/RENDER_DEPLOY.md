# Deploy no Render - Laravel + Frontend

Este documento explica como fazer o deploy do projeto Laravel com frontend integrado no Render.

## 📁 Estrutura do Projeto

```
backend/
├── app/                    # Código Laravel
├── public/                 # Frontend (HTML/CSS/JS) + assets Laravel
├── routes/
│   ├── api.php            # Rotas da API (/api/*)
│   └── web.php            # Fallback para frontend (/*)
├── Procfile               # Configuração do Render
├── Dockerfile             # Configuração do container
└── entrypoint.sh          # Script de inicialização
```

## 🚀 Configuração no Render

### 1. Criar Novo Web Service

1. Acesse [render.com](https://render.com) e faça login
2. Clique em "New +" → "Web Service"
3. Conecte seu repositório Git (GitHub/GitLab)
4. Configure as seguintes opções:

### 2. Configurações do Web Service

**Main Settings:**
- **Name:** seu-nome-app
- **Environment:** Docker
- **Root Directory:** `backend`

**Build Settings:**
- **Build Command:** `composer install --no-dev --optimize-autoloader`
- **Start Command:** `heroku-php-apache2 public/`

**Environment Variables:**
```bash
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://seu-nome-app.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

### 3. Variáveis de Ambiente Obrigatórias

**Laravel:**
- `APP_KEY`: Gere com `php artisan key:generate --show`
- `APP_ENV=production`
- `APP_DEBUG=false`

**Banco de dados:**
- Configure conforme sua base de dados MySQL/PostgreSQL

## 🔧 Funcionamento

### URLs e Rotas

- **Frontend:** `https://seuapp.onrender.com/` → serve `index.html`
- **Login:** `https://seuapp.onrender.com/login` → serve `login.html`
- **API:** `https://seuapp.onrender.com/api/login` → rota Laravel
- **API:** `https://seuapp.onrender.com/api/register` → rota Laravel

### Como Funciona

1. **Requisições para `/api/*`**: São processadas pelo Laravel
2. **Requisições para `/*`**: Servem arquivos estáticos do `public/`
3. **Fallback**: Qualquer rota não-API serve `index.html` (SPA)

## 📝 Comandos Úteis

### Deploy Manual
```bash
# No diretório backend/
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Logs e Debug
```bash
# Ver logs no Render Dashboard
# Acesse: https://dashboard.render.com/web/svc-nome/logs

# Para debug local
php artisan serve --host=0.0.0.0 --port=10000
```

## 🔒 Segurança

1. **APP_DEBUG=false** em produção
2. **APP_KEY** deve ser única e secreta
3. Configure HTTPS no Render (automático)
4. Use variáveis de ambiente para dados sensíveis

## 🐛 Troubleshooting

### Problema: Frontend não carrega
- Verifique se arquivos estão em `backend/public/`
- Confirme se `routes/web.php` está correto
- Verifique logs no Render Dashboard

### Problema: API não funciona
- Verifique se `routes/api.php` está correto
- Confirme se middleware está configurado
- Verifique variáveis de ambiente

### Problema: Banco não conecta
- Verifique string de conexão no Render
- Confirme se banco está acessível
- Verifique logs de erro

## 📞 Suporte

Para problemas específicos do Render, consulte:
- [Documentação Render](https://render.com/docs)
- [Suporte Render](https://render.com/support)
