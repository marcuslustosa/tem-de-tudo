# 🔐 Acessos do Sistema

## 🎯 Estrutura de Acessos

O sistema possui **3 tipos de acesso** principais:

### 👑 Admin Real
- **Função**: Gerencia perfis das empresas, administrador do sistema
- **Email**: admin@temdetudo.com
- **Tipo**: Administrador real com poderes totais
- **Responsabilidade**: Gestão completa do sistema

### 👤 Cliente Fictício  
- **Função**: Dados fictícios para simulação de transações
- **Email**: cliente@teste.com
- **Tipo**: Cliente para demonstrações
- **Características**: 
  - Possui pontos fictícios (250 pontos)
  - Dados são apenas para simulação
  - Pode realizar transações de teste

### 🏢 Empresa Fictícia
- **Função**: Dados fictícios para simulação de transações
- **Email**: empresa@teste.com  
- **Tipo**: Empresa para demonstrações
- **Características**:
  - Dados fictícios completos
  - Pode simular ofertas e promoções
  - Transações sem fins legais

## ⚠️ Importante

- **Dados Fictícios = Sem Fins Legais**
- Os usuários "Cliente" e "Empresa" são apenas para simulação
- Todas as transações são de demonstração
- O Admin é o único acesso real para gestão do sistema

## 🔑 Como Obter as Senhas

### 🚀 **AUTOMÁTICO NO RENDER:**
- Os 3 acessos são criados **automaticamente** a cada deploy
- Sempre que você fizer push para GitHub, eles estarão disponíveis
- **🛡️ SEGURANÇA TOTAL:** Preserva 100% dos dados reais
- **✅ PROTEGE:** Cadastros, transações, logins, empresas, pontos reais
- **🔄 ATUALIZA:** Apenas os 3 acessos de teste específicos
- Não precisa executar nada manualmente!

### 💻 **LOCAL (se precisar):**
Para obter as senhas localmente:

1. Execute: `php artisan db:seed --class=SimpleSeeder`
2. As senhas serão exibidas no terminal durante a execução  
3. **Não commite senhas no repositório!**

## 🚀 Como Usar

1. Faça login com qualquer dos 3 acessos
2. Teste as funcionalidades específicas de cada tipo
3. Use dados fictícios para demonstrações
4. Admin gerencia todo o sistema