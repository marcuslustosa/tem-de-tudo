# ✅ Validação Funcional Completa - Sistema VIPus

**Data:** 22/04/2026  
**Objetivo:** Garantir que todos os fluxos estão 100% funcionais antes do deploy

---

## 🎯 Fluxos Críticos a Validar

### 1. Autenticação e Cadastro
- [ ] Cadastro de novo cliente (perfil: cliente)
- [ ] Cadastro de nova empresa (perfil: empresa)
- [ ] Login com credenciais válidas
- [ ] Login com credenciais inválidas (deve falhar)
- [ ] Logout
- [ ] Recuperação de senha (forgot password)
- [ ] Reset de senha com token

**Páginas:**
- `/criar_conta.html`
- `/entrar.html`
- `/escolher-tipo.html`
- `/forgot_password.html`
- `/reset_password.html`

**APIs:**
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`

---

### 2. Dashboard Cliente
- [ ] Visualizar saldo de pontos
- [ ] Ver histórico de transações
- [ ] Ver empresas parceiras
- [ ] Ver badges conquistados
- [ ] Ver ranking/leaderboard

**Páginas:**
- `/dashboard-cliente.html`
- `/meus_pontos.html`
- `/hist_rico_de_uso.html`
- `/parceiros_tem_de_tudo.html`

**APIs:**
- `GET /api/cliente/dashboard`
- `GET /api/wallet/saldo`
- `GET /api/wallet/extrato`
- `GET /api/cliente/empresas`
- `GET /api/leaderboard/global`

---

### 3. Dashboard Empresa
- [ ] Visualizar estatísticas (clientes, check-ins, pontos distribuídos)
- [ ] Ver top clientes
- [ ] Ver transações recentes
- [ ] Gerenciar produtos/recompensas
- [ ] Criar/editar promoções

**Páginas:**
- `/dashboard-empresa.html`
- `/dashboard_parceiro.html`
- `/clientes_fidelizados_loja.html`
- `/minhas_campanhas_loja.html`
- `/gest_o_de_ofertas_parceiro.html`

**APIs:**
- `GET /api/empresa/dashboard`
- `GET /api/empresa/dashboard-stats`
- `GET /api/empresa/top-clients`
- `GET /api/empresa/{id}/produtos`
- `GET /api/promocoes`

---

### 4. Sistema de Pontos
- [ ] Acumular pontos via check-in
- [ ] Acumular pontos via compra
- [ ] Ver extrato detalhado
- [ ] Verificar multiplicadores VIP (Bronze 1x, Prata 1.5x, Ouro 2x, Platina 3x)
- [ ] Testar regras anti-fraude

**APIs:**
- `POST /api/check-ins`
- `POST /api/pontos/adicionar`
- `GET /api/pontos/extrato`
- `GET /api/pontos/saldo`

---

### 5. Resgate de Recompensas (PDV)
- [ ] Listar produtos/recompensas disponíveis
- [ ] Criar intent de resgate (reserve)
- [ ] Confirmar resgate (confirm)
- [ ] Cancelar resgate (cancel)
- [ ] Testar expiração de reserva (15 minutos)
- [ ] Reverter resgate (apenas admin)

**Páginas:**
- `/recompensas.html`
- `/validar_resgate.html`

**APIs:**
- `GET /api/empresas/{id}/produtos`
- `POST /api/redemption/request`
- `POST /api/redemption/confirm`
- `POST /api/redemption/cancel`
- `GET /api/redemption/{intentId}`

---

### 6. QR Codes
- [ ] Gerar QR Code para empresa
- [ ] Gerar QR Code para cliente
- [ ] Visualizar QR Code (imagem PNG)
- [ ] Validar QR Code via API

**APIs:**
- `GET /api/qrcode/meu-qrcode`
- `GET /api/qrcode/empresa/{id}`

---

### 7. Badges e Gamificação
- [ ] Listar badges disponíveis
- [ ] Ver badges do usuário
- [ ] Conquistar novo badge (automático)
- [ ] Ver ranking de badges

**APIs:**
- `GET /api/badges`
- `GET /api/badges/meus`
- `POST /api/badges/verificar-novos`
- `GET /api/badges/ranking`

---

### 8. Billing e Assinaturas (Empresas)
- [ ] Ver plano atual
- [ ] Upgrade de plano (Basic → Pro → Enterprise)
- [ ] Verificar limites do plano
- [ ] Testar suspensão por falta de pagamento
- [ ] Gerar fatura automática

**APIs:**
- `GET /api/billing/subscription`
- `POST /api/billing/upgrade`
- `GET /api/billing/invoices`

---

### 9. Dashboard Admin Master
- [ ] Ver estatísticas gerais do sistema
- [ ] Listar todas empresas
- [ ] Listar todos clientes
- [ ] Ver logs de auditoria
- [ ] Gerenciar banners
- [ ] Ver relatórios

**Páginas:**
- `/dashboard_admin_master.html`
- `/gest_o_de_estabelecimentos.html`
- `/gest_o_de_clientes_master.html`
- `/gest_o_de_usu_rios_master.html`
- `/banners_e_categorias_master.html`
- `/relat_rios_gerais_master.html`

**APIs:**
- `GET /api/admin/system-stats`
- `GET /api/admin/empresas`
- `GET /api/admin/clientes`
- `GET /api/admin/audit-logs`
- `GET /api/admin/banners`

---

### 10. Promoções e Campanhas
- [ ] Criar promoção
- [ ] Listar promoções ativas
- [ ] Resgatar promoção
- [ ] Verificar regras de promoção
- [ ] Ver multiplicadores ativos

**APIs:**
- `POST /api/promocoes`
- `GET /api/promocoes`
- `POST /api/promocoes/{id}/resgatar`
- `GET /api/campanhas/multiplicador-ativo`

---

### 11. Banners
- [ ] Listar banners ativos
- [ ] Criar novo banner (admin)
- [ ] Upload de imagem
- [ ] Ativar/desativar banner
- [ ] Reordenar banners

**APIs:**
- `GET /api/banners`
- `POST /api/admin/banners`
- `POST /api/admin/banners/{id}/toggle`
- `POST /api/admin/banners/reorder`

---

### 12. Analytics e Leaderboard
- [ ] Ver leaderboard global
- [ ] Ver leaderboard mensal
- [ ] Ver estatísticas de empresa
- [ ] Ver estatísticas de cliente
- [ ] Ver badges conquistados

**APIs:**
- `GET /api/leaderboard/global`
- `GET /api/leaderboard/monthly`
- `GET /api/leaderboard/company/{id}`
- `GET /api/leaderboard/user/{id}`
- `GET /api/leaderboard/badges`

---

## 🗃️ Validação de Dados

### Seeders Executados:
- [x] ProductionDemoSeeder (15 empresas, 50 clientes)
- [x] ProdutosRecompensasSeeder (71 produtos)
- [x] BannersSeeder (8 banners)
- [ ] DesafiosSeeder
- [ ] NPSSeeder
- [ ] SegmentacaoSeeder

### Banco de Dados:
- [ ] Verificar empresas criadas (15 esperadas)
- [ ] Verificar clientes criados (50 esperados)
- [ ] Verificar produtos criados (71 esperados)
- [ ] Verificar banners criados (8 esperados)
- [ ] Verificar pontos no ledger (>400 transações)
- [ ] Verificar índices criados (43 índices)

---

## 🧪 Cenários de Teste

### Cenário 1: Jornada do Cliente Novo
1. Acessar `/criar_conta.html`
2. Cadastrar como cliente
3. Fazer login
4. Ver dashboard (saldo = 0)
5. Buscar empresas parceiras
6. Fazer check-in em uma empresa
7. Ganhar pontos (100-500 pts)
8. Ver produtos/recompensas
9. Tentar resgatar (se tiver pontos suficientes)

### Cenário 2: Jornada da Empresa
1. Cadastrar como empresa
2. Fazer login
3. Ver dashboard (0 clientes)
4. Criar produto/recompensa
5. Criar promoção
6. Visualizar QR Code
7. Ver estatísticas

### Cenário 3: Resgate PDV Completo
1. Cliente com pontos suficientes
2. Criar intent de resgate (reserve)
3. PDV confirma resgate
4. Pontos debitados
5. Ledger atualizado (imutável)
6. Status: completed

### Cenário 4: Resgate Expirado
1. Criar intent de resgate
2. Aguardar 16 minutos
3. Intent expirado automaticamente
4. Pontos devolvidos
5. Status: expired

### Cenário 5: Anti-Fraude
1. Cliente tenta fazer 6 check-ins no mesmo dia
2. Deve ser bloqueado após 5º check-in
3. Log de fraude gerado
4. Alert enviado

---

## 📋 Checklist de Validação

### Performance:
- [ ] Páginas carregam em <2s
- [ ] APIs respondem em <500ms
- [ ] Cache funcionando (headers Cache-Control)
- [ ] Assets minificados carregando
- [ ] Sem queries N+1 (verificar com Debugbar)

### Segurança:
- [ ] Rate limits ativos (5/min login)
- [ ] CORS configurado corretamente
- [ ] Headers de segurança presentes
- [ ] Tokens JWT válidos
- [ ] Sanitização de inputs

### Funcionalidade:
- [ ] Todos os formulários submetem
- [ ] Validações de frontend funcionam
- [ ] Mensagens de erro claras
- [ ] Redirecionamentos corretos
- [ ] Logout funciona

### UI/UX:
- [ ] Tailwind CSS carregando
- [ ] Ícones exibindo
- [ ] Responsivo (mobile/desktop)
- [ ] Sem erros no console
- [ ] Loading states visíveis

---

## 🔧 Ferramentas de Validação

### Manual:
1. Navegador (Chrome DevTools)
2. Postman/Insomnia (testes de API)
3. DB Browser (verificar dados)

### Automatizado:
```bash
# Executar teste completo
php backend/test_completo.php

