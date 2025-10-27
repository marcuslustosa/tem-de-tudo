# 🚀 RENDER.COM - CONFIGURAÇÃO NOVA CONTA

## ⚙️ **CONFIGURAÇÕES EXATAS PARA COPY/PASTE**

### **🔧 Web Service Settings:**
```
Service Name: tem-de-tudo
Environment: Node  
Region: Oregon
Plan: Free
Branch: main
Root Directory: backend
Build Command: composer install --no-dev --optimize-autoloader && php artisan config:clear
Start Command: php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
Auto-Deploy: Yes
```

### **🗃️ PostgreSQL Database:**
```
Database Name: tem-de-tudo-db
Database: tem_de_tudo_db  
User: tem_de_tudo_user
Region: Oregon
Plan: Free
```

### **🔐 Environment Variables:**

**COPIE E COLE NO PAINEL RENDER:**

```bash
# Application
APP_NAME=Tem de Tudo
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:3cQV4S7tE8m2dR9wQ5lN6pK1jH0uI8yT7rE3wQ9pL5k=
APP_URL=https://tem-de-tudo.onrender.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

# Database (Conectar via "Add from Database")
DB_CONNECTION=pgsql
DB_SSL_MODE=require

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# JWT Authentication
JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# CORS & Security
SANCTUM_STATEFUL_DOMAINS=tem-de-tudo.onrender.com

# Mail
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@temdetudo.com
MAIL_FROM_NAME=Tem de Tudo

# Logging
LOG_LEVEL=error
LOG_CHANNEL=stack

# PHP
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

# File System
FILESYSTEM_DISK=local
```

### **🎯 URLs Finais:**
- **App:** https://tem-de-tudo.onrender.com
- **Admin:** https://tem-de-tudo.onrender.com/admin.html  
- **Login:** https://tem-de-tudo.onrender.com/login.html

### **👤 Credenciais:**
- **Admin:** admin@sistema.com / admin123
- **Cliente:** cliente@teste.com / 123456
- **Empresa:** empresa@teste.com / 123456

---

## ⏱️ **TEMPO ESTIMADO: 15 MINUTOS**

1. Criar conta (2 min)
2. Conectar GitHub (1 min)  
3. Configurar service (5 min)
4. Criar PostgreSQL (2 min)
5. Deploy + Build (5 min)

**✅ SISTEMA 100% FUNCIONAL!**