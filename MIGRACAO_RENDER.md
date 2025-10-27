# 🔄 GUIA DE MIGRAÇÃO - RENDER NOVA CONTA

## 🎯 **MIGRAÇÃO DO TEM DE TUDO**

### **📋 PASSO 1: PREPARAR NOVA CONTA**

1. **Criar conta no Render**
   - Acesse: https://render.com
   - Cadastre-se com email do cliente
   - Conecte GitHub do cliente

### **📋 PASSO 2: CONFIGURAR REPOSITÓRIO**

1. **Fork/Transfer do repositório:**
   - Opção A: Transfer ownership para conta do cliente
   - Opção B: Cliente faz fork do repositório atual

2. **Conectar no Render:**
   - Dashboard → New → Web Service
   - Connect Repository → Selecionar: tem-de-tudo
   - Branch: main

### **📋 PASSO 3: CONFIGURAÇÕES DO DEPLOY**

```yaml
# Configurações básicas:
Name: tem-de-tudo
Region: Oregon (US West)
Branch: main
Root Directory: backend
Runtime: PHP
Build Command: composer install --no-dev --optimize-autoloader
Start Command: php artisan serve --host=0.0.0.0 --port=$PORT
```

### **📋 PASSO 4: ENVIRONMENT VARIABLES**

**Copie exatamente essas variáveis no painel:**

```bash
APP_NAME=Tem de Tudo
APP_ENV=production  
APP_DEBUG=false
APP_URL=https://[seu-app-name].onrender.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

# Database (será preenchido automaticamente quando criar PostgreSQL)
DB_CONNECTION=pgsql
DB_SSL_MODE=require

# Sessions e Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# JWT Auth
JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# CORS
SANCTUM_STATEFUL_DOMAINS=[seu-app-name].onrender.com

# Mail
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@temdetudo.com
MAIL_FROM_NAME=Tem de Tudo

# Logs
LOG_LEVEL=error
LOG_CHANNEL=stack
```

### **📋 PASSO 5: CRIAR BANCO POSTGRESQL**

1. **No Dashboard Render:**
   - New → PostgreSQL
   - Name: tem-de-tudo-db
   - Database Name: tem_de_tudo_db
   - User: tem_de_tudo_user
   - Plan: Free

2. **Conectar ao Web Service:**
   - Environment → Add from Database
   - Selecionar o PostgreSQL criado
   - Mapear variáveis:
     - Host → DB_HOST
     - Port → DB_PORT  
     - Database → DB_DATABASE
     - Username → DB_USERNAME
     - Password → DB_PASSWORD

### **📋 PASSO 6: BUILD & DEPLOY**

1. **Deploy automático será iniciado**
2. **Aguardar ~10-15 minutos**
3. **Verificar logs de build**
4. **Testar URLs principais**

### **📋 PASSO 7: VERIFICAÇÃO FINAL**

**URLs para testar:**
- https://[seu-app].onrender.com
- https://[seu-app].onrender.com/login.html
- https://[seu-app].onrender.com/admin.html

**Credenciais de teste:**
- Admin: admin@sistema.com / admin123
- Cliente: cliente@teste.com / 123456
- Empresa: empresa@teste.com / 123456

## ✅ **CHECKLIST DE VERIFICAÇÃO**

- [ ] Nova conta Render criada
- [ ] Repositório conectado
- [ ] Environment variables configuradas
- [ ] PostgreSQL criado e conectado
- [ ] Build bem-sucedido
- [ ] Migrations executadas
- [ ] Seeders executados
- [ ] Login funcionando
- [ ] APIs respondendo
- [ ] Frontend carregando

## 🎯 **RESULTADO ESPERADO**

**Sistema 100% funcional na nova conta em ~15 minutos!**

## 📞 **SUPORTE**

Se houver algum problema durante a migração:
1. Verificar logs de build no Render
2. Confirmar environment variables
3. Testar conexão do banco
4. Verificar se branch main está atualizada

---

**✨ MIGRAÇÃO SIMPLES E RÁPIDA! ✨**