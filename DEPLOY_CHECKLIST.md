# ✅ CHECKLIST DE DEPLOY SEGURO - Tem de Tudo

## 🔥 URGENTE - ANTES DE QUALQUER DEPLOY

### 1. Segurança de Credenciais
- [ ] **MUDAR SENHA DO BANCO IMEDIATAMENTE**
  - Acesse Render Dashboard → PostgreSQL → Reset Password
  - Senha atual (EXPOSTA): `8lBmP4LBS1rKAgAYZRdEdBYfHlpHSX99`
  - Gere nova senha forte (min 32 caracteres)
  - Atualize `.env` de produção com nova senha
  
- [ ] **Verificar .gitignore**
  - Confirmar que `.env` está no .gitignore
  - Nunca commitar `.env` real
  - Usar apenas `.env.example` com placeholders

- [ ] **Auditar repositório Git**
  - Verificar histórico: `git log --all --full-history --source -- backend/.env`
  - Se `.env` foi commitado: usar `git filter-branch` ou BFG Repo-Cleaner
  - Considerar rotacionar TODAS as credenciais comprometidas

### 2. Configuração do Ambiente

- [ ] **Variáveis de ambiente essenciais (.env)**
  ```bash
  APP_ENV=production
  APP_DEBUG=false
  APP_KEY=<gerar com: php artisan key:generate>
  
  DB_CONNECTION=pgsql
  DB_HOST=<novo-host-render>
  DB_PASSWORD=<NOVA_SENHA_GERADA>
  DB_CHARSET=utf8mb4
  DB_COLLATION=utf8mb4_unicode_ci
  
  CACHE_STORE=database
  CACHE_PREFIX=tdt_
  
  LOG_LEVEL=error
  LOG_CHANNEL=stack
  ```

- [ ] **JWT Secrets**
  - Gerar novo: `php artisan jwt:secret`
  - Nunca reutilizar entre ambientes

### 3. Banco de Dados

- [ ] **Migrar schema**
  ```bash
  php artisan migrate --force
  ```

- [ ] **Verificar charset UTF-8**
  ```sql
  SELECT character_set_name FROM information_schema.character_sets;
  -- Deve retornar UTF8MB4
  ```

- [ ] **Criar índices críticos**
  - `users.email` (unique)
  - `pontos.user_id, pontos.created_at`
  - `empresas.status, empresas.categoria`

### 4. Performance

- [ ] **Cache de configuração**
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- [ ] **Build de assets**
  ```powershell
  # Windows
  .\build-assets.ps1
  
  # Linux/Mac
  bash build-assets.sh
  ```

- [ ] **Atualizar versão do JS nas páginas**
  - Alterar `?v=20260401-stab14` para `?v=20260406-prod`
  - Ou usar `/dist/stitch-app.min.js` (recomendado)

### 5. Segurança de Headers

- [ ] **Configurar CORS** (config/cors.php)
  - `allowed_origins` → domínio específico (não usar `*` em produção)

- [ ] **Headers de segurança** (adicionar middleware)
  ```php
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  X-XSS-Protection: 1; mode=block
  Strict-Transport-Security: max-age=31536000
  ```

### 6. Verificações Pré-Deploy

- [ ] **Rodar testes** (quando existirem)
  ```bash
  php artisan test
  ```

- [ ] **Verificar logs verbosos**
  - Buscar por `Log::info.*password|data|headers`
  - Já corrigido em AuthController, mas verificar outros controllers

- [ ] **Validar rotas públicas**
  ```bash
  php artisan route:list --compact
  ```
  - Confirmar que rotas admin têm middleware `auth:sanctum`

### 7. Deploy na Render

- [ ] **Variáveis de ambiente**
  - Configurar todas as vars do .env no Render Dashboard
  - Confirmar que `APP_KEY` e `JWT_SECRET` estão definidos

- [ ] **Build Command**
  ```bash
  bash deploy-render.sh
  ```

- [ ] **Start Command**
  ```bash
  bash entrypoint-render.sh
  ```

### 8. Pós-Deploy

- [ ] **Smoke Tests**
  - [ ] Login como cliente (POST /api/auth/login)
  - [ ] Check-in (POST /api/pontos/checkin)
  - [ ] Listar empresas (GET /api/empresas)
  - [ ] Dashboard admin (GET /api/admin/totals)

- [ ] **Monitoramento**
  - [ ] Configurar Sentry (variável `SENTRY_DSN` já existe)
  - [ ] Ativar logs de erro
  - [ ] Configurar alertas de uptime (UptimeRobot, Pingdom, etc.)

- [ ] **Backup inicial**
  ```bash
  pg_dump -h <host> -U temdetudo -d aplicativo_tem_de_tudo > backup_$(date +%Y%m%d).sql
  ```

### 9. Documentação

- [ ] **Atualizar README.md**
  - Endpoints da API
  - Como rodar localmente
  - Comandos de build

- [ ] **Credenciais de teste**
  - Documentar usuários demo (arquivo separado, NÃO no Git)
  - Exemplo: `cliente1@email.com / senha123`

## 🚨 ROLLBACK PLAN

Se algo der errado:

1. **Reverter deploy**
   ```bash
   git revert HEAD
   git push origin main
   ```

2. **Restaurar backup do banco**
   ```bash
   psql -h <host> -U temdetudo -d aplicativo_tem_de_tudo < backup_20260406.sql
   ```

3. **Voltar versão anterior no Render**
   - Dashboard → Deploy → "Revert to this version"

## ✅ CHECKLIST FINAL

Antes de marcar deploy como concluído:

- [ ] ✅ Senha do banco alterada e testada
- [ ] ✅ App rodando sem erros 500
- [ ] ✅ Login funcionando para os 3 perfis
- [ ] ✅ Assets minificados carregando
- [ ] ✅ UTF-8 renderizando corretamente
- [ ] ✅ Monitoramento configurado
- [ ] ✅ Backup criado

---

**Última atualização:** 06/04/2026  
**Responsável:** [Seu nome]
