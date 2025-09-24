# 💎 Tem de Tudo - Programa de Fidelidade

Sistema completo de programa de fidelidade com sistema de pontos, níveis e recompensas.

## 🌟 Funcionalidades Principais

### 🎯 Sistema de Pontos
- **Ganho de Pontos**: R$ 1,00 = 1 ponto base
- **Multiplicadores por Nível**: Bronze (1x), Prata (1.5x), Ouro (2x), Diamante (3x)
- **Bônus de Boas-vindas**: 100 pontos para novos usuários
- **Simulador de Compras**: Sistema de demonstração funcional

### 🏅 Níveis de Fidelidade
- **🥉 Bronze**: 0 - 999 pontos (1x multiplicador)
- **🥈 Prata**: 1.000 - 4.999 pontos (1.5x multiplicador)
- **🥇 Ouro**: 5.000 - 9.999 pontos (2x multiplicador)
- **💎 Diamante**: 10.000+ pontos (3x multiplicador)

### 👤 Sistema de Usuários
- Cadastro e login com autenticação JWT
- Perfil completo com dashboard de pontos
- Acompanhamento de nível e progresso
- Interface responsiva e moderna

## 🚀 Deploy no Render

### Configuração Automática
Este projeto está configurado para deploy automático no Render:

1. **Conecte o repositório** no Render
2. **Configure as variáveis de ambiente** (já configuradas no `render.yaml`)
3. **Deploy automático** será executado

### Variáveis de Ambiente Principais
```bash
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_URL=https://tem-de-tudo.onrender.com
DB_CONNECTION=pgsql
# Outras variáveis configuradas automaticamente pelo Render
```

### Usuários de Demonstração
O sistema cria automaticamente usuários para demonstração:

| Usuário | Email | Senha | Nível | Pontos |
|---------|-------|--------|-------|--------|
| Admin | admin@temdetudo.com | admin123 | Admin | 50.000 |
| João Cliente | cliente@teste.com | 123456 | Bronze | 250 |
| Maria Prata | cliente.prata@teste.com | 123456 | Prata | 2.500 |
| Carlos Ouro | cliente.ouro@teste.com | 123456 | Ouro | 7.500 |
| Ana Diamante | cliente.diamante@teste.com | 123456 | Diamante | 15.000 |
| Loja Parceira | empresa@teste.com | 123456 | Empresa | 0 |

## 🎨 Visual e UX

### Tema Premium
- **Cores principais**: Dourado (#f59e0b) e Azul (#1e40af)
- **Gradientes elegantes** em botões e seções
- **Animações suaves** e efeitos visuais
- **Design responsivo** para mobile e desktop

### Componentes Interativos
- Dashboard com estatísticas em tempo real
- Barra de progresso animada para próximo nível
- Simulador de compras funcional
- Feedback visual com toasts e loading

## 🛠️ Tecnologias Utilizadas

### Backend
- **Laravel 11** (PHP 8.2+)
- **PostgreSQL** (produção) / SQLite (desenvolvimento)
- **Laravel Sanctum** para autenticação
- **Docker** para containerização

### Frontend
- **HTML5, CSS3, JavaScript** vanilla
- **Design responsivo** com CSS Grid/Flexbox
- **Animações CSS** para melhor UX
- **API REST** para comunicação com backend

### Deploy e Infraestrutura
- **Render.com** para hospedagem
- **Docker** container otimizado
- **PostgreSQL** gerenciado pelo Render
- **SSL/HTTPS** automático

## 📱 Páginas Principais

1. **Landing Page** (`/`) - Apresentação do programa
2. **Login** (`/login.html`) - Autenticação de usuários
3. **Cadastro** (`/register.html`) - Registro de novos membros
4. **Perfil** (`/profile-client.html`) - Dashboard completo do usuário

## 🔧 API Endpoints

### Autenticação
- `POST /api/auth/register` - Cadastro de usuário
- `POST /api/auth/login` - Login
- `GET /api/user` - Dados do usuário logado
- `POST /api/logout` - Logout

### Pontos e Gamificação
- `POST /api/add-pontos` - Adicionar pontos ao usuário
- Cálculo automático de níveis e multiplicadores

## 🎯 Fluxo de Demonstração

### Para Vendedores/Apresentação:
1. **Acesse a landing page** - Mostra o conceito do programa
2. **Faça um cadastro** - Demonstra facilidade de adesão
3. **Explore o perfil** - Dashboard completo com pontos e níveis
4. **Simule compras** - Demonstra ganho de pontos em tempo real
5. **Teste diferentes níveis** - Use os usuários pré-criados

### Casos de Uso para Demonstração:
- **Cliente novo**: Cadastra e ganha 100 pontos bônus
- **Compra simulada**: Ganha pontos conforme valor e multiplicador
- **Progressão de nível**: Visualiza evolução de Bronze para Diamante
- **Dashboard interativo**: Acompanha estatísticas e progresso

## 💡 Pontos de Venda

### Para o Cliente:
- ✅ **Gratuito para participar**
- ✅ **Pontos a cada compra**
- ✅ **Níveis com vantagens crescentes**
- ✅ **Interface moderna e fácil**
- ✅ **Acompanhamento em tempo real**

### Para o Lojista:
- ✅ **Aumenta fidelização**
- ✅ **Incentiva compras maiores**
- ✅ **Sistema gamificado engaja clientes**
- ✅ **Relatórios e analytics**
- ✅ **Fácil implementação**

## 🚀 Próximos Passos

- [ ] Sistema de recompensas/prêmios
- [ ] Notificações push
- [ ] App mobile nativo
- [ ] Integração com sistemas de PDV
- [ ] Analytics avançados
- [ ] Programa de indicação

---

**🔥 Sistema pronto para demonstração e venda!**