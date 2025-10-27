# 🎯 CONFIGURAÇÃO RENDER - CAMPOS OBRIGATÓRIOS

## ✅ Configurações Principais

### **Nome**
```
tem-de-tudo
```

### **Language**
```
Docker
```
⚠️ **IMPORTANTE**: Use Docker (PHP não está disponível)

### **Branch**
```
main
```

### **Region**
```
Oregon (US West)
```

### **Root Directory**
```
backend
```

### **Dockerfile Path**
```
Dockerfile
```

### **Instance Type**
```
Free ($0/month)
```

## 🔧 Environment Variables

**Clique "Add Environment Variable" para cada uma:**

```
APP_NAME=Tem de Tudo
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:3cQV4S7tE8m2dR9wQ5lN6pK1jH0uI8yT7rE3wQ9pL5k=
APP_URL=https://tem-de-tudo.onrender.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

DB_CONNECTION=pgsql
DB_SSL_MODE=require
DB_HOST=dpg-d3vps0k9c44c738q64gg-a
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user
DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error
LOG_CHANNEL=stack

JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

SANCTUM_STATEFUL_DOMAINS=tem-de-tudo.onrender.com

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@temdetudo.com
MAIL_FROM_NAME=Tem de Tudo

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12
FILESYSTEM_DISK=local
```

## 📝 Advanced Settings

### **Health Check Path:**
```
/
```

### **Docker Build Context Directory:**
```
backend
```

### **Dockerfile Path:**
```
Dockerfile
```

## ⚠️ Campos para CONFIGURAR

- **Dockerfile Path**: `Dockerfile`
- **Docker Build Context**: `backend`
- **Health Check Path**: `/`

## ⚠️ Campos para IGNORAR

- **Pre-Deploy Command**: Deixe em branco
- **Secret Files**: Não adicione nada
- **Disk**: Não adicione nada
- **Registry Credential**: Deixe "No credential"
- **Docker Command**: Deixe em branco (o Dockerfile já define)

## 🚀 Depois de Configurar

1. **Clique "Create Web Service"**
2. **Aguarde o deploy** (5-10 minutos)
3. **Teste**: https://tem-de-tudo.onrender.com

---

**RESUMO**: Mude para PHP, adicione as variáveis de ambiente, e configure os comandos de build/start! 🎯