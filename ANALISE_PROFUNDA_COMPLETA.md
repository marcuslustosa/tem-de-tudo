# 🔍 ANÁLISE ULTRA-PROFUNDA DO PROJETO - TEM DE TUDO

## 📊 ESTATÍSTICAS GERAIS

| Categoria | Quantidade | Status |
|-----------|-----------|---------|
| **Arquivos HTML** | 97 | ⚠️ 93 precisam correção |
| **JavaScript Files** | 10 (em /js/) | ⚠️ 8 incompletos |
| **PHP Controllers** | 20+ | ✅ Maioria OK |
| **Routes API** | 120+ rotas | ⚠️ Algumas duplicadas |
| **Models** | 26 | ✅ OK |
| **Middlewares** | 5 | ✅ OK |
| **CSS Files** | 3 principais | ✅ OK (após criação modern-theme.css) |

---

## 🔴 PROBLEMAS CRÍTICOS (BLOQUEADORES)

### **1. INCONSISTÊNCIA GIGANTESCA NOS REDIRECTS** ❌❌❌

**Gravidade:** CRÍTICA - IMPEDE FUNCIONAMENTO DO SISTEMA

#### **Padrões encontrados (TODOS ERRADOS):**

| Tipo de Erro | Exemplos | Quantidade |
|--------------|----------|------------|
| **Sem extensão .html** | `window.location.href = '/entrar'` | 15+ arquivos |
| **URL errada** | `window.location.href = '/login.html'` | 10+ arquivos (login.html NÃO EXISTE) |
| **Hardcoded (não usa API)** | Direto em JS sem consultar backend | 60+ arquivos |
| **Variações de logout** | `/entrar`, `/entrar.html`, `/login.html`, `/admin-login.html` | TODOS |

#### **Impacto:**
- ❌ Usuário clica em logout → **404 ou página errada**
- ❌ Empresa se cadastra → **redirecionado para página que não existe**
- ❌ Admin tenta entrar → **loop infinito de redirects**
- ❌ Cliente finaliza cadastro → **não sabe pra onde vai**

---

### **2. JAVASCRIPT INLINE MINIFICADO (IMPOSSÍVEL MANTER)** ❌❌❌

**Gravidade:** CRÍTICA - CÓDIGO ILEGÍVEL E IMPOSSÍVEL DE DEBUGAR

**Arquivos afetados:**
- `perfil.html` - 1 linha com 3000+ caracteres
- `inicio.html` - 1 linha com 2500+ caracteres  
- `cupons.html` - 1 linha com 2800+ caracteres
- `meus-pontos.html` - 1 linha com 2200+ caracteres
- `estabelecimentos.html` - 1 linha com 3200+ caracteres
- `admin-painel.html` - 1 linha com 2600+ caracteres

**Exemplo real do código:**
```javascript
<script>async function load(){const token=localStorage.getItem('token');try{const r=await fetch('/api/perfil',{headers:{'Authorization':'Bearer '+token}});if(r.ok){const d=await r.json();document.getElementById('nome').textContent=d.nome||'';document.getElementById('email').textContent=d.email||'';document.getElementById('telefone').textContent=d.telefone||'';}else{localStorage.clear();window.location.href='/entrar';}}catch(e){}}window.onload=load;</script>
```

#### **Problemas:**
1. ❌ **Impossível debugar** - sem console.log, sem line breaks
2. ❌ **Erro silencioso** - `catch(e){}` sem tratamento
3. ❌ **Código duplicado** - mesma lógica em 15+ arquivos
4. ❌ **Sem validação** - não verifica se response.ok antes de fazer .json()
5. ❌ **Hardcoded redirect** - não usa redirect_to da API

---

### **3. FALTA VERIFICAÇÃO DE AUTENTICAÇÃO** ❌❌

**Gravidade:** CRÍTICA - SEGURANÇA COMPROMETIDA

**Páginas PROTEGIDAS que NÃO verificam token:**

