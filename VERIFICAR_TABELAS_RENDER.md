# 🔍 Verificar Tabelas do Banco no Render

## Método 1: Via Shell do Render (Recomendado) ⭐

### Passo a Passo:

1. **Acesse o Dashboard do Render**
   - https://dashboard.render.com
   - Entre com sua conta

2. **Abra o Shell do Serviço**
   - Clique no serviço "tem-de-tudo"
   - No canto superior direito, clique no ícone de **Shell** (terminal)
   - Aguarde o shell carregar

3. **Execute os Comandos**

```bash
# Ir para o diretório do backend
cd backend

# Verificar status das migrations
php artisan migrate:status
```

### 📋 Resultado Esperado:

```
Migration name ............................................................. Batch / Status
2014_10_12_000000_create_users_table ......................................... [1] Ran
2014_10_12_100000_create_password_reset_tokens_table ......................... [1] Ran
2019_08_19_000000_create_failed_jobs_table ................................... [1] Ran
2019_12_14_000001_create_personal_access_tokens_table ........................ [1] Ran
2024_01_01_000000_create_empresas_table ...................................... [1] Ran
2024_01_02_000000_create_inscricoes_empresas_table ........................... [1] Ran
2024_01_03_000000_create_promocoes_table ..................................... [1] Ran
2024_01_04_000000_create_cupons_table ........................................ [1] Ran
2024_01_05_000000_create_cartoes_fidelidade_table ............................ [1] Ran
2024_01_06_000000_create_bonus_adesao_table .................................. [1] Ran
2024_01_07_000000_create_bonus_aniversario_table ............................. [1] Ran
2026_02_18_000001_add_missing_fields_to_promocoes_table ...................... [2] Ran
2026_02_18_000002_add_geolocation_to_users ................................... [2] Ran
```

> ✅ Se mostrar **[X] Ran** = Migration executada com sucesso!  
> ⚠️ Se mostrar **Pending** = Migration não executou

### 4. **Verificar Colunas Específicas**

```bash
# Entrar no Tinker (console Laravel)
php artisan tinker

# Verificar se users tem latitude/longitude
>>> Schema::hasColumn('users', 'latitude');
# Deve retornar: true

>>> Schema::hasColumn('users', 'longitude');
# Deve retornar: true

# Verificar se promocoes tem novos campos
>>> Schema::hasColumn('promocoes', 'desconto');
# Deve retornar: true

>>> Schema::hasColumn('promocoes', 'data_inicio');
# Deve retornar: true

>>> Schema::hasColumn('promocoes', 'validade');
# Deve retornar: true

>>> Schema::hasColumn('promocoes', 'status');
# Deve retornar: true

# Sair do Tinker
>>> exit
```

---

## Método 2: Via Logs do Render 📝

### Passo a Passo:

1. **Acesse o Dashboard do Render**
2. **Clique no serviço "tem-de-tudo"**
3. **Vá na aba "Logs"**
4. **Procure por linhas de migration**

### 🔍 O que procurar:

```
=== VERIFICANDO CONFIGURAÇÃO ===
DB_CONNECTION=pgsql
DB_HOST=dpg-d5dqrch5pdvs73dg81ig-a.virginia-postgres.render.com
DB_DATABASE=aplicativo_tem_de_tudo

Running migrations.
Migrating: 2026_02_18_000001_add_missing_fields_to_promocoes_table
Migrated:  2026_02_18_000001_add_missing_fields_to_promocoes_table (123.45ms)
Migrating: 2026_02_18_000002_add_geolocation_to_users
Migrated:  2026_02_18_000002_add_geolocation_to_users (89.12ms)
```

> ✅ Se ver "Migrated:" = Sucesso!  
> ⚠️ Se não aparecer = Pode não ter executado

---

## Método 3: Conectar Direto no PostgreSQL 🐘

### No seu computador local:

#### Windows (PowerShell):