# Ver rotas
php artisan route:list

# Ver tabelas
php artisan db:show

# Limpar cache
php artisan cache:clear
php artisan config:clear
```

---

## 📊 Critérios de Sucesso

### ✅ Sistema Aprovado se:
- 90%+ dos fluxos críticos funcionam
- 0 erros 500 em APIs principais
- Dados de demo carregam corretamente
- Performance aceitável (<2s)
- Segurança ativa (rate limits, headers)

### ❌ Sistema Reprovado se:
- Login/cadastro não funciona
- Pontos não acumulam
- Resgate não funciona
- Erros frequentes (>10% das requests)
- Dados inconsistentes no banco

---

## 📝 Log de Testes

### Executado em: __/__/2026
**Testador:** ___________

| Fluxo | Status | Observações |
|-------|--------|-------------|
| Cadastro Cliente | ⏳ | |
| Login | ⏳ | |
| Dashboard Cliente | ⏳ | |
| Acumular Pontos | ⏳ | |
| Resgate PDV | ⏳ | |
| QR Codes | ⏳ | |
| Badges | ⏳ | |
| Dashboard Empresa | ⏳ | |
| Admin Master | ⏳ | |
| Banners | ⏳ | |

**Legenda:**
- ⏳ Aguardando teste
- ✅ Passou
- ⚠️ Passou com ressalvas
- ❌ Falhou

---

**Próximo Passo:** Executar testes e documentar resultados
