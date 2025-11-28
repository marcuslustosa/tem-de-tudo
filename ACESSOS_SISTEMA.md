# 🔐 Estrutura de Acessos - Sistema Tem de Tudo

## 📋 Resumo dos Perfis

O sistema possui **3 perfis principais**:

### 1️⃣ **Cliente** (`perfil: 'cliente'`)
- **Objetivo**: Acumular pontos e resgatar descontos
- **Dashboard**: `/dashboard-cliente.html`
- **Páginas disponíveis**:
  - `/cliente/perfil.html` - Ver perfil pessoal
  - `/cliente/pontos.html` - Ver pontos acumulados e nível
  - `/cliente/cupons.html` - Ver cupons disponíveis
  - `/cliente/historico.html` - Ver histórico de transações

### 2️⃣ **Empresa** (`perfil: 'empresa'`)
- **Objetivo**: Gerenciar programa de fidelidade e descontos
- **Dashboard**: `/dashboard-estabelecimento.html`
- **Páginas disponíveis**:
  - `/profile-company.html` - Painel da empresa
  - `/configurar-descontos.html` - Configurar níveis e percentuais
  - `/aplicar-desconto.html` - Aplicar desconto em compra
  - `/meus-descontos.html` - Ver histórico de descontos aplicados

### 3️⃣ **Admin Master** (`perfil: 'admin'`)
- **Objetivo**: Gerenciar todo o sistema
- **Login separado**: `/admin-login.html`
- **Dashboard**: `/admin.html`
- **Páginas disponíveis**:
  - `/admin.html` - Dashboard administrativo
  - `/admin-create-user.html` - Criar usuários
  - `/admin-relatorios.html` - Relatórios do sistema
  - `/admin-configuracoes.html` - Configurações gerais

---

## 🔄 Fluxo de Autenticação

### Login Cliente/Empresa
1. Usuário acessa `/login.html`
2. Informa email e senha
3. Sistema faz POST para `/api/auth/login`
4. Backend verifica credenciais e retorna:
   ```json
   {
     "success": true,
     "data": {
       "user": { "perfil": "cliente" | "empresa" },
       "token": "Bearer token...",
       "redirect_to": "/dashboard-cliente.html" | "/dashboard-estabelecimento.html"
     }
   }
   ```
5. Frontend salva token no localStorage:
   - `tem_de_tudo_token`
   - `tem_de_tudo_user`
6. Redireciona para dashboard correto baseado no perfil

### Login Admin
1. Admin acessa `/admin-login.html` (página separada)
2. Informa email e senha
3. Sistema faz POST para `/api/admin/login` (endpoint separado)
4. Backend verifica se `perfil === 'admin'`
5. Retorna token e redireciona para `/admin.html`

---

## ✅ Estado Atual - O que está BEM DEFINIDO

### ✔️ Separação de Rotas
- `/api/auth/login` - Login para cliente e empresa
- `/api/admin/login` - Login exclusivo para admin

### ✔️ Redirecionamento Automático
O arquivo `login.html` já contém a lógica correta:
```javascript
const user = data.data.user;
let redirectUrl = '/dashboard-cliente.html'; // default

if (user.perfil === 'empresa') {
    redirectUrl = '/dashboard-estabelecimento.html';
} else if (user.perfil === 'admin') {
    redirectUrl = '/admin.html';
}

window.location.href = redirectUrl;
```

### ✔️ Proteção de Rotas
Todas as páginas possuem função `checkAuth()`:
```javascript
function checkAuth() {
    const token = localStorage.getItem('tem_de_tudo_token');
    if (!token) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}
```

### ✔️ Model User
O campo `perfil` está corretamente definido:
```php
protected $fillable = ['name', 'email', 'password', 'perfil', ...];

public function isAdmin(): bool {
    return $this->perfil === 'admin';
}
```

---

## ⚠️ Pontos de Atenção - O que PRECISA SER MELHORADO

### 🔴 1. Login Admin NÃO está aparecendo pelo login normal
**Problema**: Se um admin tentar fazer login pelo `/login.html`, ele será redirecionado para `/admin.html`, mas isso pode causar confusão.

