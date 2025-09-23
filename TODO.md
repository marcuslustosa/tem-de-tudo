# TODO - Deploy Laravel no Render

## ✅ COMPLETADO
- [x] Análise da estrutura atual do projeto
- [x] Criação do plano de implementação
- [x] Mover frontend para backend/public/
- [x] Ajustar routes/web.php para fallback correto
- [x] Criar Procfile para Render
- [x] Ajustar Dockerfile para Apache
- [x] Verificar entrypoint.sh
- [x] Corrigir problema do Composer (composer.lock regenerado)

## 🚧 EM ANDAMENTO
- [ ] Testar build do Docker
- [ ] Testes de funcionalidade

## 📋 DETALHES DA IMPLEMENTAÇÃO

### 1. Mover Frontend
- ✅ Copiar todo conteúdo de `frontend/` para `backend/public/`
- ✅ Manter estrutura: css/, js/, assets/, img/, service-worker.js
- ✅ Preservar: index.html, login.html, register.html, etc.

### 2. Ajustar Rotas Laravel
- ✅ Modificar `backend/routes/web.php`
- ✅ Adicionar fallback que serve index.html para rotas não-API
- ✅ Usar regex para excluir rotas `/api`

### 3. Criar Procfile
- ✅ Criar `backend/Procfile`
- ✅ Conteúdo: `web: ./entrypoint.sh`

### 4. Ajustar Dockerfile
- ✅ Mudar de `php:8.2-cli` para `php:8.2-apache`
- ✅ Instalar extensões necessárias
- ✅ Configurar Apache corretamente
- ✅ Expor porta 80

### 5. Verificar Entrypoint
- ✅ Verificar compatibilidade com Apache
- ✅ Ajustar se necessário

### 6. Corrigir Composer
- ✅ Remover scripts problemáticos do composer.json
- ✅ Regenerar composer.lock
- ✅ Ajustar Dockerfile para usar --no-scripts

### 7. Testes
- [ ] Verificar se HTMLs carregam
- [ ] Testar rotas API
- [ ] Verificar assets CSS/JS
- [ ] Testar service worker
