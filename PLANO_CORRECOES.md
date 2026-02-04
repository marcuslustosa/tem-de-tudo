# 🔧 PLANO DE CORREÇÕES COMPLETAS

## ✅ JÁ CONCLUÍDO

1. ✅ Arquitetura Enterprise (SOLID, DTOs, Services, Repositories)
2. ✅ PWA Implementado (instalável, offline, notificações)
3. ✅ 6 usuários de teste criados (2 clientes, 2 empresas, 2 admins)
4. ✅ 6 empresas cadastradas com dados completos
5. ✅ 8 promoções ativas
6. ✅ Histórico de check-ins (7 total)
7. ✅ Cupons (2 total)

---

## 🚨 CORREÇÕES PRIORITÁRIAS

### 1. CARACTERES ESPECIAIS QUEBRADOS ❌
**Problema:** Encoding UTF-8 não aplicado corretamente
**Arquivos afetados:**
- app-promocoes.html (linha 10: "Promo��es")
- Potencialmente outros arquivos

**Solução:**
```html
<!-- Garantir em TODOS os HTML -->
<meta charset="UTF-8">
```

**Ação:** Executar script `fix_encoding_all.py` que já existe no projeto

---

### 2. PÁGINA EMPRESAS - MÚLTIPLOS PROBLEMAS ❌

#### 2.1 Links sem estilização
**Arquivo:** app-empresas.html
**Problema:** Links na parte inferior sem CSS

#### 2.2 Falta barra de navegação padrão
**Problema:** Bottom navigation existe mas pode estar inconsistente

#### 2.3 Visualização Grade vs Linha
**Solução:** Adicionar botões toggle:
```html
<div class="view-toggle">
    <button onclick="setView('grid')"><i class="fas fa-th"></i></button>
    <button onclick="setView('list')"><i class="fas fa-list"></i></button>
</div>
```

#### 2.4 Apenas 2 empresas (precisa de 6+)
**Status:** SQL já tem 6 empresas
**Ação:** Garantir que JavaScript carrega todas do banco

#### 2.5 Check-in não funciona
**Problema:** Função `fazerCheckin()` pode ter erro de API_BASE_URL
**Linha:** 509 do app-empresas.html
**Correção:** Verificar se `API_BASE_URL` está definido corretamente

---

### 3. PROMOÇÕES - PROBLEMAS ❌

#### 3.1 Dados fictícios faltando
**Status:** SQL tem 8 promoções
**Ação:** JavaScript precisa carregar do banco

#### 3.2 Redirecionamento 404
**Problema:** Link de desconto 20% vai para página inexistente
**Solução:** Criar `app-promocao-detalhes.html?id=X`

---

### 4. QR CODE - IDENTIDADE VISUAL ❌

**Arquivo:** app-qrcode.html
**Problema:** QR Code genérico sem logo
**Solução:** 
- Usar biblioteca qrcode.js
- Adicionar logo centralizado
- Aplicar cores roxas do sistema

---

### 5. PERFIL - PÁGINAS FALTANDO ❌

**Arquivos a criar/corrigir:**
- ✅ app-perfil-cliente.html (provavelmente existe)
- ❌ app-editar-perfil.html (CRIAR)
- ❌ app-alterar-senha.html (CRIAR)
- ❌ app-notificacoes.html (CRIAR)
- ❌ app-privacidade.html (CRIAR)
- ❌ app-ajuda.html (CRIAR com FAQ)

**Padrão FAQ:**
```markdown
## Como ganhar pontos?
Faça check-in nas empresas parceiras...

## Como resgatar cupons?
Acesse Promoções e...
```

---

### 6. BOTÃO SAIR NÃO FUNCIONA ❌

**Arquivos afetados:**
- dashboard-cliente.html
- painel-empresa.html
- admin-painel.html

**Problema:** Função logout não limpa token ou redireciona
**Solução:**
```javascript
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/index.html';
}
```

---

### 7. NOME DINÂMICO EM VEZ DE "CLIENTE" ❌

**Arquivos afetados:**
- dashboard-cliente.html (linha 260)
- dashboard-cliente-novo.html (linha 204)
- app-inicio.html (linha 393)
- painel-empresa.html (linha 45)

