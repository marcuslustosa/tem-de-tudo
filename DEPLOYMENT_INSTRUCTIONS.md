# 🚀 Instruções de Deploy - Projeto Tem de Tudo

## 📋 Pré-requisitos
- Conta no GitHub
- Conta no Render
- Conta no Vercel
- Projeto Laravel funcionando localmente

## 1. 📦 Preparação do Repositório

### 1.1 Criar repositório no GitHub
```bash
# Criar novo repositório no GitHub
# Nome sugerido: temdetudo-app
```

### 1.2 Configurar .gitignore
Certifique-se de que o `.gitignore` contém:
```
# Laravel
/vendor/
/node_modules/
/storage/app/
/storage/framework/
/storage/logs/
/bootstrap/cache/
.env
.env.local
.env.production

# Database
*.sqlite
*.db

# IDE
.vscode/
.idea/

# OS
.DS_Store
Thumbs.db
```

### 1.3 Fazer commit e push
```bash
git init
git add .
git commit -m "Initial commit - Tem de Tudo app"
git branch -M main
git remote add origin https://github.com/SEU_USERNAME/temdetudo-app.git
git push -u origin main
```

## 2. 🔧 Deploy do Backend (Laravel) no Render

### 2.1 Criar Web Service
1. Acesse [render.com](https://render.com)
2. Clique em "New" → "Web Service"
3. Conecte seu repositório GitHub
4. Configure as seguintes opções:

**Build Settings:**
- **Build Command:** `composer install --optimize-autoloader --no-dev`
- **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`

**Environment Variables:**
```
APP_NAME=TemDeTudo
APP_ENV=production
APP_KEY=base64:GERAR_NOVA_CHAVE_COM_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://SEU_BACKEND_URL.onrender.com
DB_CONNECTION=sqlite
DB_DATABASE=/opt/render/project/database/database.sqlite
SANCTUM_STATEFUL_DOMAINS=SEU_FRONTEND_URL.vercel.app
```

### 2.2 Executar Migrations
Após o deploy, execute as migrations via SSH no Render:
```bash
php artisan migrate
php artisan db:seed  # se houver seeders
```

## 3. 🎨 Deploy do Frontend no Vercel

### 3.1 Criar projeto no Vercel
1. Acesse [vercel.com](https://vercel.com)
2. Clique em "New Project"
3. Conecte seu repositório GitHub
4. Configure:

**Build Settings:**
- **Framework Preset:** Other
- **Root Directory:** frontend
- **Build Command:** (deixe vazio)
- **Output Directory:** . (raiz do frontend)

**Environment Variables:**
```
NEXT_PUBLIC_API_URL=https://SEU_BACKEND_URL.onrender.com/api
```

### 3.2 Deploy
O Vercel fará o deploy automático após conectar o repositório.

## 4. 🔗 Conectar Frontend ao Backend

### 4.1 Atualizar URLs
Após ter as URLs do Render e Vercel:

1. **No Render (Backend):**
   - Atualize `SANCTUM_STATEFUL_DOMAINS` com a URL do Vercel
   - Atualize `APP_URL` com a URL do Render

2. **No Vercel (Frontend):**
   - Atualize `NEXT_PUBLIC_API_URL` com a URL do Render + `/api`

### 4.2 Testar Conexão
```bash
# Testar registro
curl -X POST https://SEU_BACKEND_URL.onrender.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}'

# Testar login
curl -X POST https://SEU_BACKEND_URL.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

## 5. 🧪 Testes Pós-Deploy

### 5.1 Funcionalidades Essenciais
- [ ] Registro de usuários
- [ ] Login e logout
- [ ] Acesso a páginas protegidas
- [ ] Responsividade mobile
- [ ] Programa de fidelidade

### 5.2 URLs de Teste
- **Frontend:** https://SEU_FRONTEND_URL.vercel.app
- **Backend API:** https://SEU_BACKEND_URL.onrender.com/api

## 6. 🔧 Otimizações Recomendadas

### 6.1 Performance
```bash
# Otimizar Laravel para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6.2 Segurança
- Configurar HTTPS (automático no Render/Vercel)
- Manter dependências atualizadas
- Usar variáveis de ambiente para chaves sensíveis

### 6.3 Monitoramento
- Configurar logs no Render
- Monitorar erros no Vercel
- Configurar alertas de uptime

## 7. 🐛 Troubleshooting

### Problemas Comuns
1. **Erro de CORS:** Verificar `SANCTUM_STATEFUL_DOMAINS`
2. **Erro de banco:** Executar migrations no Render
3. **Erro 500:** Verificar logs no Render
4. **Frontend não carrega:** Verificar `NEXT_PUBLIC_API_URL`

### Logs no Render
```bash
# Ver logs do Render
# Acesse Dashboard → Service → Logs
```

## 8. 📝 Próximos Passos
- Implementar upload de fotos reais
- Adicionar mais funcionalidades do programa de fidelidade
- Configurar notificações push
- Implementar sistema de avaliações
- Adicionar analytics

---

## 📞 Suporte
Em caso de problemas, verifique:
1. Logs do Render/Vercel
2. Configurações de ambiente
3. Conectividade entre frontend/backend
4. Documentação oficial das plataformas

**URLs de Documentação:**
- [Render Docs](https://docs.render.com/)
- [Vercel Docs](https://vercel.com/docs)
- [Laravel Deploy](https://laravel.com/docs/deployment)