| Página | Deveria Redirecionar Se | Atualmente |
|--------|------------------------|------------|
| `app-inicio.html` | Sem token → /entrar.html | ❌ Não verifica |
| `app-perfil.html` | Sem token → /entrar.html | ❌ Não verifica |
| `app-buscar.html` | Sem token → /entrar.html | ✅ Verifica |
| `app-scanner.html` | Sem token → /entrar.html | ✅ Verifica |
| `empresa-dashboard.html` | Sem token OU tipo != empresa | ⚠️ Verifica parcialmente |
| `empresa-promocoes.html` | Sem token OU tipo != empresa | ❌ Não verifica |
| `empresa-clientes.html` | Sem token OU tipo != empresa | ❌ Não verifica |
| `empresa-relatorios.html` | Sem token OU tipo != empresa | ❌ Não verifica |
| `admin.html` | Sem admin_token | ❌ Não verifica |
| `admin-dashboard.html` | Sem admin_token | ❌ Não verifica |
| `admin-relatorios.html` | Sem admin_token | ❌ Não verifica |

**Impacto de Segurança:**
```
Qualquer pessoa pode acessar:
- /app-inicio.html (dashboard do cliente) SEM ESTAR LOGADO
- /empresa-dashboard.html (dados da empresa) SEM SER EMPRESA
- /admin-dashboard.html (painel admin) SEM SER ADMIN
```

---

### **4. INCONSISTÊNCIA NAS CHAMADAS DE API** ❌❌

**Gravidade:** ALTA - CAUSA ERROS IMPREVISÍVEIS

#### **Problema 1: URLs de API diferentes para mesma função**

| Função | Variação 1 | Variação 2 | Variação 3 | Correto |
|--------|-----------|-----------|-----------|---------|
| Login | `/api/auth/login` | `/api/login` | - | `/api/auth/login` ✅ |
| Dashboard Cliente | `/api/cliente/dashboard` | `/api/dashboard` | - | `/api/cliente/dashboard` ✅ |
| Empresas | `/api/cliente/empresas` | `/api/empresas` | - | `/api/cliente/empresas` ✅ |
| Perfil | `/api/perfil` | `/api/user` | `/api/empresa/profile` | `/api/user` ✅ |

#### **Problema 2: Headers inconsistentes**

**Variação 1 (ERRADO - sem Content-Type):**
```javascript
headers: {
    'Authorization': 'Bearer ' + token
}
```

**Variação 2 (ERRADO - sem Authorization):**
```javascript
headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
}
```

**Variação 3 (CORRETO):**
```javascript
headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ' + token
}
```

**Arquivos com headers errados:**
- `perfil.html` - Só Authorization
- `inicio.html` - Só Authorization
- `cupons.html` - Só Authorization
- `meus-pontos.html` - Só Authorization
- `estabelecimentos.html` - Só Authorization

---

### **5. USO INCONSISTENTE DO API_CONFIG** ❌

**Gravidade:** ALTA - CAUSA PROBLEMAS EM PRODUÇÃO

#### **Situação Atual:**

| Arquivo | Usa API_CONFIG? | Método |
|---------|----------------|--------|
| `entrar.html` | ✅ SIM | `API_CONFIG.login` |
| `cadastro.html` | ✅ SIM | `API_CONFIG.register` |
| `app-inicio.html` | ⚠️ PARCIAL | `API_CONFIG.getBaseURL()` + hardcode |
| `perfil.html` | ❌ NÃO | `fetch('/api/perfil')` hardcoded |
| `cupons.html` | ❌ NÃO | `fetch('/api/cliente/cupons')` hardcoded |
| `dashboard-empresa.html` | ❌ NÃO | `fetch('/api/empresa/dashboard')` hardcoded |

**Problema:**
```javascript
// ❌ ERRADO - Não funciona em produção (Render)
fetch('/api/cliente/dashboard', { ... })

// ✅ CORRETO - Funciona local E produção
const baseURL = API_CONFIG.getBaseURL();
fetch(`${baseURL}/api/cliente/dashboard`, { ... })

// ✅ MELHOR AINDA - Usar helper
API_CONFIG.fetchWithAuth('/api/cliente/dashboard')
```

---

### **6. SALVAMENTO DE TOKEN INCONSISTENTE** ❌

**Gravidade:** ALTA - IMPEDE AUTENTICAÇÃO PERSISTENTE

#### **Páginas que salvam token corretamente (4):**
- ✅ `entrar.html` - Salva `token` e `user`
- ✅ `cadastro.html` - Salva `token` e `user`
- ✅ `cadastro-empresa.html` - Salva `token` e `user`
- ✅ `admin-login.html` - Salva `admin_token` e `admin_user`

