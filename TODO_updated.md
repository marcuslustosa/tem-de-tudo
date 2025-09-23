# TODO - Deploy Laravel no Render

## ✅ COMPLETADO
- [x] Análise da estrutura atual do projeto
- [x] Criação do plano de implementação
- [x] Mover frontend para backend/public/
- [x] Ajustar routes/web.php para fallback correto
- [x] Criar Procfile para Render
- [x] Ajustar Dockerfile para Apache
- [x] Verificar entrypoint.sh
- [x] Criar usuários de teste para validação

## 🚧 EM ANDAMENTO
- [ ] Testes de funcionalidade

## 📋 DETALHES DA IMPLEMENTAÇÃO

### ✅ 1. Mover Frontend
- [x] Copiar todo conteúdo de `frontend/` para `backend/public/`
- [x] Manter estrutura: css/, js/, assets/, img/, service-worker.js
- [x] Preservar: index.html, login.html, register.html, etc.

### ✅ 2. Ajustar Rotas Laravel
- [x] Modificar `backend/routes/web.php`
- [x] Adicionar fallback que serve index.html para rotas não-API
- [x] Usar regex para excluir rotas `/api`

### ✅ 3. Criar Procfile
- [x] Criar `backend/Procfile`
- [x] Conteúdo: `web: vendor/bin/heroku-php-apache2 public/`

### ✅ 4. Ajustar Dockerfile
- [x] Mudar de `php:8.2-cli` para `php:8.2-apache`
- [x] Instalar extensões necessárias
- [x] Configurar Apache corretamente
- [x] Expor porta 80

### ✅ 5. Verificar Entrypoint
- [x] Verificar compatibilidade com Apache
- [x] Ajustar se necessário

### ✅ 6. Usuários de Teste
- [x] Criar usuários para diferentes roles (admin, cliente, empresa)
- [x] Script para popular banco de dados

## 👥 USUÁRIOS DE TESTE CRIADOS

### Admin Master
- **Email:** admin@temdetudo.com
- **Senha:** Admin123!
- **Role:** admin

### Cliente Teste
- **Email:** cliente@temdetudo.com
- **Senha:** Cliente123!
- **Role:** cliente

### Empresa Teste
- **Email:** empresa@temdetudo.com
- **Senha:** Empresa123!
- **Role:** empresa

### Test User
- **Email:** test@example.com
- **Senha:** password
- **Role:** cliente

## 🧪 TESTES NECESSÁRIOS

1. [ ] Verificar se todos os arquivos HTML carregam corretamente
2. [ ] Testar se as rotas de API continuam funcionando
3. [ ] Verificar se os assets (CSS/JS) são carregados
4. [ ] Testar o service worker
5. [ ] Verificar se o Apache está servindo corretamente
6. [ ] Testar login com usuários de teste
7. [ ] Testar funcionalidades específicas por role
