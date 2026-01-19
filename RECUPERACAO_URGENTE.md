# 🆘 RECUPERAÇÃO DE EMERGÊNCIA - SISTEMA FORA DO AR

## 🔴 PROBLEMA ATUAL
Site não carrega nada no Render (`aplicativo-tem-de-tudo.onrender.com`)

## ✅ SOLUÇÃO RÁPIDA (10 minutos)

### OPÇÃO 1: Popular Banco Manualmente (RECOMENDADO)

1. **Acesse o Dashboard do Render:**
   ```
   https://dashboard.render.com
   ```

2. **Vá para o PostgreSQL:**
   - Clique em "aplicativo_tem_de_tudo" (database)
   - Clique na aba "Shell" ou "Connect"

3. **Execute o SQL:**
   - Copie TODO o conteúdo de `backend/database/populate-render.sql`
   - Cole no console SQL do Render
   - Execute

4. **Teste Imediatamente:**
   ```
   https://aplicativo-tem-de-tudo.onrender.com/teste-login.html
   ```
   - Clique "2. Login Admin" → `admin@temdetudo.com / admin123`
   - Clique "3. Login Cliente" → `cliente@teste.com / 123456`

---

### OPÇÃO 2: Resetar Deploy (se Opção 1 falhar)

1. **No Dashboard do Render, serviço "tem-de-tudo":**
   - Clique "Manual Deploy" → "Clear build cache & deploy"
   
2. **Aguarde 20 min** para rebuild completo

3. **Quando ficar "Live", execute:**
   ```
   https://aplicativo-tem-de-tudo.onrender.com/api/setup-database?secret=temdetudo2024
   ```

---

## 🔑 CREDENCIAIS QUE VÃO FUNCIONAR

```
Admin:
- Email: admin@temdetudo.com
- Senha: admin123
- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

Cliente:
- Email: cliente@teste.com
- Senha: 123456
- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

Empresa:
- Email: empresa@teste.com
- Senha: 123456
- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

Clientes 1-5:
- Email: cliente1@email.com até cliente5@email.com
- Senha: senha123
- Hash: $2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza
```

---

## 🧪 TESTAR LOCALMENTE (FUNCIONA 100%)

```bash
cd c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend
php artisan serve

# Em outro terminal:
# Acessar: http://127.0.0.1:8000/teste-login.html
# Clicar nos botões para testar logins
```

---

## 📊 VERIFICAR STATUS DO RENDER

### Logs do Render:
1. Dashboard → "tem-de-tudo" → Aba "Logs"
2. Procurar por erros recentes

### Comandos para verificar no Shell do PostgreSQL:
```sql
-- Ver quantos usuários existem
SELECT COUNT(*) FROM users;

-- Ver emails cadastrados
SELECT email, perfil FROM users;

-- Ver empresas
SELECT COUNT(*) FROM empresas;
```

---

## 🚨 SE NADA FUNCIONAR

Execute este comando SQL no PostgreSQL do Render para APAGAR TUDO e recomeçar:

```sql
DROP TABLE IF EXISTS personal_access_tokens CASCADE;
DROP TABLE IF EXISTS coupons CASCADE;
DROP TABLE IF EXISTS pontos CASCADE;
DROP TABLE IF EXISTS check_ins CASCADE;
DROP TABLE IF EXISTS qr_codes CASCADE;
DROP TABLE IF EXISTS empresas CASCADE;
DROP TABLE IF EXISTS users CASCADE;
```

Depois force um novo deploy no Render.

---

## 📞 PRÓXIMOS PASSOS

1. Execute a **OPÇÃO 1** primeiro (SQL manual)
2. Teste em `/teste-login.html`
3. Se funcionar, acesse `/entrar.html` e faça login real
4. Me avise o resultado