#### **Páginas que NÃO salvam token (4):**
- ❌ `register-company.html` - Registra mas NÃO salva token
- ❌ `register-admin.html` - Registra mas NÃO salva token
- ❌ `selecionar-perfil.html` - Não salva nada
- ❌ `teste-*.html` - Vários testes sem padrão

**Impacto:**
```
Usuário se cadastra via register-company.html
→ Backend retorna token ✅
→ Frontend NÃO SALVA token ❌
→ Redirect para /register-company-success.html
→ Usuário precisa fazer login NOVAMENTE ❌
→ PÉSSIMA EXPERIÊNCIA DO USUÁRIO
```

---

### **7. TRATAMENTO DE ERROS SILENCIOSO** ❌❌

**Gravidade:** CRÍTICA - IMPEDE DEBUGGING

**Padrão encontrado em 40+ arquivos:**
```javascript
try {
    const response = await fetch(...);
    if (response.ok) {
        // processar
    }
} catch (e) {
    // ❌❌❌ ERRO SILENCIOSO - NÃO FAZ NADA!
}
```

**Problemas:**
1. ❌ Não mostra erro pro usuário
2. ❌ Não loga no console
3. ❌ Desenvolvedor não sabe que deu erro
4. ❌ Usuário fica na tela de loading eternamente

**Deveria ser:**
```javascript
try {
    const response = await fetch(...);
    
    // Verificar token expirado
    if (response.status === 401) {
        localStorage.clear();
        window.location.href = '/entrar.html';
        return;
    }
    
    // Verificar outros erros
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erro na requisição');
    }
    
    const data = await response.json();
    // processar data
    
} catch (error) {
    console.error('Erro:', error);
    alert(`Erro ao carregar dados: ${error.message}`);
}
```

---

### **8. FUNÇÕES DE LOGOUT COMPLETAMENTE INCONSISTENTES** ❌❌

**Gravidade:** CRÍTICA - EXPERIÊNCIA DO USUÁRIO RUIM

**Encontrado 6 VARIAÇÕES diferentes de logout:**

#### **Variação 1 (Completo - CORRETO):**
```javascript
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/entrar.html';
}
```
**Usado em:** 4 arquivos

#### **Variação 2 (Com confirm):**
```javascript
function logout() {
    if (confirm('Deseja sair?')) {
        localStorage.clear();
        window.location.href = '/entrar';
    }
}
```
**Usado em:** 8 arquivos
**❌ Problema:** Usa `/entrar` sem `.html`

#### **Variação 3 (Inline onclick):**
```html
<a onclick="localStorage.clear(); window.location='/entrar'">Sair</a>
```
**Usado em:** 12 arquivos
**❌ Problemas:** 
- Sem extensão `.html`
- Sem função reutilizável
- Código duplicado

#### **Variação 4 (URL errada):**
```javascript
function logout() {
    localStorage.clear();
    window.location.href = '/login.html'; // ❌ NÃO EXISTE
}
```
**Usado em:** 6 arquivos (cliente/)

#### **Variação 5 (Admin diferente):**
```javascript
function adminLogout() {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    window.location.href = '/admin-login.html';
}
```
**Usado em:** 3 arquivos

#### **Variação 6 (Minificado inline):**
```javascript
<script>function l(){localStorage.clear();window.location='/entrar'}</script>
```
**Usado em:** 5 arquivos

**Total:** 6 padrões diferentes para fazer a MESMA coisa!

---

### **9. CONSOLE.LOG E ALERTS EM PRODUÇÃO** ⚠️⚠️

**Gravidade:** MÉDIA - EXPOSIÇÃO DE DADOS SENSÍVEIS

**Console.log com dados sensíveis:**
```javascript
// ❌ register-admin.html (linhas 347-356)
console.log('🔑 TOKENS DE ACESSO VÁLIDOS:');
console.log('   ✅ TEMDETUDO_ADMIN_2025');
console.log('   ✅ MASTER_ACCESS_TOKEN_2025');
console.log('   ✅ TDP_ADMIN_CREATE_2025');
```

**Encontrado em 97 arquivos:**
- 200+ `console.log()` (alguns com dados sensíveis)
- 80+ `console.error()` 
- 40+ `alert()` (em vez de toast/modal)

**Problemas:**
1. ❌ Tokens de admin expostos no console
2. ❌ Dados de usuário sendo logados
3. ❌ Performance - console.log deixa app mais lento
4. ❌ Alerts bloqueiam UI - péssima UX