**Solução Recomendada**:
- Admin deve SEMPRE usar `/admin-login.html`
- No login normal, bloquear acesso de perfil admin:
```javascript
if (user.perfil === 'admin') {
    showMessage('Use o painel administrativo para fazer login', 'error');
    setTimeout(() => {
        window.location.href = '/admin-login.html';
    }, 2000);
    return;
}
```

### 🟡 2. Verificação de Perfil nas Páginas
Atualmente as páginas só verificam se TEM token, mas não verificam se o PERFIL está correto.

**Exemplo**: Um cliente com token válido poderia acessar `/profile-company.html`

**Solução**: Adicionar verificação de perfil:
```javascript
function checkAuthAndProfile(requiredProfile) {
    const token = localStorage.getItem('tem_de_tudo_token');
    const userStr = localStorage.getItem('tem_de_tudo_user');
    
    if (!token) {
        window.location.href = '/login.html';
        return false;
    }
    
    if (userStr) {
        const user = JSON.parse(userStr);
        if (user.perfil !== requiredProfile) {
            // Redirecionar para dashboard correto
            if (user.perfil === 'cliente') {
                window.location.href = '/dashboard-cliente.html';
            } else if (user.perfil === 'empresa') {
                window.location.href = '/dashboard-estabelecimento.html';
            } else if (user.perfil === 'admin') {
                window.location.href = '/admin.html';
            }
            return false;
        }
    }
    
    return true;
}
```

### 🟡 3. Criar Usuário Admin Padrão
É necessário ter um admin master inicial no banco.

**Script SQL**:
```sql
-- Inserir admin master (senha: admin123)
INSERT INTO users (name, email, password, perfil, status, created_at, updated_at)
VALUES (
    'Admin Master',
    'admin@temdetudo.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'ativo',
    NOW(),
    NOW()
) ON CONFLICT (email) DO NOTHING;
```

---

## 📝 Usuários de Teste

### Cliente
- **Email**: `cliente@teste.com`
- **Senha**: `senha123`
- **Perfil**: `cliente`
- **Acesso**: `/login.html` → `/dashboard-cliente.html`

### Empresa
- **Email**: `empresa@teste.com`
- **Senha**: `senha123`
- **Perfil**: `empresa`
- **Acesso**: `/login.html` → `/dashboard-estabelecimento.html`

### Admin Master
- **Email**: `admin@temdetudo.com`
- **Senha**: `admin123`
- **Perfil**: `admin`
- **Acesso**: `/admin-login.html` → `/admin.html`

---

## 🔧 Melhorias Recomendadas

### 1. Bloquear Admin no Login Normal
```javascript
// Em login.html, adicionar após receber resposta:
if (user.perfil === 'admin') {
    showMessage('Administradores devem usar o painel administrativo', 'warning');
    setTimeout(() => {
        window.location.href = '/admin-login.html';
    }, 2000);
    return;
}
```

### 2. Adicionar Verificação de Perfil em Cada Página

**Páginas de Cliente** devem ter:
```javascript
checkAuthAndProfile('cliente');
```

**Páginas de Empresa** devem ter:
```javascript
checkAuthAndProfile('empresa');
```

**Páginas de Admin** devem ter:
```javascript
checkAuthAndProfile('admin');
```

### 3. Menu de Navegação Baseado no Perfil
O menu deve mostrar apenas as opções relevantes ao perfil do usuário.

### 4. Criar Middleware no Backend
```php
// Middleware para verificar perfil
if ($request->user()->perfil !== 'empresa') {
    return response()->json(['error' => 'Acesso negado'], 403);
}
```

---

## 🎯 Conclusão

### ✅ O que está BOM:
- Estrutura de perfis definida no banco
- Rotas separadas para admin e usuários comuns
- Redirecionamento automático baseado em perfil
- Token de autenticação funcionando

### ⚠️ O que PRECISA MELHORAR:
1. **Bloquear admin no login normal** - Forçar uso do `/admin-login.html`
2. **Verificar perfil nas páginas** - Impedir acesso cruzado
3. **Criar usuário admin inicial** - Ter um admin master no banco
4. **Adicionar middleware de perfil** - Proteger rotas da API por perfil

---

## 📞 Próximos Passos

1. Implementar bloqueio de admin no login normal
2. Adicionar `checkAuthAndProfile()` em todas as páginas
3. Criar script SQL para inserir admin inicial
4. Testar fluxo completo de autenticação
5. Documentar no README os acessos de teste
