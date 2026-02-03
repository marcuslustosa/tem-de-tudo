# 🎯 GUIA COMPLETO - Como Usar o Sistema Tem de Tudo

## 📱 ACESSO RÁPIDO AO SISTEMA

**URL de Produção:** https://tem-de-tudo-9g7r.onrender.com

---

## 🚀 PASSO 1: CRIAR SUA CONTA

### Opção A: Cadastrar como CLIENTE (Ganhar Pontos)

1. Acesse: https://tem-de-tudo-9g7r.onrender.com/cadastro.html
2. Escolha **"Cliente - Acumule pontos"**
3. Preencha os dados:
   - Nome completo
   - CPF (11 dígitos)
   - Email
   - Telefone
   - **Senha: mínimo 8 caracteres**
   - ✅ **Aceitar termos de uso** (obrigatório!)
4. Clique em **"Criar Conta"**
5. Será redirecionado automaticamente para o dashboard

### Opção B: Cadastrar como EMPRESA (Distribuir Pontos)

1. Acesse: https://tem-de-tudo-9g7r.onrender.com/cadastro.html
2. Escolha **"Empresa - Ofereça benefícios"**
3. Preencha os dados:
   - Nome da empresa
   - CNPJ (14 dígitos)
   - Email corporativo
   - Telefone
   - Endereço completo
   - **Senha: mínimo 8 caracteres**
   - ✅ **Aceitar termos**
4. Clique em **"Criar Conta"**

---

## 🎮 EXPERIÊNCIA DO CLIENTE (App de Fidelidade)

### Dashboard Principal
👉 `/dashboard-cliente.html`

**O que você vê:**
- 📊 Total de pontos acumulados
- 🎁 Promoções ativas disponíveis
- 📈 Pontos ganhos este mês
- 🔲 Seu QR Code pessoal
- 📜 Histórico recente de transações

**Navegação Inferior (Bottom Nav):**
```
🏠 Início | 🏪 Empresas | 🏷️ Promoções | 📱 Meu QR | 👤 Perfil
```

---

### 🏪 Buscar Empresas Parceiras
👉 `/app-empresas.html`

**Funcionalidades:**
- 🔍 Busca por nome ou endereço
- 🏷️ Filtros por categoria (Restaurante, Loja, Serviço, etc)
- ⭐ Avaliação de cada empresa
- 👥 Número de clientes
- 💰 Pontos ganhos por real gasto (ex: 10 pts/R$)

**Como usar:**
1. Digite o nome da empresa na busca
2. Ou filtre por categoria (🍔 Restaurantes, 🛍️ Lojas, etc)
3. Clique em "Ver Detalhes" para mais informações

---

### 🏷️ Ver Promoções
👉 `/app-promocoes.html`

**Filtros disponíveis:**
- **Todas** - Ver tudo
- **Posso Resgatar** - Você tem pontos suficientes
- **Expirando em Breve** - Últimos dias
- **Novas** - Adicionadas recentemente

**Como resgatar:**
1. Escolha uma promoção
2. Verifique se tem pontos suficientes
3. Clique em **"Resgatar"**
4. Confirme o resgate
5. ✅ Pronto! Cupom gerado

---

### 📱 Meu QR Code
👉 `/app-qrcode.html`

**Funcionalidades:**
- 📲 QR Code pessoal em tela cheia
- 💡 Aumentar brilho da tela
- 📤 Compartilhar QR Code
- 💾 Baixar como imagem PNG

**Como ganhar pontos:**
1. Abra `/app-qrcode.html`
2. Mostre o QR Code no caixa do estabelecimento
3. O funcionário escaneia
4. ✅ Pontos creditados automaticamente!

---

### 👤 Meu Perfil
👉 `/app-perfil-cliente.html`