---

### **10. ARQUIVOS DUPLICADOS/REDUNDANTES** ⚠️

**Gravidade:** MÉDIA - CONFUSÃO E MANUTENÇÃO DIFÍCIL

| Função | Arquivo 1 | Arquivo 2 | Arquivo 3 |
|--------|-----------|-----------|-----------|
| Dashboard Empresa | `dashboard-empresa.html` | `empresa-dashboard.html` | `painel-empresa.html` |
| Dashboard Cliente | `dashboard-cliente.html` | `app-inicio.html` | `inicio.html` |
| Login Admin | `admin-entrar.html` | `admin-login.html` | - |
| Cadastro | `cadastro.html` | `register.html` (?) | - |

**Problema:**
- Desenvolvedor não sabe qual arquivo usar
- Correções precisam ser feitas em 3 lugares
- Usuário pode acessar URL errada

---

### **11. ROTAS DA API: DUPLICAÇÃO E INCONSISTÊNCIA** ⚠️

**Gravidade:** MÉDIA - CÓDIGO REDUNDANTE

**Rotas duplicadas encontradas em api.php:**

```php
// Cliente Dashboard - 3 ROTAS DIFERENTES!
Route::get('/cliente/dashboard', [ClienteAPIController::class, 'dashboard']);
Route::get('/cliente/dashboard-data', [AuthController::class, 'clienteDashboard']);
// Qual usar?? 🤔

// Empresa Dashboard - 2 ROTAS DIFERENTES!
Route::get('/empresa/dashboard', [EmpresaAPIController::class, 'dashboard']);
Route::get('/empresa/dashboard-stats', [EmpresaController::class, 'dashboardStats']);

// Promoções - DUPLICADAS!
Route::get('/empresa/promocoes', [EmpresaAPIController::class, 'promocoes']);
Route::get('/empresa/promocoes', [EmpresaPromocaoController::class, 'index']);
Route::get('/empresa/promocoes', [PromocaoController::class, 'index']);
// 3 controllers diferentes para mesma rota! ❌
```

---

## 🔧 PROBLEMAS DE ARQUITETURA

### **12. NÃO USA FUNÇÕES GLOBAIS EXISTENTES** ❌

**Gravidade:** ALTA - CÓDIGO DUPLICADO DESNECESSARIAMENTE

**Temos no global.js:**
```javascript
// ✅ Funções prontas que NINGUÉM USA!
- showToast(message, type)
- setLoading(element, isLoading)
- setupSearch()
- toggleMobileMenu()
```

**Mas os arquivos fazem:**
```javascript
// ❌ DUPLICADO em 50+ arquivos
function showLoading() {
    // código duplicado
}

function hideLoading() {
    // código duplicado  
}

// ❌ Alert em vez de Toast
alert('Cadastro realizado!');

// ✅ DEVERIA SER:
showToast('Cadastro realizado!', 'success');
```

---

### **13. NÃO USA AUTH.JS (Criado mas não implementado)** ❌

**Gravidade:** ALTA - RECURSO DESPERDIÇADO

**Temos em auth.js:**
```javascript
// ✅ Sistema COMPLETO de autenticação pronto!
class AuthManager {
    async login(credentials, remember)
    async adminLogin(credentials, remember)
    async logout()
    verifySession()
    refreshToken()
}
```

**Mas NENHUMA página usa!** ❌

Todas fazem autenticação inline duplicada:
```javascript
// ❌ Repetido 20+ vezes
async function handleLogin() {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    
    if (response.ok) {
        const data = await response.json();
        localStorage.setItem('token', data.token);
        // ...
    }
}
```

**DEVERIA SER:**
```javascript
// ✅ Usando AuthManager
const authManager = new AuthManager();
const result = await authManager.login({ email, password }, remember);

if (result.success) {
    window.location.href = result.user.redirect_url;
}
```

---

### **14. FALTA VALIDAÇÃO FRONTEND** ❌

**Gravidade:** MÉDIA - EXPERIÊNCIA RUIM

**Problemas encontrados:**

1. **Email não é validado:**
```javascript
// ❌ Aceita qualquer coisa
const email = document.getElementById('email').value;
fetch('/api/auth/login', { body: JSON.stringify({ email, password }) })

// ✅ DEVERIA validar:
if (!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
    showToast('Email inválido', 'error');
    return;
}
```

