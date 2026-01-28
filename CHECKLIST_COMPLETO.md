# 📋 CHECKLIST COMPLETO - TODAS AS FUNCIONALIDADES

**Data:** 28/01/2026  
**Sistema:** Tem de Tudo - Fidelização Digital

---

## 🎯 TESTES PRIORITÁRIOS (FAZER PRIMEIRO)

### 1️⃣ AUTENTICAÇÃO BÁSICA

#### ✅ Login Cliente
- [ ] Acessar `/entrar.html`
- [ ] Login com: `cliente@teste.com` / `123456`
- [ ] Verificar token salvo em `localStorage.token`
- [ ] Redireciona para `/app-inicio.html`
- [ ] Console sem erros
- [ ] Mensagem de sucesso aparece

#### ✅ Login Admin
- [ ] Acessar `/admin-login.html`
- [ ] Login com: `admin@temdetudo.com` / `admin123`
- [ ] Verificar token salvo em `localStorage.admin_token`
- [ ] Redireciona para `/admin.html`
- [ ] Console sem erros
- [ ] Mensagem de sucesso aparece

#### ✅ Login Empresa
- [ ] Acessar `/entrar.html`
- [ ] Login com: `empresa@teste.com` / `123456`
- [ ] Redireciona para `/empresa.html`
- [ ] Token salvo corretamente
- [ ] Console sem erros

#### ✅ Cadastro Cliente
- [ ] Acessar `/cadastro.html`
- [ ] Preencher todos os campos obrigatórios
- [ ] Aceitar termos de uso
- [ ] Submit funciona
- [ ] Usuário criado no banco
- [ ] Redireciona após sucesso
- [ ] Validações funcionam

---

## 🔐 TESTES DE SEGURANÇA

### 2️⃣ VALIDAÇÕES DE FORMULÁRIO

#### Login
- [ ] Email vazio → Mostra erro
- [ ] Senha vazia → Mostra erro
- [ ] Email inválido → Mostra erro
- [ ] Credenciais erradas → Mensagem "Credenciais inválidas"
- [ ] Rate limiting funciona (5 tentativas)

#### Cadastro
- [ ] Nome vazio → Erro
- [ ] Email inválido → Erro
- [ ] Telefone inválido → Erro
- [ ] Senha < 6 caracteres → Erro
- [ ] Termos não aceitos → Bloqueia submit
- [ ] Email duplicado → Erro do backend

---

## 📱 TESTES DO CLIENTE (APP)

### 3️⃣ DASHBOARD CLIENTE (`/app-inicio.html`)
- [ ] Página carrega sem erros
- [ ] Mostra nome do usuário
- [ ] Exibe saldo de pontos
- [ ] Mostra nível atual
- [ ] Botões principais funcionam:
  - [ ] Buscar estabelecimentos
  - [ ] Meu QR Code
  - [ ] Histórico de pontos
  - [ ] Promoções ativas

### 4️⃣ QR CODE (`/app-meu-qrcode.html`)
- [ ] Gera QR Code único do usuário
- [ ] QR Code é escaneável
- [ ] Mostra ID do usuário
- [ ] Botão compartilhar funciona
- [ ] Botão salvar funciona

### 5️⃣ BUSCAR ESTABELECIMENTOS (`/app-buscar.html`)
- [ ] Lista todas as empresas cadastradas
- [ ] Mostra 8 empresas com imagens:
  - [ ] Restaurante Sabor & Arte (imagem carrega)
  - [ ] Academia Corpo Forte (imagem carrega)
  - [ ] Cafeteria Aroma Premium (imagem carrega)
  - [ ] Pet Shop Amigo Fiel (imagem carrega)
  - [ ] Salão Beleza Total (imagem carrega)
  - [ ] Mercado Bom Preço (imagem carrega)
  - [ ] Farmácia Saúde Mais (imagem carrega)
  - [ ] Padaria Pão Quentinho (imagem carrega)
- [ ] Filtro por categoria funciona
- [ ] Busca por nome funciona
- [ ] Click em empresa abre detalhes

### 6️⃣ DETALHES DA EMPRESA (`/app-estabelecimento.html`)
- [ ] Mostra logo da empresa
- [ ] Exibe informações completas
- [ ] Mostra endereço e telefone
- [ ] Botão "Como chegar" funciona
- [ ] Botão "Ligar" funciona
- [ ] Mostra promoções ativas
- [ ] Mostra avaliações

### 7️⃣ PERFIL CLIENTE (`/app-perfil.html`)
- [ ] Carrega dados do usuário
- [ ] Permite editar:
  - [ ] Nome
  - [ ] Email
  - [ ] Telefone
  - [ ] Foto de perfil
- [ ] Botão salvar funciona
- [ ] Validações funcionam
- [ ] Atualiza dados no backend

