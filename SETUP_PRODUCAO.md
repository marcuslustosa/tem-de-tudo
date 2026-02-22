# 🚀 Tem de Tudo - Sistema de Fidelidade para Produção

## 📋 Configuração Completa para Produção

### ⚙️ Pré-requisitos
- ✅ PHP 8.0+ com extensões: pdo, mysql, mbstring, openssl
- ✅ MySQL 5.7+ ou PostgreSQL 12+
- ✅ Composer instalado
- ✅ Servidor web (Apache/Nginx)

---

## 🛠️ Instalação e Configuração

### 1️⃣ **Clone e Instalação**
```bash
# Clone do repositório
git clone https://github.com/marcuslustosa/tem-de-tudo.git
cd tem-de-tudo/backend

# Instalar dependências
composer install

# Configurar permissões
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 2️⃣ **Configuração do Banco de Dados**
```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Editar configurações do banco
nano .env
```

**Configurações essenciais no .env:**
```env
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tem_de_tudo
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

JWT_SECRET=sua_chave_jwt_aqui
```

### 3️⃣ **Executar Migrações**
```bash
# Criar tabelas do banco
php artisan migrate

# Gerar chave da aplicação
php artisan key:generate

# Gerar chave JWT
php artisan jwt:secret
```

---

## 🎭 **Dados de Demonstração**

### 📊 **Populando com Dados Fictícios**

**Opção A - Via PHP (Recomendado):**
```bash
php seed_demonstracao.php
```

**Opção B - Via SQL Direto:**
```bash
mysql -u usuario -p tem_de_tudo < database/seed_producao_demonstracao.sql
```

### 🔑 **Credenciais de Demonstração**

**👨‍💼 ADMINISTRADOR:**
- Email: `admin@temdetudo.com`
- Senha: `123456`
- Acesso: Painel administrativo completo

**👥 CLIENTES DE EXEMPLO:**
- `maria@email.com` - 180 pontos acumulados
- `joao@email.com` - 110 pontos, histórico de compras
- `ana@email.com` - 65 pontos, notificações ativas
- `roberto@email.com` - Cliente novo
- `patricia@email.com` - Cliente ativo

**🏢 EMPRESAS DE EXEMPLO:**
- `contato@sabordacasa.com` - Restaurante com promoções
- `contato@farmaciajoao.com` - Farmácia com descontos
- `contato@shellcentro.com` - Posto com programa de fidelidade
- `contato@superfamilia.com` - Supermercado com ofertas
- `contato@fashionloja.com` - Loja de roupas

*Todas as senhas: **123456***

---

## 🌐 **Configuração do Servidor Web**

### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

# Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### **Nginx**
```nginx
server {
    listen 80;
    server_name seudominio.com;
    root /var/www/tem-de-tudo/backend/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 📱 **Funcionalidades Implementadas**

### ✅ **Sistema Completo**
- 🔐 **Autenticação JWT** - Login seguro para 3 perfis
- 👥 **Gestão de Usuários** - CRUD completo no admin
- 🏢 **Gestão de Empresas** - Cadastro, aprovação, dashboard
- ⭐ **Sistema de Pontos** - Acúmulo, uso, histórico
- 🎁 **Promoções** - Criação, gestão, aplicação
- 📱 **PWA Completo** - App instalável offline-first
- 🔔 **Notificações** - Sistema completo de alertas
- 📊 **Relatórios** - Dashboards para todos os perfis

### 🎨 **Design System Vivo**
- ✅ Cores: Roxo (#6F1AB6) + Branco
- ✅ Tipografia: Inter (Google Fonts)
- ✅ Responsivo: Mobile-first
- ✅ Componentes: Unificados em global-styles.css

---

## 🔧 **APIs Implementadas**

### 🔐 **Autenticação**
```
POST /api/auth/login       - Login de usuários
POST /api/auth/register    - Cadastro de novos usuários  
POST /api/auth/logout      - Logout
GET  /api/auth/me          - Dados do usuário logado
```

### 👥 **Usuários**
```
GET    /api/users          - Listar usuários (admin)
POST   /api/users          - Criar usuário
GET    /api/users/{id}     - Detalhes do usuário
PUT    /api/users/{id}     - Atualizar usuário
DELETE /api/users/{id}     - Excluir usuário
```

### 🏢 **Empresas**
```
GET  /api/empresas         - Listar empresas
POST /api/empresas         - Cadastrar empresa
PUT  /api/empresas/{id}    - Atualizar empresa
GET  /api/empresas/nearby  - Empresas próximas
```

### ⭐ **Pontos**
```
GET  /api/pontos           - Extrato de pontos
POST /api/pontos/ganhar    - Adicionar pontos
POST /api/pontos/usar      - Usar pontos
GET  /api/pontos/saldo     - Saldo atual
```

---

## 🚀 **Deploy para Produção**

### 1️⃣ **Otimizações**
```bash
# Otimizar autoload
composer install --optimize-autoloader --no-dev

# Cache das configurações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Otimizar para produção
php artisan optimize
```

### 2️⃣ **Segurança**
```bash
# Configurar SSL/HTTPS
# Configurar firewall
# Backup automático do banco
# Monitoramento de logs
```

### 3️⃣ **Variáveis de Produção**
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# Configurar email real
MAIL_MAILER=smtp
MAIL_HOST=seu_servidor_smtp
MAIL_PORT=587
MAIL_USERNAME=seu_email
MAIL_PASSWORD=sua_senha
```

---

## 🎯 **Como Demonstrar o Sistema**

### 1️⃣ **Para o Cliente / Investidor:**
1. **Acesse:** `https://seudominio.com/entrar.html`
2. **Use Admin:** `admin@temdetudo.com` / `123456`
3. **Mostre:** Dashboard completo, gestão de usuários, relatórios
4. **Demonstre:** Aprovação de empresas, configurações

### 2️⃣ **Para Empresas Interessadas:**
1. **Use Empresa:** `contato@sabordacasa.com` / `123456`
2. **Mostre:** Dashboard empresa, clientes, promoções
3. **Demonstre:** Scanner QR, relatórios de pontos

### 3️⃣ **Para Usuários Finais:**
1. **Use Cliente:** `maria@email.com` / `123456`
2. **Mostre:** App completo, pontos, promoções
3. **Demonstre:** QR Code, histórico, notificações

---

## 📊 **Dados de Demonstração Incluídos**

- 👥 **5 Clientes** com diferentes perfis e pontos
- 🏢 **5 Empresas** de setores variados com promoções ativas
- ⭐ **180+ pontos** distribuídos entre clientes  
- 🎁 **5 Promoções** ativas para demonstração
- 🔔 **Notificações** variadas para cada perfil
- 📈 **Histórico** de transações completo

---

## 🆘 **Suporte e Manutenção**

### 📋 **Checklist de Funcionamento**
```bash
# Testar login dos 3 perfis
# Verificar APIs no /api/test
# Validar dados no admin
# Testar promoções
# Conferir notificações
```

### 🔧 **Comandos Úteis**
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Limpar cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Backup do banco
mysqldump -u usuario -p tem_de_tudo > backup.sql
```

---

## 🎯 **Sistema 100% Pronto para Produção!**

✅ **Backend Laravel** completo e funcional  
✅ **Frontend** com Design System Vivo  
✅ **APIs RESTful** documentadas  
✅ **Dados de demonstração** realistas  
✅ **3 perfis** com funcionalidades distintas  
✅ **PWA** instalável e offline-first  
✅ **Segurança** implementada (JWT, HTTPS)  

**🚀 Pronto para apresentar ao cliente e colocar no ar!**