# 🔥 AÇÕES IMEDIATAS - NÃO DEIXE PARA DEPOIS

## 1. TROCAR SENHA DO BANCO (5 minutos) ⚠️ CRÍTICO

### Passo a passo:

1. **Acessar Render Dashboard:**
   - https://dashboard.render.com
   - Login com sua conta

2. **Navegar para PostgreSQL:**
   - Serviços → PostgreSQL
   - Procurar: `aplicativo_tem_de_tudo`
   - Clicar no serviço

3. **Resetar senha:**
   - Aba "Settings"
   - Seção "Security"
   - Botão "Reset Database Password"
   - Confirmar ação

4. **Copiar nova senha:**
   - Render vai gerar automaticamente
   - Exemplo: `A9mK2nQ8rL3vX7wP5yT1zH6jN4sB0cF3`
   - ⚠️ **COPIAR E SALVAR EM LOCAL SEGURO**

5. **Atualizar .env de produção:**
   - Render → Service (Backend) → Environment
   - Editar variável `DB_PASSWORD`
   - Colar nova senha
   - Salvar (vai reiniciar o app automaticamente)

6. **Atualizar .env local:**
   ```bash
   DB_PASSWORD=<NOVA_SENHA_AQUI>
   ```

### ⏱️ Tempo total: 5 minutos
### 🔴 Prioridade: MÁXIMA
### ✅ Quando fazer: AGORA (antes de qualquer outra coisa)

---

## 2. VERIFICAR HISTÓRICO GIT (5 minutos)

### Comandos:

```powershell
cd C:\Users\X472795\Desktop\tem-de-tudo\tem-de-tudo

# Verificar se .env real foi commitado
git log --all --full-history -- backend/.env

# Verificar todos os arquivos de ambiente
git log --all --full-history -- **/.env
```

### Se aparecer algum commit:

```powershell
# OPÇÃO 1: BFG Repo-Cleaner (recomendado)
# 1. Baixar: https://rtyley.github.io/bfg-repo-cleaner/
# 2. Executar:
java -jar bfg.jar --delete-files .env
git reflog expire --expire=now --all
git gc --prune=now --aggressive
git push --force

# OPÇÃO 2: git filter-branch (manual)
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch backend/.env" \
  --prune-empty --tag-name-filter cat -- --all
git push --force
```

⚠️ **IMPORTANTE:** Depois de limpar, TODAS as credenciais comprometidas devem ser rotacionadas:
- DB_PASSWORD (já vai fazer no passo 1)
- APP_KEY
- JWT_SECRET
- Mercado Pago keys
- VAPID keys

---

## 3. COMMITAR CORREÇÕES DE SEGURANÇA (2 minutos)

```powershell
cd C:\Users\X472795\Desktop\tem-de-tudo\tem-de-tudo

git add .
git commit -m "🔒 SECURITY: Remove exposed credentials and sensitive logs

- Remove real PostgreSQL credentials from .env.example
- Sanitize verbose logging in AuthController (no more password logs)
- Add UTF-8 charset configuration (DB_CHARSET, DB_COLLATION)
- Add cache prefix configuration (CACHE_PREFIX=tdt_)
- Create CacheResponse middleware for performance
- Add build scripts (build-assets.ps1, build-assets.sh)
- Create deployment documentation (DEPLOY_CHECKLIST.md, CACHE_OPTIMIZATION.md)

BREAKING CHANGE: Database password must be updated immediately in production
See DEPLOY_CHECKLIST.md for step-by-step instructions"

git push origin main
```

---

## 4. RODAR BUILD DE ASSETS (5 minutos)

```powershell
cd C:\Users\X472795\Desktop\tem-de-tudo\tem-de-tudo\backend

# Se Node.js não estiver instalado:
# Baixar: https://nodejs.org (versão LTS)

# Executar build
.\build-assets.ps1

# Verificar resultado
Get-Item public/js/stitch-app.js, public/dist/stitch-app.min.js | 
    Select-Object Name, @{N='Size(KB)';E={[math]::Round($_.Length/1KB,2)}}
```

### Resultado esperado:
```
Name                     Size(KB)
----                     --------
stitch-app.js            148.77
stitch-app.min.js         49.12
```

---

## 5. DEPLOY (OPCIONAL - pode deixar para amanhã)

Se quiser fazer deploy hoje:

```powershell
cd backend

# 1. Testar localmente
php artisan config:clear
php artisan cache:clear
php artisan serve

# 2. Commitar versão minificada
git add public/dist/
git commit -m "build: Add minified assets"
git push origin main

# 3. Deploy na Render (automático)
# Render vai detectar push e fazer deploy
```

Seguir [DEPLOY_CHECKLIST.md](../DEPLOY_CHECKLIST.md) passo a passo

---

## ✅ CHECKLIST DE HOJE

- [ ] ✅ **SENHA DO BANCO ALTERADA** (5 min) 🔴 URGENTE
- [ ] ✅ Histórico Git verificado (5 min)
- [ ] ✅ Correções commitadas (2 min)
- [ ] ✅ Build de assets executado (5 min)
- [ ] ⏸️ Deploy (opcional - pode deixar para amanhã)

**Tempo total:** 17 minutos  
**Prioridade:** Fazer AGORA (pelo menos itens 1-3)

---

## 🚨 SE ALGO DER ERRADO

### App não sobe após trocar senha:
```bash
# Verificar variáveis de ambiente
# Render → Service → Environment → DB_PASSWORD
# Deve ter EXATAMENTE a nova senha (sem espaços)
```

### Build de assets falha:
```powershell
# Instalar terser manualmente
npm install -g terser

# Executar novamente
.\build-assets.ps1
```

### Git push rejeitado:
```powershell
# Puxar mudanças primeiro
git pull origin main --rebase
git push origin main
```

---

**Gerado em:** 06/04/2026  
**Autor:** GitHub Copilot