```powershell
# Definir senha
$env:PGPASSWORD = "8lBmP4LBS1rKAgAYZRdEdBYfHlpHSX99"

# Conectar
psql -h dpg-d5dqrch5pdvs73dg81ig-a.virginia-postgres.render.com `
     -U temdetudo `
     -d aplicativo_tem_de_tudo `
     -p 5432
```

### Comandos PostgreSQL:

```sql
-- Listar todas as tabelas
\dt

-- Ver estrutura da tabela users
\d+ users

-- Ver estrutura da tabela promocoes
\d+ promocoes

-- Verificar se coluna existe
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'users' 
  AND column_name IN ('latitude', 'longitude');

-- Verificar colunas de promocoes
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'promocoes' 
  AND column_name IN ('desconto', 'data_inicio', 'validade', 'status');

-- Contar registros na tabela migrations
SELECT * FROM migrations ORDER BY id DESC LIMIT 5;

-- Sair
\q
```

---

## Método 4: Forçar Migration (Se Não Rodou) 🔧

### Via Shell do Render:

```bash
cd backend

# Rodar migrations manualmente
php artisan migrate --force

# Verificar status
php artisan migrate:status
```

### Ou Force um Redeploy:

1. No Dashboard do Render
2. Clique em **"Manual Deploy"**
3. Selecione **"Deploy latest commit"**
4. Acompanhe os logs durante o build

---

## ✅ Checklist de Verificação

### Tabelas Principais:
- [ ] `users` (deve ter `latitude` e `longitude`)
- [ ] `empresas`
- [ ] `inscricoes_empresas`
- [ ] `promocoes` (deve ter `desconto`, `data_inicio`, `validade`, `status`)
- [ ] `cupons`
- [ ] `cartoes_fidelidade`
- [ ] `bonus_adesao`
- [ ] `bonus_aniversario`
- [ ] `migrations`

### Novas Colunas (Migrations Recentes):
- [ ] `users.latitude` (DECIMAL 10,8)
- [ ] `users.longitude` (DECIMAL 11,8)
- [ ] `promocoes.desconto` (DECIMAL 5,2)
- [ ] `promocoes.data_inicio` (DATE)
- [ ] `promocoes.validade` (DATE)
- [ ] `promocoes.status` (ENUM: ativa/inativa/expirada)

---

## 🚨 Se as Tabelas NÃO Existirem

### Opção 1: Via Shell (Mais Rápido)

```bash
cd backend
php artisan migrate:fresh --force --seed
```

> ⚠️ **CUIDADO:** Isso APAGA todos os dados e recria as tabelas!

### Opção 2: Via Endpoint HTTP

Abra no navegador:
```
https://app-tem-de-tudo.onrender.com/api/setup-database
```

Isso vai executar as migrations via HTTP.

### Opção 3: Redeploy Manual

1. Dashboard do Render
2. Manual Deploy > Clear build cache & deploy
3. Aguarde o build completo
4. Verifique os logs

---

## 📊 Comandos Úteis do PostgreSQL

### Listar todas as tabelas:
```sql
SELECT tablename FROM pg_tables WHERE schemaname = 'public';
```

### Ver tamanho das tabelas:
```sql
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Contar registros de todas as tabelas:
```sql
SELECT 
    schemaname,
    tablename,
    (SELECT count(*) FROM pg_tables WHERE schemaname = 'public') AS total_tables
FROM pg_tables
WHERE schemaname = 'public';
```

---

## 🎯 Teste Rápido Via API

### Verificar se o sistema está funcionando:

```bash
# Status da API
curl https://app-tem-de-tudo.onrender.com/api/debug

# Resposta esperada:
{
  "status": "OK",
  "message": "API funcionando",
  "database": {
    "connection": "pgsql",
    "status": "connected"
  },
  "timestamp": "2026-02-18T08:00:00.000000Z",
  "environment": "production"
}
```

Se retornar **"status": "connected"** = Banco conectado! ✅

---

**Pronto!** Agora você pode verificar se todas as tabelas foram criadas corretamente no Render! 🚀