### 8️⃣ HISTÓRICO DE PONTOS (`/meus-pontos.html`)
- [ ] Lista todas as transações
- [ ] Mostra data e hora
- [ ] Exibe pontos ganhos/gastos
- [ ] Mostra empresa relacionada
- [ ] Filtro por período funciona
- [ ] Paginação funciona

### 9️⃣ CUPONS (`/cupons.html`)
- [ ] Lista cupons ativos
- [ ] Lista cupons expirados
- [ ] Lista cupons usados
- [ ] Mostra detalhes do cupom
- [ ] Botão "Usar cupom" funciona
- [ ] QR Code do cupom é gerado

### 🔟 SCANNER (`/app-scanner.html`)
- [ ] Camera abre corretamente
- [ ] Reconhece QR Code
- [ ] Valida cupom/pontos
- [ ] Mostra feedback visual
- [ ] Funciona em mobile

---

## 🏢 TESTES DA EMPRESA

### 1️⃣1️⃣ DASHBOARD EMPRESA (`/empresa-dashboard.html`)
- [ ] Mostra estatísticas:
  - [ ] Total de clientes
  - [ ] Pontos distribuídos hoje
  - [ ] Cupons resgatados
  - [ ] Avaliação média
- [ ] Gráficos carregam
- [ ] Período de filtro funciona

### 1️⃣2️⃣ CLIENTES DA EMPRESA (`/empresa-clientes.html`)
- [ ] Lista todos os clientes
- [ ] Mostra pontos de cada cliente
- [ ] Busca por nome funciona
- [ ] Filtro por status funciona
- [ ] Exportar lista funciona

### 1️⃣3️⃣ SCANNER EMPRESA (`/empresa-scanner.html`)
- [ ] Camera funciona
- [ ] Lê QR Code do cliente
- [ ] Adiciona pontos corretamente
- [ ] Mostra confirmação visual
- [ ] Histórico de scans aparece

### 1️⃣4️⃣ PROMOÇÕES (`/empresa-promocoes.html`)
- [ ] Lista promoções ativas
- [ ] Lista promoções expiradas
- [ ] Botão criar promoção funciona
- [ ] Editar promoção funciona
- [ ] Excluir promoção funciona
- [ ] Validações corretas

### 1️⃣5️⃣ NOVA PROMOÇÃO (`/empresa-nova-promocao.html`)
- [ ] Formulário completo
- [ ] Upload de imagem funciona
- [ ] Define período validade
- [ ] Define pontos necessários
- [ ] Salva corretamente
- [ ] Validações funcionam

### 1️⃣6️⃣ RELATÓRIOS EMPRESA (`/empresa-relatorios.html`)
- [ ] Relatório de pontos
- [ ] Relatório de cupons
- [ ] Relatório financeiro
- [ ] Exportar PDF funciona
- [ ] Exportar Excel funciona
- [ ] Filtros por data funcionam

### 1️⃣7️⃣ CONFIGURAÇÕES EMPRESA (`/empresa-configuracoes.html`)
- [ ] Editar dados da empresa
- [ ] Upload de logo funciona
- [ ] Alterar horário funcionamento
- [ ] Definir % de cashback
- [ ] Salvar alterações funciona

---

## 👨‍💼 TESTES DO ADMIN

### 1️⃣8️⃣ DASHBOARD ADMIN (`/admin-dashboard.html`)
- [ ] Mostra estatísticas gerais:
  - [ ] Total de usuários
  - [ ] Total de empresas
  - [ ] Total de transações
  - [ ] Receita total
- [ ] Gráficos carregam
- [ ] Cards informativos aparecem

### 1️⃣9️⃣ GESTÃO DE USUÁRIOS (`/admin.html`)
- [ ] Lista todos os usuários
- [ ] Filtro por perfil funciona:
  - [ ] Clientes
  - [ ] Empresas
  - [ ] Admins
- [ ] Busca por nome/email funciona
- [ ] Ações disponíveis:
  - [ ] Ver detalhes
  - [ ] Editar
  - [ ] Bloquear/Desbloquear
  - [ ] Excluir

### 2️⃣0️⃣ CRIAR USUÁRIO (`/admin-create-user.html`)
- [ ] Formulário completo
- [ ] Seleciona tipo de perfil
- [ ] Validações funcionam
- [ ] Cria usuário no banco
- [ ] Envia email de boas-vindas
- [ ] Redireciona após sucesso

### 2️⃣1️⃣ RELATÓRIOS ADMIN (`/admin-relatorios.html`)
- [ ] Relatório de usuários
- [ ] Relatório de transações
- [ ] Relatório financeiro
- [ ] Relatório de empresas
- [ ] Exportações funcionam
- [ ] Filtros avançados funcionam

### 2️⃣2️⃣ CONFIGURAÇÕES SISTEMA (`/admin-configuracoes.html`)
- [ ] Configurações gerais
- [ ] Parâmetros de pontos
- [ ] Configuração de emails
- [ ] Integração Mercado Pago
- [ ] Backup automático
- [ ] Salvar funciona