**Informações exibidas:**
- 👤 Dados pessoais (nome, CPF, email, telefone)
- 📊 Estatísticas (pontos, resgates, check-ins)
- ⚙️ Configurações
- 📜 Histórico completo
- 🚪 Sair da conta

---

### 📊 Histórico Completo
👉 `/app-historico.html`

**Filtros:**
- Todas transações
- Apenas ganhos
- Apenas resgates
- Bônus especiais
- Pontos expirados

**Informações por transação:**
- 🏪 Empresa onde ganhou/usou pontos
- 💰 Quantidade de pontos
- 📅 Data e hora
- 📝 Descrição da transação

---

## 🏢 EXPERIÊNCIA DA EMPRESA

### Dashboard Empresa
👉 `/dashboard-empresa.html`

**Métricas exibidas:**
- 👥 Total de clientes cadastrados
- 💰 Pontos distribuídos
- 📈 Check-ins hoje
- ⭐ Avaliação média
- 👑 Top clientes
- 💬 Avaliações recentes

**Ações rápidas:**
- ➕ Nova Promoção
- 👥 Ver Clientes
- 📊 Relatórios

---

### 📸 Scanner de QR Code
👉 `/empresa-scanner.html`

**Como usar:**
1. Cliente abre `/app-qrcode.html`
2. Empresa abre `/empresa-scanner.html`
3. Aponte a câmera para o QR Code do cliente
4. Sistema lê automaticamente
5. Digite o valor da compra
6. ✅ Pontos creditados!

---

### 🎁 Criar Promoção
👉 `/empresa-nova-promocao.html`

**Campos:**
- 📝 Título da promoção
- 📄 Descrição
- 💰 Pontos necessários
- 📅 Data de início/fim
- 🏷️ Categoria (desconto, brinde, cashback)
- 🔢 Limite de resgates

**Exemplo:**
```
Título: "Desconto de R$20"
Descrição: "Resgate 200 pontos e ganhe R$20 OFF"
Pontos: 200
Validade: 30 dias
```

---

### 👥 Gerenciar Clientes
👉 `/empresa-clientes.html`

**Funcionalidades:**
- 📋 Lista de todos os clientes
- 🔍 Buscar por nome/CPF
- 💰 Ver pontos de cada cliente
- 📊 Histórico de compras
- 📧 Enviar notificação

---

## 👨‍💼 PAINEL ADMIN

### Dashboard Admin
👉 `/admin-dashboard.html`

**Controle total:**
- 🏢 Total de empresas cadastradas
- 👥 Total de clientes
- 💰 Pontos em circulação
- ➕ Criar novos usuários (Admin/Empresa/Cliente)

**Ações:**
- Aprovar/reprovar empresas
- Ver transações do sistema
- Gerar relatórios gerais
- Gerenciar configurações globais

---

## 🎯 FLUXO COMPLETO DE USO

### Cenário 1: Cliente Ganhando Pontos

```
1. Cliente se cadastra → /cadastro.html
2. Vai a uma loja parceira
3. Faz compra de R$ 50
4. Mostra QR Code → /app-qrcode.html
5. Loja escaneia → /empresa-scanner.html
6. Sistema calcula: R$ 50 × 10 pts = 500 pontos
7. ✅ 500 pontos creditados!
8. Cliente vê no histórico → /app-historico.html
```

### Cenário 2: Cliente Resgatando Promoção

```
1. Cliente acessa promoções → /app-promocoes.html
2. Vê: "Pizza Grátis - 300 pontos"
3. Tem 500 pontos disponíveis
4. Clica em "Resgatar"
5. ✅ Cupom gerado
6. Apresenta na empresa
7. -300 pontos debitados
8. Saldo atual: 200 pontos
```

### Cenário 3: Empresa Criando Promoção

```
1. Empresa faz login → /entrar.html
2. Acessa dashboard → /dashboard-empresa.html
3. Clica "Nova Promoção"
4. Preenche dados:
   - "Combo Mega - R$30 OFF"
   - 500 pontos necessários
   - Válido por 60 dias
5. Salva promoção
6. ✅ Aparece para todos os clientes em /app-promocoes.html
```