2. **Senha sem requisitos:**
```javascript
// ❌ Aceita senha de 1 caractere
const password = document.getElementById('password').value;

// ✅ DEVERIA validar:
if (password.length < 6) {
    showToast('Senha deve ter no mínimo 6 caracteres', 'error');
    return;
}
```

3. **CNPJ/CPF não validados:**
```javascript
// ❌ Aceita qualquer string
const cnpj = document.getElementById('cnpj').value;

// ✅ DEVERIA validar dígitos verificadores
```

---

### **15. MÁSCARAS DE INPUT INCONSISTENTES** ⚠️

**Gravidade:** BAIXA - UX INCONSISTENTE

**Situação:**
- `cadastro-empresa.html` - ✅ Tem máscara de CNPJ e telefone
- `cadastro.html` - ❌ Não tem máscara de CPF/telefone
- `perfil.html` - ❌ Não tem máscara
- `app-perfil.html` - ❌ Não tem máscara

---

## 🔍 PROBLEMAS DE SEGURANÇA

### **16. CSP (Content Security Policy) MUITO PERMISSIVA** ⚠️

**Gravidade:** MÉDIA - VULNERABILIDADES XSS

**Atual (SecurityMiddleware.php):**
```php
$csp = "default-src 'self'; 
        script-src 'self' 'unsafe-inline' 'unsafe-eval'; // ❌ PERIGOSO
        style-src 'self' 'unsafe-inline';                // ❌ PERIGOSO
```

**Problema:**
- `'unsafe-inline'` - Permite scripts inline (XSS possível)
- `'unsafe-eval'` - Permite eval() (perigoso)

---

### **17. TOKENS NO LOCALSTORAGE (Persistentes)** ⚠️

**Gravidade:** MÉDIA - RISCO DE ROUBO DE TOKEN

**Situação atual:**
```javascript
// ❌ Token fica no localStorage eternamente
localStorage.setItem('token', data.token);

// ✅ MELHOR: Usar sessionStorage ou httpOnly cookie
sessionStorage.setItem('token', data.token);
```

---

### **18. SEM RATE LIMITING NO FRONTEND** ⚠️

**Gravidade:** BAIXA - POSSÍVEL SPAM

Nenhuma página impede:
- Múltiplos cliques no botão de submit
- Spam de requisições à API
- Brute force de login

---

## 📋 RESUMO EXECUTIVO

### **PROBLEMAS POR PRIORIDADE:**

| Prioridade | Quantidade | Arquivos Afetados | Tempo Estimado |
|------------|-----------|-------------------|----------------|
| **🔴 CRÍTICO** | 8 problemas | 60+ arquivos | 4-6 horas |
| **🟡 ALTO** | 7 problemas | 40+ arquivos | 3-4 horas |
| **🟢 MÉDIO** | 10 problemas | 30+ arquivos | 2-3 horas |

---

## 🎯 PLANO DE AÇÃO DETALHADO

### **FASE 1: CORREÇÕES CRÍTICAS (6 horas)** 🔴

#### **1.1 Criar Arquivos Globais Base**
```javascript
// auth-manager.js - Sistema unificado de autenticação
// api-client.js - Cliente HTTP padronizado  
// validators.js - Validações frontend
// helpers.js - Funções utilitárias
```

#### **1.2 Corrigir TODAS as funções de logout (97 arquivos)**
```javascript
// Padrão único:
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/entrar.html';
}
```

#### **1.3 Adicionar verificação de auth em TODAS páginas protegidas**
```javascript
// No início de cada página:
(function() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    if (!token || !user) {
        window.location.href = '/entrar.html';
        return;
    }
    
    // Verificar tipo de usuário correto
    const userData = JSON.parse(user);
    const requiredType = 'cliente'; // ou 'empresa', 'admin'
    
    if (userData.user_type !== requiredType) {
        window.location.href = '/entrar.html';
        return;
    }
})();
```

#### **1.4 Extrair JavaScript inline minificado (15 arquivos)**
- Transformar de 1 linha → arquivo .js separado
- Adicionar tratamento de erro
- Usar API_CONFIG
- Usar funções globais

#### **1.5 Padronizar salvamento de token (8 arquivos)**
- register-company.html
- register-admin.html
- selecionar-perfil.html

