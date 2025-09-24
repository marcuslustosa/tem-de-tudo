# 👑 Sistema de Administradores Master

## 📋 Visão1. Digite o email do administrador
2. Digite a senha: adm@123
3. Clique em "Entrar"ral

O sistema Tem de Ponto possui um sistema robusto de administradores master que permite gerenciar todo o sistema de pontuação e empresas. Este guia explica como criar e gerenciar contas de administrador.

## 🔑 Como Criar um Usuário Master

### 1. Acesso à Página de Registro
- Acesse: `http://localhost/register-admin.html`
- Ou clique em "Registrar Administrador" na página de login

### 2. Tokens de Acesso Válidos
Para criar um administrador, você precisa de um dos seguintes tokens:

```
TEMDETUDO_ADMIN_2025
MASTER_ACCESS_TOKEN_2025
TDP_ADMIN_CREATE_2025
```

### 3. Informações Necessárias
- **Token de Acesso**: Um dos tokens listados acima
- **Nome Completo**: Nome do administrador
- **Email**: Email único para login
- **Telefone**: Telefone de contato
- **Senha**: Mínimo 6 caracteres
- **Nome da Empresa** (opcional): Empresa do administrador
- **CNPJ** (opcional): CNPJ da empresa

### 4. Processo de Criação
1. Preencha todos os campos obrigatórios
2. Digite um token válido
3. Confirme a senha
4. Clique em "Criar Conta de Administrador"
5. Aguarde a confirmação de criação

## 🚪 Como Fazer Login como Administrador

### Opção 1: Administrador de Teste (Pré-configurado)
```
Email: admin@temdeponto.com
Senha: adm@123
```

### Opção 2: Administrador Criado via Registro
```
Email: o email que você cadastrou
Senha: a senha que você definiu
```

### Processo de Login
1. Acesse a página de login
2. Digite o email do administrador
3. Digite qualquer senha (sistema de demonstração)
4. Clique em "Entrar"
5. Será redirecionado automaticamente para `/admin.html`

## 🎯 Recursos do Administrador Master

### 📊 Dashboard Principal
- Métricas globais do sistema
- Gráficos de desempenho
- Visão geral de empresas ativas

### 🏢 Gerenciamento de Empresas
- Lista de todas as empresas cadastradas
- Aprovação de novos cadastros
- Configuração de taxas e comissões
- Controle de status (ativo/inativo)

### 💰 Relatórios Financeiros
- Receita total do sistema
- Comissões por empresa
- Relatórios de pontos gerados
- Histórico de transações

### ⚙️ Configurações do Sistema
- Configurações globais
- Taxas de pontuação
- Parâmetros de recompensas
- Configurações de segurança

### 👥 Gestão de Usuários
- Lista de todos os usuários
- Estatísticas de uso
- Moderação de contas
- Suporte ao cliente

## 🔧 Configuração Técnica

### Armazenamento Local
Os administradores são armazenados no `localStorage` com a chave `systemAdmins`:

```javascript
// Estrutura do administrador
{
  id: timestamp,
  nome: "Nome do Admin",
  email: "admin@empresa.com",
  telefone: "(11) 99999-9999",
  tipo: "admin",
  nivel: "Master",
  empresa: "Nome da Empresa",
  cnpj: "00.000.000/0000-00",
  permissoes: [
    "gerenciar_empresas",
    "relatorios_financeiros", 
    "configuracoes_sistema",
    "criar_administradores"
  ],
  criadoEm: "2025-09-24T...",
  status: "ativo"
}
```

### Sistema de Validação
- **Tokens de Acesso**: Validação no frontend (para demonstração)
- **Emails Únicos**: Não permite duplicação de emails
- **Senhas**: Mínimo 6 caracteres
- **Status**: Apenas admins ativos podem fazer login

## 🚀 Próximos Passos para Produção

### 1. Segurança Backend
```php
// Implementar no backend Laravel
Route::post('/admin/register', [AdminController::class, 'register'])
    ->middleware('validateAdminToken');
```

### 2. Validação de Tokens
```php
// Validar tokens no servidor
class AdminTokenValidator {
    public function validate($token) {
        return Hash::check($token, config('app.admin_master_token'));
    }
}
```

### 3. Base de Dados
```sql
CREATE TABLE admins (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    company_cnpj VARCHAR(18),
    permissions JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 📞 Suporte

### Para Desenvolvedores
- Os tokens são definidos no arquivo `register-admin.html`
- Modifique a array `VALID_TOKENS` para adicionar novos tokens
- O sistema funciona completamente offline para demonstração

### Para Clientes
1. Entre em contato com o desenvolvedor para obter um token
2. Acesse a página de registro de administrador
3. Preencha os dados e use o token fornecido
4. Após criar a conta, faça login normalmente

## ⚠️ Importantes Considerações

### Segurança
- **Nunca exponha tokens em produção**
- Use HTTPS em produção
- Implemente rate limiting
- Use autenticação JWT ou similar

### Backup
- Faça backup regular dos dados de administradores
- Mantenha logs de acesso e ações
- Implemente recuperação de conta

### Monitoramento
- Log todas as ações administrativas
- Monitore tentativas de acesso inválido
- Alertas para criação de novos administradores

---

*Este sistema foi desenvolvido para oferecer máxima flexibilidade e segurança na gestão de administradores do sistema Tem de Ponto.*