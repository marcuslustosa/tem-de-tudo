# ✅ GUIA DE TESTE COMPLETO - TEM DE TUDO

**Última atualização:** 28/01/2026

---

## 🔐 CREDENCIAIS PARA TESTE

### 1. **Admin**
```
Email: admin@temdetudo.com
Senha: admin123
URL: https://aplicativo-tem-de-tudo.onrender.com/admin-login.html
```

### 2. **Cliente**
```
Email: cliente@teste.com
Senha: 123456
URL: https://aplicativo-tem-de-tudo.onrender.com/entrar.html
```

### 3. **Empresa**
```
Email: empresa@teste.com
Senha: 123456
URL: https://aplicativo-tem-de-tudo.onrender.com/entrar.html
```

### 4. **Clientes Fictícios (1-50)**
```
Email: cliente1@email.com até cliente50@email.com
Senha: senha123
```

---

## 🧪 TESTES A REALIZAR

### ✅ TESTE 1: Login Cliente
1. Abrir https://aplicativo-tem-de-tudo.onrender.com/entrar.html
2. Preencher: `cliente@teste.com` / `123456`
3. Clicar em "Entrar"
4. **Esperado:** Redirecionar para `/app-inicio.html`
5. **Verificar:** Token salvo em localStorage

**Console deve mostrar:**
```
🔑 Tentando login: {email: "cliente@teste.com", api: "..."}
Resposta do login: {success: true, data: {...}}
```

---

### ✅ TESTE 2: Login Admin
1. Abrir https://aplicativo-tem-de-tudo.onrender.com/admin-login.html
2. Preencher: `admin@temdetudo.com` / `admin123`
3. Clicar em "Entrar no Sistema"
4. **Esperado:** Redirecionar para `/admin.html`
5. **Verificar:** admin_token salvo em localStorage

**Console deve mostrar:**
```
🔑 Admin login: {email: "admin@temdetudo.com"}
Resposta admin: {success: true, data: {...}}
```

---

### ✅ TESTE 3: Cadastro Novo Cliente
1. Abrir https://aplicativo-tem-de-tudo.onrender.com/cadastro.html
2. Preencher formulário:
   - Nome: Teste Novo
   - Email: teste@novo.com
   - Telefone: (11) 99999-9999
   - Senha: 123456
   - Aceitar termos
3. Clicar em "Criar Conta"
4. **Esperado:** Sucesso + Redirecionar para `/app-inicio.html`

---

### ✅ TESTE 4: Dados Fictícios Aparecem
1. Fazer login como cliente
2. Ir para página de empresas
3. **Verificar:** 8 empresas com fotos aparecem:
   - Restaurante Sabor & Arte
   - Academia Corpo Forte
   - Cafeteria Aroma Premium
   - Pet Shop Amigo Fiel
   - Salão Beleza Total
   - Mercado Bom Preço
   - Farmácia Saúde Mais
   - Padaria Pão Quentinho

---

### ✅ TESTE 5: Visual Padronizado
1. Abrir `/index.html`
2. Abrir `/entrar.html`
3. Abrir `/cadastro.html`
4. Abrir `/admin-login.html`

**Verificar:**
- ✅ Mesmo gradiente (roxo/azul)
- ✅ Mesmos botões (estilo, cores)
- ✅ Mesmos inputs (bordas, foco)
- ✅ CSS carregando de `/css/app-unified.css`

---

### ✅ TESTE 6: Redirecionamentos
**Cliente:**
- Login → `/app-inicio.html` ✅
- Logout → `/entrar.html` ✅

**Admin:**
- Login → `/admin.html` ✅
- Logout → `/admin-login.html` ✅

**Empresa:**
- Login → `/empresa.html` ✅

---

### ✅ TESTE 7: Token Persistência
1. Fazer login
2. Fechar navegador
3. Abrir novamente
4. Acessar página protegida
5. **Esperado:** Continuar logado

---

### ✅ TESTE 8: Validações
**Login:**
- Email vazio → Erro ✅
- Senha vazia → Erro ✅
- Credenciais erradas → "Credenciais inválidas" ✅

**Cadastro:**
- Email inválido → Erro ✅
- Senha fraca → Erro ✅
- Termos não aceitos → Erro ✅

---

## 🐛 PROBLEMAS CONHECIDOS

### ❌ NÃO FUNCIONA:
- [ ] Recuperação de senha
- [ ] Login social (Google, Facebook)
- [ ] Algumas páginas podem não existir

### ⚠️ A VERIFICAR:
- [ ] Todas as páginas existem?
- [ ] Todos os redirecionamentos funcionam?
- [ ] Dados aparecem nas listagens?

---

## 📊 CHECKLIST FINAL

### Backend:
- [x] API `/api/auth/login` funciona
- [x] API `/api/auth/register` funciona
- [x] API `/api/admin/login` funciona
- [x] Banco de dados populado
- [x] 8 empresas com imagens
- [x] 53 usuários (1 admin + 1 cliente + 1 empresa + 50 clientes)

### Frontend:
- [x] CSS unificado aplicado
- [x] config.js configurado
- [x] Formulários com validação
- [x] Mensagens de erro/sucesso
- [x] Loading states

### Funcionalidades:
- [x] Login cliente
- [x] Login admin
- [x] Login empresa
- [x] Cadastro
- [x] Logout
- [ ] Listagem de empresas (A TESTAR)
- [ ] Dashboard cliente (A TESTAR)
- [ ] Dashboard admin (A TESTAR)

---

## 🚀 PRÓXIMOS PASSOS

1. **Testar TODOS os logins** ✅
2. **Verificar redirecionamentos** ⏳
3. **Testar listagens** ⏳
4. **Corrigir páginas que faltam** ⏳
5. **Validar fluxo completo** ⏳

---

**Status Geral:** 🟡 Parcialmente funcional
**Prioridade:** 🔥 Alta - Testar agora após deploy
