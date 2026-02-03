# 🔑 CREDENCIAIS DE TESTE - TEM DE TUDO

**TODOS OS USUÁRIOS TÊM A SENHA:** `senha123`

---

## 👤 ADMIN

| Email | Senha | Perfil |
|-------|-------|--------|
| admin@temdetudo.com | senha123 | admin |

**Acesso:** https://tem-de-tudo-9g7r.onrender.com/admin-login.html

---

## 🛍️ CLIENTES

| Email | Senha | Pontos | Nível |
|-------|-------|--------|-------|
| joao@cliente.com | senha123 | 1.500 | Prata ⚪ |
| maria@cliente.com | senha123 | 500 | Bronze 🟤 |
| pedro@cliente.com | senha123 | 5.500 | Ouro 🟡 |
| ana@cliente.com | senha123 | 12.000 | Diamante 💎 |
| carlos@cliente.com | senha123 | 250 | Bronze 🟤 |

**Acesso:** https://tem-de-tudo-9g7r.onrender.com/entrar.html

---

## 🏪 EMPRESAS

| Email | Senha | Nome |
|-------|-------|------|
| contato@pizzariabella.com | senha123 | Pizzaria Bella 🍕 |
| contato@fashionstyle.com | senha123 | Loja Fashion Style 👗 |
| contato@cafearoma.com | senha123 | Café Aroma ☕ |
| contato@fitgym.com | senha123 | Academia FitGym 💪 |
| contato@salonbeauty.com | senha123 | Salão Beauty 💇 |

**Acesso:** https://tem-de-tudo-9g7r.onrender.com/entrar.html

---

## 📝 COMO POPULAR O BANCO

### **Opção 1: Usando SQL direto no Render**

1. Acesse: https://dashboard.render.com
2. Vá em PostgreSQL → `tem-de-tudo-db`
3. Clique em "Shell" ou "Connect"
4. Cole o conteúdo do arquivo `backend/database/seed_test_users.sql`
5. Execute

### **Opção 2: Via API (setup-database)**

Acesse no navegador:
```
https://tem-de-tudo-9g7r.onrender.com/api/setup-database
```

### **Opção 3: Criar usuário pelo cadastro**

1. Acesse: https://tem-de-tudo-9g7r.onrender.com/cadastro.html
2. Escolha perfil (Cliente ou Empresa)
3. Preencha dados
4. Clique em "Criar Conta"
5. Será redirecionado automaticamente

---

## 🧪 TESTES

### **Teste de Login Cliente:**
```
Email: joao@cliente.com
Senha: senha123
```

### **Teste de Login Empresa:**
```
Email: contato@pizzariabella.com
Senha: senha123
```

### **Teste de Login Admin:**
```
Email: admin@temdetudo.com
Senha: senha123
```

---

## 🔐 HASH DA SENHA

O hash usado é:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

Corresponde à senha: **senha123**

---

## ✅ VERIFICAR SE USUÁRIOS FORAM CRIADOS

Execute no PostgreSQL:

```sql
SELECT 
    id,
    name,
    email,
    perfil,
    status,
    pontos_totais,
    nivel
FROM users
ORDER BY perfil, name;
```

---

## 🚀 PRÓXIMOS PASSOS

1. ✅ Popular banco com usuários de teste
2. ✅ Fazer login com qualquer credencial acima
3. ✅ Testar dashboards (cliente, empresa, admin)
4. ✅ Testar busca de empresas
5. ⚠️ Criar páginas faltantes:
   - estabelecimento.html
   - meu-qrcode.html
   - historico.html
   - scanner.html
   - promocoes.html

---

**📅 Atualizado:** 3 de fevereiro de 2026  
**🌐 URL:** https://tem-de-tudo-9g7r.onrender.com
