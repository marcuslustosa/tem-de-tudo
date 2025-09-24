# 🔐 Sistema de Administração - Funcionalidades de Produção

Este documento descreve as funcionalidades de produção implementadas no sistema TemDeTudo.

## ✨ Funcionalidades Implementadas

### 🔑 Autenticação JWT
- **Token JWT** para autenticação segura de administradores
- **Refresh tokens** com expiração configurável
- **Blacklist de tokens** para logout seguro
- **Middleware de autenticação** com verificação automática

### 🛡️ Rate Limiting & Segurança
- **Rate limiting** no login (5 tentativas por minuto)
- **Bloqueio temporário** de contas após tentativas falhadas
- **Cabeçalhos de segurança** (HSTS, CSP, X-Frame-Options, etc.)
- **Forçar HTTPS** em produção
- **Middleware de segurança** global

### 📊 Audit Logging
- **Log completo** de todas as ações administrativas
- **Rastreamento de IPs** e user agents
- **Eventos de segurança** especializados
- **Relatórios de auditoria** com filtros
- **Cleanup automático** de logs antigos

### 👑 Sistema de Permissões
- **Roles hierárquicos**: super_admin, admin, moderator
- **Permissões granulares** por funcionalidade
- **Middleware de permissões** automático
- **Verificação dinâmica** de acesso

### 📈 Relatórios Administrativos
- **Estatísticas do sistema** em tempo real
- **Relatório de logins** e segurança
- **Eventos de segurança** recentes
- **Relatório de usuários** com filtros
- **Dashboard administrativo** completo

## 🚀 Estrutura da API

### Endpoints de Autenticação
```
POST /api/admin/login          # Login de administrador
POST /api/admin/logout         # Logout seguro
GET  /api/admin/me            # Perfil do admin
POST /api/admin/refresh       # Renovar token
```

### Endpoints Protegidos (requer JWT)
```
# Gestão de Usuários (permissão: create_users)
POST /api/admin/create-user

# Gerenciamento (permissão: manage_users)
GET  /api/admin/users
PUT  /api/admin/users/{id}/status

# Relatórios (permissão: view_reports)
GET  /api/admin/system-stats
GET  /api/admin/audit-logs
GET  /api/admin/security-events
GET  /api/admin/login-stats
GET  /api/admin/users-report

# Sistema (permissão: manage_system)
POST /api/admin/cleanup-logs
```

## 🛠️ Configuração de Produção

### 1. Variáveis de Ambiente (.env)
```env
# JWT Configuration
JWT_SECRET=sua_chave_super_secreta_aqui
JWT_TTL=60                    # 1 hora
JWT_REFRESH_TTL=20160        # 2 semanas
JWT_BLACKLIST_ENABLED=true

# Segurança
FORCE_HTTPS=true
LOGIN_RATE_LIMIT_ATTEMPTS=5
LOGIN_RATE_LIMIT_MINUTES=1
LOGIN_LOCKOUT_MINUTES=15

# Auditoria
AUDIT_ENABLED=true
AUDIT_RETENTION_DAYS=90
```

### 2. Base de Dados
Execute as migrations:
```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

### 3. Configuração de Produção
- **HTTPS obrigatório** com redirecionamento automático
- **Rate limiting** configurado por IP
- **Cabeçalhos de segurança** aplicados automaticamente
- **Logs de auditoria** com rotação automática

## 🔐 Segurança Implementada

### Proteções Ativas
- ✅ **Força HTTPS** em produção
- ✅ **Rate limiting** por IP
- ✅ **Bloqueio de conta** após tentativas falhadas
- ✅ **Tokens JWT** com expiração
- ✅ **Blacklist de tokens** revogados
- ✅ **Cabeçalhos de segurança** completos
- ✅ **Content Security Policy**
- ✅ **Audit logging** completo
- ✅ **Permissões granulares**

### Cabeçalhos de Segurança
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000
Content-Security-Policy: default-src 'self'...
```

## 👥 Sistema de Usuários Admin

### Roles Disponíveis
1. **super_admin** - Acesso total ao sistema
2. **admin** - Gestão de usuários e relatórios
3. **moderator** - Acesso limitado a relatórios

### Permissões Granulares
- `create_users` - Criar novos usuários
- `manage_users` - Gerenciar usuários existentes
- `delete_users` - Remover usuários
- `view_reports` - Visualizar relatórios
- `manage_system` - Configurações do sistema
- `audit_logs` - Acessar logs de auditoria

## 📊 Logs de Auditoria

### Eventos Rastreados
- ✅ Login bem-sucedido
- ✅ Tentativas de login falhadas
- ✅ Rate limiting ativado
- ✅ Acesso não autorizado
- ✅ Criação de usuários
- ✅ Alterações de permissões
- ✅ Logout de sessões

### Dados Capturados
- **User ID** do admin
- **Ação** executada
- **IP Address** de origem
- **User Agent** do navegador
- **Detalhes** específicos da ação
- **Timestamp** preciso

## 🧪 Testes

Execute o script de teste da API:
```bash
php test_admin_api.php http://localhost:8000/api
```

O script testa:
- ✅ Login JWT
- ✅ Autenticação com token
- ✅ Sistema de permissões
- ✅ Relatórios administrativos
- ✅ Rate limiting
- ✅ Logout seguro

## 📱 Exemplo de Uso

### 1. Login de Admin
```javascript
const response = await fetch('/api/admin/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@temdetudo.com',
    password: 'admin123'
  })
});

const { token } = await response.json();
```

### 2. Usar Token JWT
```javascript
const stats = await fetch('/api/admin/system-stats', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### 3. Verificar Permissões
```javascript
// Criar usuário (requer permissão create_users)
const user = await fetch('/api/admin/create-user', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Novo Usuário',
    email: 'novo@email.com',
    password: 'senha123'
  })
});
```

## 🚨 Usuários Padrão

### Super Admin
- **Email**: `admin@temdetudo.com`
- **Senha**: `admin123`
- **Permissões**: Todas

### Admin Moderador
- **Email**: `moderador@temdetudo.com`
- **Senha**: `mod123`
- **Permissões**: Limitadas

---

## 🎯 Próximos Passos

1. **Deploy em HTTPS** obrigatório
2. **Configurar firewall** e proxy reverso
3. **Monitoramento** de logs em tempo real
4. **Backup** automático do banco de dados
5. **Alertas** de segurança por email/SMS

---

> ⚠️ **IMPORTANTE**: Este sistema é adequado para **produção** com todas as medidas de segurança implementadas. Certifique-se de usar HTTPS e configurar adequadamente as variáveis de ambiente antes do deploy.