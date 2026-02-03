# ✅ CHECKLIST - BANCO DE DADOS TEM DE TUDO

**Data:** 3 de fevereiro de 2026  
**Banco:** PostgreSQL no Render  
**Migrations:** 26 arquivos

---

## 📋 TABELAS QUE DEVEM EXISTIR (26 tabelas)

### **Core Tables (Essenciais)**
- [ ] `users` - Usuários do sistema (admin, cliente, empresa)
- [ ] `empresas` - Dados das empresas parceiras
- [ ] `pontos` - Pontos acumulados por usuário em cada empresa
- [ ] `personal_access_tokens` - Tokens Sanctum para autenticação
- [ ] `migrations` - Controle de migrations executadas

### **Sistema de QR Code**
- [ ] `qr_codes` - QR Codes gerados para empresas e clientes

### **Sistema de Fidelidade**
- [ ] `inscricoes_empresa` - Inscrições de clientes em empresas
- [ ] `bonus_adesao` - Bônus de primeira compra
- [ ] `cartao_fidelidades` - Cartões de fidelidade criados por empresas
- [ ] `cartoes_fidelidade_progresso` - Progresso dos clientes nos cartões

### **Sistema de Promoções**
- [ ] `promocoes` - Promoções criadas pelas empresas

### **Sistema de Avaliações**
- [ ] `avaliacoes` - Avaliações de clientes sobre empresas

### **Sistema de Bônus**
- [ ] `bonus_aniversario` - Bônus de aniversário
- [ ] `bonus_aniversarios` - Histórico de bônus

### **Sistema de Notificações**
- [ ] `notificacoes_push` - Notificações enviadas
- [ ] `lembretes_ausencia` - Lembretes para clientes inativos

### **Sistema de Descontos**
- [ ] `discount_levels` - Níveis de desconto progressivo

---

## 🔍 COMO VERIFICAR

### **1. No Render Dashboard**
1. Acesse: https://dashboard.render.com
2. Vá em PostgreSQL → `tem-de-tudo-db`
3. Clique em "Shell" ou "Connect"
4. Cole e execute: 
```sql
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
AND table_type = 'BASE TABLE'
ORDER BY table_name;
```

### **2. Via API (mais fácil)**
Acesse no navegador:
```
https://tem-de-tudo-9g7r.onrender.com/api/debug
```

Deve retornar algo como:
```json
{
  "status": "OK",
  "message": "API funcionando",
  "database": {
    "connection": "pgsql",
    "status": "connected"
  }
}
```

---

## 👤 CAMPOS OBRIGATÓRIOS NA TABELA `users`

### **Campos Base (migrations iniciais):**
- [ ] `id` - BIGSERIAL PRIMARY KEY
- [ ] `name` - VARCHAR(255)
- [ ] `email` - VARCHAR(255) UNIQUE
- [ ] `password` - VARCHAR(255)
- [ ] `remember_token` - VARCHAR(100)
- [ ] `created_at` - TIMESTAMP
- [ ] `updated_at` - TIMESTAMP
- [ ] `deleted_at` - TIMESTAMP (soft delete)

### **Campos Adicionados (migration add_fields_to_users_table):**
- [ ] `perfil` - VARCHAR (admin/cliente/empresa)
- [ ] `cpf_cnpj` - VARCHAR (CPF ou CNPJ)
- [ ] `telefone` - VARCHAR
- [ ] `data_nascimento` - DATE (para bônus aniversário)
- [ ] `fcm_token` - VARCHAR (Firebase push notifications)
- [ ] `status` - VARCHAR (ativo/inativo/bloqueado)
- [ ] `pontos_totais` - INTEGER (pontos acumulados)
- [ ] `nivel` - VARCHAR (Bronze/Prata/Ouro/Diamante)
- [ ] `empresa_id` - BIGINT (se for funcionário de empresa)

---

## 📊 VERIFICAR DADOS DE TESTE

Execute no banco:

```sql
-- Contar usuários por perfil
SELECT 
    perfil,
    COUNT(*) as total,
    STRING_AGG(email, ', ') as emails
FROM users
GROUP BY perfil
ORDER BY perfil;
```

**Resultado Esperado:**
```
perfil   | total | emails
---------|-------|------------------
admin    |   1   | admin@temdetudo.com
cliente  |   5   | joao@cliente.com, maria@cliente.com, ...
empresa  |   5   | contato@pizzariabella.com, ...
```

---

## 🚨 SE ALGO ESTIVER FALTANDO

### **Opção 1: Rodar migrations novamente**
```bash
cd backend
php artisan migrate:fresh --seed
```

### **Opção 2: Executar setup via API**
```
https://tem-de-tudo-9g7r.onrender.com/api/setup-database
```

### **Opção 3: Popular usuários de teste**
Execute o arquivo: `backend/database/seed_test_users.sql`

---

## ✅ TESTE RÁPIDO

1. **Tente fazer login:**
```
URL: https://tem-de-tudo-9g7r.onrender.com/entrar.html
Email: joao@cliente.com
Senha: senha123
```

2. **Se der erro "Email ou senha incorretos":**
   - ❌ Usuário não existe no banco
   - ❌ Senha está diferente
   - ❌ Campo `perfil` está NULL

3. **Se funcionar:**
   - ✅ Banco está 100% funcional
   - ✅ Tabela users está correta
   - ✅ Autenticação funcionando

---

## 📝 ESTRUTURA MÍNIMA NECESSÁRIA

Para o sistema funcionar, PRECISA TER no mínimo:

```sql
-- Tabela users COM perfil
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    perfil VARCHAR(50) NOT NULL DEFAULT 'cliente',  -- ⚠️ OBRIGATÓRIO
    cpf_cnpj VARCHAR(20),
    telefone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'ativo',
    pontos_totais INTEGER DEFAULT 0,
    nivel VARCHAR(20) DEFAULT 'Bronze',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Índices importantes
CREATE INDEX idx_users_perfil ON users(perfil);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_email ON users(email);
```

---

## 🔧 COMANDOS ÚTEIS

### **Verificar se migrations rodaram:**
```sql
SELECT migration, batch 
FROM migrations 
ORDER BY id DESC 
LIMIT 10;
```

### **Resetar banco (CUIDADO!):**
```sql
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
```

### **Popular com dados de teste:**
```sql
-- Cole o conteúdo de backend/database/seed_test_users.sql
```

---

## 🎯 PRÓXIMO PASSO

**SE O BANCO ESTIVER OK:**
1. ✅ Teste o login com: joao@cliente.com / senha123
2. ✅ Deve redirecionar para dashboard-cliente.html
3. ✅ Dados devem aparecer corretamente

**SE DER ERRO:**
1. ❌ Execute: `verificar_banco.sql` no PostgreSQL
2. ❌ Me mostre o resultado
3. ❌ Vamos corrigir juntos

---

**📅 Atualizado:** 3 de fevereiro de 2026  
**🗄️ Banco:** dpg-d6145d94tr6s73e18v90-a.oregon-postgres.render.com:5432