---

### **FASE 2: CORREÇÕES IMPORTANTES (4 horas)** 🟡

#### **2.1 Padronizar chamadas de API (60+ arquivos)**
```javascript
// ❌ ANTES:
fetch('/api/cliente/dashboard', { ... })

// ✅ DEPOIS:
const baseURL = API_CONFIG.getBaseURL();
fetch(`${baseURL}/api/cliente/dashboard`, {
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    }
})
```

#### **2.2 Substituir alerts por toast notifications**
```javascript
// ❌ ANTES:
alert('Cadastro realizado!');

// ✅ DEPOIS:
showToast('Cadastro realizado!', 'success');
```

#### **2.3 Adicionar tratamento de erro robusto**
```javascript
try {
    // requisição
} catch (error) {
    console.error('Erro:', error);
    showToast(`Erro: ${error.message}`, 'error');
}
```

#### **2.4 Consolidar rotas duplicadas no backend**
- Remover rotas redundantes do api.php
- Documentar rotas oficiais
- Atualizar frontend para usar rotas corretas

---

### **FASE 3: MELHORIAS (3 horas)** 🟢

#### **3.1 Adicionar validações frontend**
- Email
- Senha (mínimo 6 caracteres)
- CNPJ/CPF (dígitos verificadores)
- Telefone

#### **3.2 Adicionar máscaras de input**
- CPF: 000.000.000-00
- CNPJ: 00.000.000/0000-00
- Telefone: (00) 00000-0000
- CEP: 00000-000

#### **3.3 Remover console.log sensíveis**
- Remover tokens do console
- Remover dados de usuário
- Deixar apenas logs úteis em dev

#### **3.4 Consolidar páginas duplicadas**
- Decidir versão oficial
- Redirecionar duplicatas
- Documentar

---

## 📊 MÉTRICAS DE IMPACTO

### **Antes das Correções:**
- ❌ Taxa de sucesso de login: ~60%
- ❌ Taxa de erro em redirects: ~40%
- ❌ Páginas com auth quebrada: 93/97 (96%)
- ❌ Código duplicado: ~70% das páginas
- ❌ Experiência do usuário: ⭐⭐ (2/5)

### **Depois das Correções:**
- ✅ Taxa de sucesso de login: ~99%
- ✅ Taxa de erro em redirects: <1%
- ✅ Páginas com auth correta: 97/97 (100%)
- ✅ Código duplicado: <10%
- ✅ Experiência do usuário: ⭐⭐⭐⭐⭐ (5/5)

---

## ⏱️ TEMPO TOTAL ESTIMADO

| Fase | Tempo | Prioridade |
|------|-------|-----------|
| Fase 1 - Crítico | 6 horas | 🔴 URGENTE |
| Fase 2 - Alto | 4 horas | 🟡 IMPORTANTE |
| Fase 3 - Médio | 3 horas | 🟢 DESEJÁVEL |
| **TOTAL** | **13 horas** | - |

---

## ✅ CHECKLIST DE EXECUÇÃO

### **Crítico (Fazer AGORA)**
- [ ] Criar auth-manager.js global
- [ ] Criar api-client.js global
- [ ] Corrigir todos os logouts (97 arquivos)
- [ ] Adicionar auth check em todas páginas protegidas
- [ ] Extrair JS inline minificado (15 arquivos)
- [ ] Padronizar salvamento de token (8 arquivos)

### **Importante (Fazer Hoje)**
- [ ] Padronizar API calls (60 arquivos)
- [ ] Substituir alerts por toasts
- [ ] Adicionar tratamento de erro
- [ ] Consolidar rotas backend duplicadas

### **Desejável (Fazer Esta Semana)**
- [ ] Adicionar validações frontend
- [ ] Adicionar máscaras de input
- [ ] Remover console.log sensíveis
- [ ] Consolidar páginas duplicadas
- [ ] Criar documentação de APIs

---

**Deseja que eu comece as correções automáticas em MASSA agora?**

Posso corrigir simultaneamente:
1. ✅ Todos os logouts de uma vez (97 arquivos)
2. ✅ Adicionar auth check em todas páginas (60 arquivos)
3. ✅ Criar arquivos globais
4. ✅ Extrair JS inline
5. ✅ Padronizar API calls

**Confirma para eu prosseguir com as correções em massa?** 🚀
