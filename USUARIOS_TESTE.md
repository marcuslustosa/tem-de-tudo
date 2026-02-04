# 👥 USUÁRIOS DE TESTE - TEM DE TUDO

## 🔐 Senha Padrão
**Todos os usuários:** `senha123`

---

## 👤 CLIENTES (2 usuários)

### Cliente 1 - Maria Silva
- **Email:** `maria@email.com`
- **Senha:** `senha123`
- **Telefone:** 11 98765-4321
- **CPF:** 123.456.789-01
- **Pontos:** 45
- **Histórico:**
  - ✅ 4 check-ins realizados
  - 🎁 1 cupom usado
  - 🏪 Empresas visitadas: Sabor & Arte, Bella Napoli, Moda Urbana, Corpo Ativo

### Cliente 2 - João Santos
- **Email:** `joao@email.com`
- **Senha:** `senha123`
- **Telefone:** 11 97654-3210
- **CPF:** 987.654.321-09
- **Pontos:** 35
- **Histórico:**
  - ✅ 3 check-ins realizados
  - 🎁 1 cupom disponível
  - 🏪 Empresas visitadas: Sabor & Arte, Bella Napoli, Bella Vista

---

## 🏢 EMPRESAS (2 usuários + 4 sem login)

### Empresa 1 - Restaurante Sabor & Arte
- **Email:** `saborearte@email.com`
- **Senha:** `senha123`
- **CNPJ:** 12.345.678/0001-95
- **Telefone:** 11 3333-4444
- **Categoria:** Alimentação
- **Endereço:** Av. Paulista, 1000 - São Paulo, SP
- **Pontos por Check-in:** 10
- **Promoções Ativas:**
  - 🎉 20% de Desconto no Almoço (50 pontos)
  - 🍰 Sobremesa Grátis (30 pontos)

### Empresa 2 - Pizzaria Bella Napoli
- **Email:** `bellanapoli@email.com`
- **Senha:** `senha123`
- **CNPJ:** 98.765.432/0001-87
- **Telefone:** 11 2222-3333
- **Categoria:** Alimentação
- **Endereço:** Rua Augusta, 500 - São Paulo, SP
- **Pontos por Check-in:** 15
- **Promoções Ativas:**
  - 🍕 Pizza Grande R$ 29,90 (80 pontos)
  - 🎊 Compre 1 Leve 2 às Terças (100 pontos)

### Empresa 3 - Loja Moda Urbana
- **CNPJ:** 11.222.333/0001-44
- **Categoria:** Moda
- **Endereço:** Shopping Center, Loja 201
- **Pontos:** 8
- **Promoção:** 15% OFF em Toda Loja (60 pontos)

### Empresa 4 - Academia Corpo Ativo
- **CNPJ:** 22.333.444/0001-55
- **Categoria:** Saúde
- **Endereço:** Rua das Flores, 123
- **Pontos:** 12
- **Promoção:** 1 Mês Grátis na Matrícula (150 pontos)

### Empresa 5 - Salão Bella Vista
- **CNPJ:** 33.444.555/0001-66
- **Categoria:** Beleza
- **Endereço:** Av. Brasil, 789
- **Pontos:** 10
- **Promoção:** Escova Grátis (40 pontos)

### Empresa 6 - Café Aroma & Sabor
- **CNPJ:** 44.555.666/0001-77
- **Categoria:** Alimentação
- **Endereço:** Praça da República, 45
- **Pontos:** 5
- **Promoção:** Café + Bolo R$ 10 (20 pontos)

---

## 🔧 ADMINISTRADORES (2 usuários)

### Admin 1 - Admin Sistema
- **Email:** `admin@temdetudo.com`
- **Senha:** `senha123`
- **Telefone:** 11 9999-8888
- **Privilégios:** TOTAL
  - ✅ Gerenciar usuários (ativar/desativar/editar)
  - ✅ Gerenciar empresas (criar/editar/deletar)
  - ✅ Ver todas as transações
  - ✅ Gerar relatórios
  - ✅ Configurar sistema

### Admin 2 - Gerente Operacional
- **Email:** `gerente@temdetudo.com`
- **Senha:** `senha123`
- **Telefone:** 11 8888-7777
- **Privilégios:** TOTAL
  - ✅ Mesmo acesso do Admin Sistema

---

## 📊 ESTATÍSTICAS

### Total de Promoções Ativas: **8**
1. 20% Desconto Almoço (Sabor & Arte) - 50 pts
2. Sobremesa Grátis (Sabor & Arte) - 30 pts
3. Pizza R$ 29,90 (Bella Napoli) - 80 pts
4. Compre 1 Leve 2 (Bella Napoli) - 100 pts
5. 15% OFF Loja (Moda Urbana) - 60 pts
6. 1 Mês Grátis (Corpo Ativo) - 150 pts
7. Escova Grátis (Bella Vista) - 40 pts
8. Café + Bolo (Aroma & Sabor) - 20 pts

### Check-ins Totais: **7**
- Maria: 4 check-ins (45 pontos)
- João: 3 check-ins (35 pontos)

### Cupons: **2**
- Maria: 1 usado
- João: 1 disponível

---

## 🧪 COMO TESTAR

### 1. Popular Banco de Dados
```bash
cd backend
php artisan migrate:fresh
psql -h localhost -U postgres -d tem_de_tudo -f database/dados-usuarios-ficticios.sql
```

### 2. Login como Cliente
1. Acesse: `http://localhost:8000/entrar.html`
2. Email: `maria@email.com`
3. Senha: `senha123`
4. ✅ Ver 45 pontos, 4 check-ins, 1 cupom

### 3. Login como Empresa
1. Acesse: `http://localhost:8000/entrar.html`
2. Email: `saborearte@email.com`
3. Senha: `senha123`
4. ✅ Ver painel empresa, 2 promoções ativas

### 4. Login como Admin
1. Acesse: `http://localhost:8000/admin-login.html`
2. Email: `admin@temdetudo.com`
3. Senha: `senha123`
4. ✅ Ver dashboard admin completo

---

## ✅ FUNCIONALIDADES TESTÁVEIS

### Para Clientes:
- [x] Login/Cadastro
- [x] Ver saldo de pontos
- [x] Histórico de check-ins
- [x] Ver empresas parceiras
- [x] Fazer check-in
- [x] Ver promoções
- [x] Resgatar cupons
- [x] Editar perfil
- [x] Alterar senha

### Para Empresas:
- [x] Login
- [x] Ver dashboard
- [x] Criar promoções
- [x] Ver check-ins recebidos
- [x] Validar cupons
- [x] Editar dados da empresa

### Para Admins:
- [x] Login admin
- [x] Dashboard com métricas
- [x] Listar todos os usuários
- [x] Ativar/Desativar usuários
- [x] Criar novas empresas
- [x] Ver relatórios completos
- [x] Configurar sistema

---

## 🔄 PRÓXIMOS PASSOS

1. ✅ Executar SQL no banco PostgreSQL
2. ✅ Testar login de cada perfil
3. ✅ Verificar dados aparecem corretamente
4. ✅ Testar edição de perfil
5. ✅ Testar alteração de senha
6. ✅ Verificar logout funciona

---

**Última Atualização:** 03/02/2026