---

## 🔐 CREDENCIAIS DE TESTE

### Cliente Teste
```
Email: joao@cliente.com
Senha: senha123
```

### Empresa Teste
```
Email: restaurante@exemplo.com
Senha: senha123
```

### Admin Teste
```
Email: admin@temdetudo.com
Senha: senha123
```

---

## 📱 PÁGINAS PRINCIPAIS

### CLIENTE
| Página | URL | Descrição |
|--------|-----|-----------|
| Início | `/dashboard-cliente.html` | Dashboard principal |
| Empresas | `/app-empresas.html` | Buscar parceiros |
| Promoções | `/app-promocoes.html` | Ver e resgatar ofertas |
| Meu QR | `/app-qrcode.html` | QR Code pessoal |
| Perfil | `/app-perfil-cliente.html` | Dados e configurações |
| Histórico | `/app-historico.html` | Transações completas |

### EMPRESA
| Página | URL | Descrição |
|--------|-----|-----------|
| Dashboard | `/dashboard-empresa.html` | Painel de controle |
| Scanner | `/empresa-scanner.html` | Ler QR dos clientes |
| Promoções | `/empresa-promocoes.html` | Gerenciar ofertas |
| Clientes | `/empresa-clientes.html` | Ver cadastrados |
| Relatórios | `/empresa-relatorios.html` | Estatísticas |

### ADMIN
| Página | URL | Descrição |
|--------|-----|-----------|
| Dashboard | `/admin-dashboard.html` | Visão geral |
| Empresas | `/admin-empresas.html` | Gerenciar parceiros |
| Clientes | `/admin-clientes.html` | Ver usuários |
| Relatórios | `/admin-relatorios.html` | Analytics |

---

## 🎨 FUNCIONALIDADES DO APP

### ✅ Implementado
- ✅ Cadastro de Cliente/Empresa
- ✅ Login com autenticação
- ✅ Dashboard responsivo
- ✅ Bottom navigation (tipo Instagram)
- ✅ Busca de empresas com filtros
- ✅ Sistema de promoções
- ✅ QR Code pessoal
- ✅ Histórico de transações
- ✅ Scanner de QR Code
- ✅ Perfil do usuário
- ✅ Tema escuro moderno

### 🚧 Em Desenvolvimento
- Notificações push
- Geolocalização (empresas próximas)
- Chat com suporte
- Programa de indicação
- Gamificação (badges, níveis)

---

## 🐛 SOLUÇÃO DE PROBLEMAS

### Erro: "Email ou senha incorretos"
**Causa:** Usuário não existe no banco
**Solução:** Cadastre-se em `/cadastro.html`

### Erro: "The terms field is required"
**Causa:** Não marcou checkbox de aceitar termos
**Solução:** ✅ Marque a caixa antes de cadastrar

### Erro: "Password must be at least 8 characters"
**Causa:** Senha muito curta
**Solução:** Use mínimo 8 caracteres (ex: senha123)

### Página não carrega
**Causa:** Token expirado
**Solução:** Faça logout e login novamente

---

## 🚀 COMO INICIAR AGORA

```bash
# 1. Acesse o site
https://tem-de-tudo-9g7r.onrender.com

# 2. Cadastre-se
/cadastro.html

# 3. Explore o app!
- Busque empresas
- Veja promoções
- Gere seu QR Code
- Acumule pontos
```

---

## 📞 SUPORTE

- 📧 Email: suporte@temdetudo.com
- 💬 Chat: Dentro do app
- 📱 WhatsApp: (11) 99999-9999
- 🌐 Site: https://temdetudo.com

---

**Versão:** 2.0  
**Última atualização:** 03/02/2026  
**Status:** ✅ Sistema 100% funcional em produção
