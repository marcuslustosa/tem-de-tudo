# TODO - Deploy Laravel no Render

## ✅ COMPLETADO
- [x] Análise da estrutura atual do projeto
- [x] Criação do plano de implementação
- [x] Mover frontend para backend/public/
- [x] Ajustar routes/web.php para fallback correto
- [x] Criar Procfile para Render
- [x] Ajustar Dockerfile para Apache
- [x] Verificar entrypoint.sh
- [x] Corrigir JavaScript para funcionar com respostas do backend
- [x] Corrigir formulário de registro com campo role
- [x] Configurar Sanctum corretamente
- [x] Testes de funcionalidade

## 📋 CORREÇÕES IMPLEMENTADAS

### 1. ✅ Estrutura de Arquivos
- Frontend movido para `backend/public/`
- Todos os arquivos HTML, CSS, JS preservados
- Service worker mantido

### 2. ✅ Rotas Laravel
- `routes/api.php`: Rotas de API com middleware auth:sanctum
- `routes/web.php`: Fallback para servir index.html
- Separação clara entre API e frontend

### 3. ✅ Configuração de Deploy
- `Procfile`: `web: vendor/bin/heroku-php-apache2 public/`
- `Dockerfile`: Configurado para Apache + PHP 8.2
- `entrypoint.sh`: Compatível com Apache

### 4. ✅ Correções de API
- AuthController: Respostas JSON corretas
- JavaScript: Compatível com formato das respostas
- Formulário de registro: Campo role adicionado
- CORS: Configurado para frontend-backend

### 5. ✅ Configurações Laravel
- Sanctum: Configurado corretamente
- Bootstrap: Middleware auth.sanctum configurado
- Database: Migrations e seeders atualizados

## 🚀 PRÓXIMOS PASSOS

### Deploy no Render
1. Fazer push das mudanças para o repositório
2. Conectar o repositório ao Render
3. Configurar Web Service com:
   - Root Directory: `backend`
   - Build Command: `composer install --no-dev --optimize-autoloader`
   - Start Command: `heroku-php-apache2 public/`

### Testes Finais
1. Verificar se frontend carrega em `/`
2. Testar login/cadastro em `/login`, `/register`
3. Verificar se API funciona em `/api/auth/*`
4. Testar redirecionamentos baseados em roles

## 📝 NOTAS IMPORTANTES

- **URL da API**: Frontend configurado para `https://tem-de-tudo.onrender.com/api`
- **Roles**: Suportadas: `cliente`, `empresa`, `admin`
- **Autenticação**: Usa Laravel Sanctum com tokens
- **CORS**: Configurado para permitir requisições do frontend
- **Service Worker**: Mantido para funcionalidades offline

## 🔧 ARQUIVOS MODIFICADOS

### Backend Laravel
- `routes/api.php` - Rotas de API
- `routes/web.php` - Fallback para frontend
- `app/Http/Controllers/AuthController.php` - Lógica de auth
- `bootstrap/app.php` - Configuração Sanctum
- `config/sanctum.php` - Configuração Sanctum
- `Procfile` - Comando para Render
- `Dockerfile` - Configuração Apache
- `entrypoint.sh` - Script de inicialização

### Frontend
- `public/js/app.js` - Correções para API
- `public/register.html` - Campo role adicionado
- `public/login.html` - Mantido como estava

### Database
- `database/seeders/DatabaseSeeder.php` - Usuários de teste
- `seed_users.php` - Script para criar usuários

## ✅ STATUS: PRONTO PARA DEPLOY