**Correção:**
```javascript
// Ao carregar a página
const user = JSON.parse(localStorage.getItem('user'));
document.getElementById('userName').textContent = user.nome;
```

---

### 8. ADMIN - PRIVILÉGIOS COMPLETOS ❌

**Arquivo:** admin-painel.html ou admin-dashboard.html

**Funcionalidades necessárias:**
- ✅ Dashboard com métricas gerais
- ❌ Listar todos usuários (tabela)
- ❌ Ativar/Desativar usuários (botão)
- ❌ Editar dados de usuários
- ❌ Criar novas empresas (formulário)
- ❌ Ver todos check-ins (tabela filtrada)
- ❌ Relatórios (exportar CSV/PDF)

---

### 9. HISTÓRICO DE PONTOS/PROMOÇÕES ❌

**Para cada perfil de usuário:**

**Cliente Maria:**
- Histórico: 4 check-ins (45 pontos)
- Cupons: 1 usado, saldo 45 pts

**Cliente João:**
- Histórico: 3 check-ins (35 pontos)
- Cupons: 1 disponível, saldo 35 pts

**Empresa Sabor & Arte:**
- Check-ins recebidos: 2
- Promoções: 2 ativas

**Ação:** Garantir que APIs retornam esses dados

---

### 10. EDITAR PERFIL / ALTERAR SENHA ❌

**Funcionalidades:**
- Form com campos atuais preenchidos
- Validação client-side
- API PUT /api/perfil
- API PUT /api/senha
- Feedback de sucesso/erro

---

### 11. BANCO DE DADOS - PERSISTÊNCIA ❌

**Problema relatado:** "cadastro salvou mas depois deu erro"

**Possíveis causas:**
1. Cache do navegador (LocalStorage não atualizado)
2. Transações não commitadas
3. Validação duplicada

**Solução:**
```php
// No Service, garantir transação
DB::beginTransaction();
try {
    $user = $repo->create($dto);
    DB::commit();
    return $user;
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

### 12. FAVICON COM LOGO ❌

**Arquivos a criar:**
- favicon.ico (16x16, 32x32, 48x48)
- apple-touch-icon.png (180x180)
- icon-192.png (192x192)
- icon-512.png (512x512)

**Localização:** `backend/public/`

**Solução temporária:** Criar favicon.svg com letra "T" roxa

---

## 📋 ORDEM DE EXECUÇÃO

### FASE 1 - CORREÇÕES CRÍTICAS (30 min)
1. Corrigir encoding UTF-8 (run fix_encoding_all.py)
2. Corrigir botão sair (logout)
3. Corrigir nome dinâmico
4. Corrigir check-in não funciona

### FASE 2 - PÁGINAS EMPRESAS E PROMOÇÕES (45 min)
5. Adicionar view toggle (grid/list)
6. Garantir 6 empresas aparecem
7. Criar página detalhes promoção
8. Preencher promoções com dados do SQL

### FASE 3 - PERFIL E CONFIGURAÇÕES (60 min)
9. Criar app-editar-perfil.html
10. Criar app-alterar-senha.html
11. Criar app-notificacoes.html
12. Criar app-privacidade.html
13. Criar app-ajuda.html (com FAQ completo)

### FASE 4 - ADMIN (45 min)
14. Criar tabela de usuários
15. Botões ativar/desativar
16. Formulário criar empresa
17. Relatórios básicos

### FASE 5 - QR CODE E FAVICON (30 min)
18. QR Code com identidade visual
19. Criar favicons

### FASE 6 - TESTES COMPLETOS (30 min)
20. Testar todos os 6 usuários
21. Verificar dados persistem
22. Testar todas as funcionalidades
23. Commit final

---

**TEMPO TOTAL ESTIMADO:** ~4 horas

**PRIORIDADE MÁXIMA:**
1. ✅ Encoding UTF-8
2. ✅ Botão sair
3. ✅ Nome dinâmico
4. ✅ Check-in funcionar
5. ✅ Empresas completas

---

**STATUS ATUAL:** Iniciando Fase 1