---

## 🎨 TESTES VISUAIS

### 2️⃣3️⃣ CSS UNIFICADO
- [ ] Todas as páginas com mesmo tema
- [ ] Gradientes consistentes
- [ ] Botões padronizados
- [ ] Inputs padronizados
- [ ] Cards com mesmo estilo
- [ ] Cores corretas:
  - [ ] Primary: #667eea
  - [ ] Secondary: #764ba2
  - [ ] Accent: #f093fb

### 2️⃣4️⃣ RESPONSIVIDADE
- [ ] Desktop (1920x1080) OK
- [ ] Tablet (768x1024) OK
- [ ] Mobile (375x667) OK
- [ ] Mobile landscape OK
- [ ] Menu mobile funciona
- [ ] Todos os botões acessíveis

### 2️⃣5️⃣ LOADING STATES
- [ ] Spinners aparecem durante requests
- [ ] Skeleton screens funcionam
- [ ] Botões desabilitam durante submit
- [ ] Feedback visual em todas as ações

---

## 🔄 TESTES DE FLUXO

### 2️⃣6️⃣ FLUXO COMPLETO CLIENTE
1. [ ] Cadastro → Login → Dashboard
2. [ ] Ver empresas → Selecionar → Ver detalhes
3. [ ] Ganhar pontos (via scanner)
4. [ ] Ver histórico de pontos
5. [ ] Resgatar cupom
6. [ ] Usar cupom em empresa
7. [ ] Logout

### 2️⃣7️⃣ FLUXO COMPLETO EMPRESA
1. [ ] Login empresa
2. [ ] Ver dashboard com stats
3. [ ] Criar nova promoção
4. [ ] Scanner QR Code cliente
5. [ ] Adicionar pontos ao cliente
6. [ ] Ver relatório do dia
7. [ ] Logout

### 2️⃣8️⃣ FLUXO COMPLETO ADMIN
1. [ ] Login admin
2. [ ] Ver dashboard geral
3. [ ] Criar novo usuário
4. [ ] Criar nova empresa
5. [ ] Ver relatórios
6. [ ] Configurar sistema
7. [ ] Logout

---

## 🌐 TESTES DE INTEGRAÇÃO

### 2️⃣9️⃣ API ENDPOINTS
- [ ] `/api/auth/login` → 200 OK
- [ ] `/api/auth/register` → 201 Created
- [ ] `/api/admin/login` → 200 OK
- [ ] `/api/cliente/empresas` → 200 OK (lista 8 empresas)
- [ ] `/api/cliente/historico-pontos` → 200 OK
- [ ] `/api/debug` → 200 OK (status: OK)

### 3️⃣0️⃣ DADOS FICTÍCIOS
- [ ] 1 admin existe: `admin@temdetudo.com`
- [ ] 1 cliente existe: `cliente@teste.com`
- [ ] 1 empresa existe: `empresa@teste.com`
- [ ] 50 clientes existem: `cliente1-50@email.com`
- [ ] 8 empresas com fotos aparecem

---

## 🚨 TESTES DE ERRO

### 3️⃣1️⃣ TRATAMENTO DE ERROS
- [ ] 401 Unauthorized → Redireciona para login
- [ ] 403 Forbidden → Mensagem de permissão
- [ ] 404 Not Found → Página de erro
- [ ] 500 Server Error → Mensagem amigável
- [ ] Sem internet → Mensagem offline
- [ ] Timeout → Retry automático

### 3️⃣2️⃣ VALIDAÇÕES
- [ ] XSS protegido
- [ ] SQL Injection protegido
- [ ] CSRF tokens funcionam
- [ ] Rate limiting ativo
- [ ] Senhas hasheadas
- [ ] Tokens expiram

---

## 📊 RESUMO FINAL

### TOTAL DE TESTES: **150+**

**Categorias:**
- 🔐 Autenticação: 15 testes
- 📱 Cliente: 35 testes
- 🏢 Empresa: 25 testes
- 👨‍💼 Admin: 20 testes
- 🎨 Visual: 15 testes
- 🔄 Fluxos: 15 testes
- 🌐 Integração: 10 testes
- 🚨 Erros: 15 testes

---

## ✅ COMO USAR ESTE CHECKLIST

1. **Começar pelos testes prioritários** (1-10)
2. **Marcar cada item** conforme for testando
3. **Anotar problemas** encontrados
4. **Reportar bugs** críticos imediatamente
5. **Documentar** comportamentos inesperados

---

## 📝 PRÓXIMOS PASSOS

1. ⏳ **Aguardar deploy** (5-7 min)
2. 🧪 **Executar testes prioritários**
3. 🐛 **Corrigir bugs encontrados**
4. ✅ **Validar funcionalidades**
5. 🚀 **Deploy final**

---

**Status:** 🟡 Pronto para testes  
**Atualizado:** 28/01/2026 - {{ hora atual }}
