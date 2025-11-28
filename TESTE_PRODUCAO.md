# 🚀 Guia de Teste em Produção - Render

## 🌐 URLs de Produção

**Site Principal:**
```
https://app-tem-de-tudo.onrender.com
```

**API Base:**
```
https://app-tem-de-tudo.onrender.com/api
```

---

## 🧪 Páginas para Testar

### 1️⃣ Teste de Autenticação (Debug)
```
https://app-tem-de-tudo.onrender.com/teste-auth.html
```
- Interface simples para testar registro e login
- Mostra resposta completa da API
- Exibe dados do localStorage

### 2️⃣ Cadastro
```
https://app-tem-de-tudo.onrender.com/register.html
```
**Teste com:**
- Email: `seu-email@teste.com`
- Senha: `12345678`
- Perfil: Cliente ou Empresa

### 3️⃣ Login
```
https://app-tem-de-tudo.onrender.com/login.html
```
**Credenciais de teste:**
- Email: `cliente@teste.com`
- Senha: `senha123`

### 4️⃣ Login Admin
```
https://app-tem-de-tudo.onrender.com/admin-login.html
```
**Credencial:**
- Email: `admin@temdetudo.com`
- Senha: `admin123`

---

## 🔍 Endpoints da API

### Autenticação
```
POST https://app-tem-de-tudo.onrender.com/api/auth/register
POST https://app-tem-de-tudo.onrender.com/api/auth/login
POST https://app-tem-de-tudo.onrender.com/api/admin/login
```

### Usuário
```
GET https://app-tem-de-tudo.onrender.com/api/user
(Requer: Authorization: Bearer TOKEN)
```

### Cupons
```
GET https://app-tem-de-tudo.onrender.com/api/cupons
(Requer: Authorization: Bearer TOKEN)
```

### Histórico
```
GET https://app-tem-de-tudo.onrender.com/api/pontos/historico
(Requer: Authorization: Bearer TOKEN)
```

---

## ✅ Correções Feitas

### Antes (❌ Não funcionava em produção):
```javascript
const baseUrl = window.location.origin;
const apiUrl = `${baseUrl}/api/auth/login`;
const response = await fetch(apiUrl, {...});
```

### Depois (✅ Funciona em qualquer ambiente):
```javascript
const response = await fetch('/api/auth/login', {...});
```

---

## 📊 Fluxo de Teste Completo

### 1. Criar Conta
1. Acesse: `https://app-tem-de-tudo.onrender.com/register.html`
2. Escolha perfil: **Cliente**
3. Preencha:
   - Nome: `Teste Produção`
   - Email: `teste.prod@email.com`
   - Senha: `12345678`
   - Telefone: `(11) 98765-4321`
4. Clique em **Criar conta**
5. ✅ Deve redirecionar para `/dashboard-cliente.html`

### 2. Fazer Login
1. Acesse: `https://app-tem-de-tudo.onrender.com/login.html`
2. Use as credenciais criadas
3. ✅ Deve autenticar e redirecionar

### 3. Ver Dashboard
```
https://app-tem-de-tudo.onrender.com/dashboard-cliente.html
```
- ✅ Deve mostrar cupons mockados
- ✅ Deve mostrar histórico de atividades
- ✅ Deve mostrar pontos e nível

### 4. Testar Páginas do Cliente
```
https://app-tem-de-tudo.onrender.com/cliente/cupons.html
https://app-tem-de-tudo.onrender.com/cliente/pontos.html
https://app-tem-de-tudo.onrender.com/cliente/perfil.html
https://app-tem-de-tudo.onrender.com/cliente/historico.html
```

---

## 🔧 Comandos Úteis (Deploy Render)

### Verificar Logs
No dashboard do Render:
```
Logs > Manual Deploy
```

### Forçar Redesploy
```bash
git commit --allow-empty -m "Trigger Render deploy"
git push origin main
```

### Variáveis de Ambiente (.env no Render)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app-tem-de-tudo.onrender.com

DB_CONNECTION=pgsql
DB_HOST=seu-postgres-host
DB_PORT=5432
DB_DATABASE=seu-database
DB_USERNAME=seu-username
DB_PASSWORD=sua-senha

SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=app-tem-de-tudo.onrender.com
```

---

## 🐛 Troubleshooting

### Erro 500 na API
- Verificar logs no Render
- Verificar se migrations foram executadas
- Verificar variáveis de ambiente

### Erro de CORS
- Verificar `config/cors.php`
- Verificar `SANCTUM_STATEFUL_DOMAINS`

### Banco de Dados
```bash
# Conectar no shell do Render
php artisan migrate:fresh --seed
php artisan db:seed --class=DataSeeder
```

### Criar Usuários de Teste
```bash
# No shell do Render
psql $DATABASE_URL -f backend/database/usuarios_teste.sql
```

---

## 📞 Checklist Pós-Deploy

- [ ] Site carrega em `https://app-tem-de-tudo.onrender.com`
- [ ] Registro funciona
- [ ] Login funciona
- [ ] Dashboard carrega com dados mockados
- [ ] Navegação entre páginas funciona
- [ ] Logout funciona
- [ ] Admin login funciona (separado)
- [ ] API retorna JSON correto
- [ ] localStorage salva token corretamente

---

## 🎯 Próximos Passos

1. **Testar em produção**: Use `/teste-auth.html` primeiro
2. **Criar usuários de teste**: Execute o SQL de usuários
3. **Validar fluxos**: Teste cliente, empresa e admin
4. **Monitorar logs**: Acompanhe erros no Render
5. **Ajustar se necessário**: Corrija bugs específicos de produção

---

**Status:** ✅ Pronto para teste em produção!
**Último commit:** URLs corrigidas para funcionar com Render
**Deploy:** Automático via GitHub push to main
