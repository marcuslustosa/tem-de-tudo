# API Reference - Sistema de Fidelidade

## 📋 Índice
- [Autenticação](#autenticação)
- [API Cliente](#api-cliente)
- [API Empresa](#api-empresa)
- [Dados de Teste](#dados-de-teste)
- [Regras de Negócio](#regras-de-negócio)

---

## 🔐 Autenticação

### Registro
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@email.com",
  "password": "senha123",
  "perfil": "cliente", // ou "empresa"
  "telefone": "(11) 99999-9999"
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "perfil": "cliente"
    },
    "token": "1|abc123..."
  }
}
```

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "joao@email.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "2|xyz789..."
  }
}
```

### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

---

## 👤 API Cliente

### Dashboard
Retorna overview completo do cliente.

```http
GET /api/cliente/dashboard
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "pontos_totais": 2500,
    "saldo_pontos": 1800,
    "empresas_favoritas": [
      {
        "id": 1,
        "nome": "Restaurante Sabor da Terra",
        "total_pontos": 1200
      }
    ],
    "ultimas_transacoes": [...],
    "promocoes": [...]
  }
}
```

### Listar Empresas
Lista empresas com filtros e pontos do usuário.

```http
GET /api/cliente/empresas?ramo=restaurante&busca=sabor
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nome": "Restaurante Sabor da Terra",
      "ramo": "restaurante",
      "avaliacao_media": 4.5,
      "meus_pontos": 1200
    }
  ]
}
```

### Detalhes da Empresa
Retorna perfil completo da empresa.

```http
GET /api/cliente/empresas/{id}
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "empresa": {...},
    "meus_pontos": 1200,
    "promocoes": [...],
    "avaliacoes": [...],
    "minha_avaliacao": {...}
  }
}
```

### Escanear QR Code
Escaneia QR code e ganha pontos.

**Limite:** 3 scans por dia por empresa

```http
POST /api/cliente/escanear-qrcode
Authorization: Bearer {token}
Content-Type: application/json

{
  "qrcode": "EMP1_ENTRADA_12345"
}
```

**Resposta (Sucesso):**
```json
{
  "success": true,
  "message": "QR Code escaneado com sucesso!",
  "data": {
    "pontos_ganhos": 150,
    "saldo_total": 1950,
    "empresa": "Restaurante Sabor da Terra"
  }
}
```

**Resposta (Limite Atingido):**
```json
{
  "success": false,
  "message": "Você já escaneou 3 QR Codes desta empresa hoje. Volte amanhã!"
}
```

### Resgatar Promoção
Resgata uma promoção usando pontos.

**Custo:** desconto × 10 pontos (ex: 20% desconto = 200 pontos)  
**Limite:** 1 resgate por dia por promoção

```http
POST /api/cliente/resgatar-promocao/{promocao_id}
Authorization: Bearer {token}
```

**Resposta (Sucesso):**
```json
{
  "success": true,
  "message": "Promoção resgatada com sucesso!",
  "data": {
    "codigo_resgate": "a1b2c3d4e5f6",
    "promocao": "20% de desconto no almoço",
    "pontos_gastos": 200,
    "saldo_restante": 1750
  }
}
```

**Resposta (Saldo Insuficiente):**
```json
{
  "success": false,
  "message": "Você precisa de 200 pontos para resgatar esta promoção. Você tem apenas 150."
}
```

### Avaliar Empresa
Cria ou atualiza avaliação.

```http
POST /api/cliente/avaliar
Authorization: Bearer {token}
Content-Type: application/json

{
  "empresa_id": 1,
  "estrelas": 5,
  "comentario": "Excelente atendimento!"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Avaliação registrada com sucesso!",
  "data": {
    "nova_media": 4.6,
    "total_avaliacoes": 15
  }
}
```

### Histórico de Pontos
Lista transações paginadas.

```http
GET /api/cliente/historico-pontos?tipo=ganho&empresa_id=1&page=1
Authorization: Bearer {token}
```

**Parâmetros:**
- `tipo`: `ganho` ou `resgate` (opcional)
- `empresa_id`: ID da empresa (opcional)
- `page`: Página (padrão: 1)

**Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "pontos": 150,
        "tipo": "ganho",
        "descricao": "QR Code escaneado",
        "empresa_nome": "Restaurante Sabor da Terra",
        "created_at": "2024-01-15 14:30:00"
      }
    ],
    "total": 45,
    "per_page": 20
  }
}
```

---

## 🏢 API Empresa

### Dashboard
Estatísticas gerais da empresa.

```http
GET /api/empresa/dashboard
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "empresa": {...},
    "estatisticas": {
      "total_clientes": 42,
      "pontos_hoje": 850,
      "pontos_mes": 12500,
      "scans_hoje": 15,
      "promocoes_ativas": 3
    },
    "top_clientes": [...],
    "ultimas_transacoes": [...]
  }
}
```

### Listar Clientes
Lista clientes com pontos paginado.

```http
GET /api/empresa/clientes?page=1
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "name": "Maria Silva",
        "email": "maria.silva@email.com",
        "telefone": "(11) 98765-4321",
        "total_ganho": 1200,
        "total_gasto": 400,
        "ultima_visita": "2024-01-15 14:30:00"
      }
    ],
    "total": 42,
    "per_page": 20
  }
}
```

### Listar Promoções
Lista todas as promoções da empresa.

```http
GET /api/empresa/promocoes
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "titulo": "20% de desconto no almoço",
      "desconto": 20,
      "ativo": true,
      "status": "ativa",
      "visualizacoes": 150,
      "resgates": 12
    }
  ]
}
```

### Criar Promoção
Cria nova promoção.

```http
POST /api/empresa/promocoes
Authorization: Bearer {token}
Content-Type: application/json

{
  "titulo": "30% OFF na sobremesa",
  "descricao": "Válido de seg a sex após às 18h",
  "desconto": 30,
  "imagem": "sobremesa.jpg"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Promoção criada com sucesso!",
  "data": {
    "id": 8,
    "titulo": "30% OFF na sobremesa",
    "desconto": 30,
    "ativo": true,
    "status": "ativa"
  }
}
```

### Atualizar Promoção
Atualiza promoção existente.

```http
PUT /api/empresa/promocoes/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "titulo": "40% OFF na sobremesa",
  "desconto": 40,
  "ativo": true
}
```

### Deletar Promoção
Remove promoção.

```http
DELETE /api/empresa/promocoes/{id}
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Promoção deletada com sucesso!"
}
```

### Listar QR Codes
Lista QR codes da empresa.

```http
GET /api/empresa/qrcodes
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "EMP1_ENTRADA_12345",
      "location": "Entrada",
      "active": true,
      "usage_count": 450,
      "last_used_at": "2024-01-15 14:30:00"
    }
  ]
}
```

### Avaliações
Estatísticas e lista de avaliações.

```http
GET /api/empresa/avaliacoes
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "avaliacoes": [...],
    "media": 4.5,
    "total": 25,
    "distribuicao": [
      {"estrelas": 5, "quantidade": 15},
      {"estrelas": 4, "quantidade": 7},
      {"estrelas": 3, "quantidade": 2},
      {"estrelas": 2, "quantidade": 1},
      {"estrelas": 1, "quantidade": 0}
    ]
  }
}
```

### Relatório de Pontos
Relatório detalhado de pontos por período.

```http
GET /api/empresa/relatorio-pontos?data_inicio=2024-01-01&data_fim=2024-01-31
Authorization: Bearer {token}
```

**Parâmetros:**
- `data_inicio`: Data inicial (padrão: 30 dias atrás)
- `data_fim`: Data final (padrão: hoje)

**Resposta:**
```json
{
  "success": true,
  "data": {
    "periodo": {
      "inicio": "2024-01-01",
      "fim": "2024-01-31"
    },
    "totais": {
      "total_distribuido": 12500,
      "total_resgatado": 3200,
      "total_clientes": 42
    },
    "por_dia": [
      {
        "data": "2024-01-15",
        "pontos_distribuidos": 850,
        "pontos_resgatados": 200,
        "clientes_unicos": 15
      }
    ]
  }
}
```

---

## 🧪 Dados de Teste

### Clientes
```
maria.silva@email.com / senha123
joao.santos@email.com / senha123
ana.costa@email.com / senha123
pedro.oliveira@email.com / senha123
julia.ferreira@email.com / senha123
```

### Empresas
```
contato@sabordaterra.com / senha123 - Restaurante Sabor da Terra
contato@fitlife.com / senha123 - Academia FitLife
contato@aromacafe.com / senha123 - Café Aroma & Sabor
contato@bichofeliz.com / senha123 - Pet Shop Bicho Feliz
contato@belezapura.com / senha123 - Salão Beleza Pura
contato@bompreco.com / senha123 - Mercado Bom Preço
contato@saudetotal.com / senha123 - Farmácia Saúde Total
```

### QR Codes de Teste
```
EMP1_ENTRADA - Restaurante (Entrada)
EMP1_CAIXA - Restaurante (Caixa)
EMP2_ENTRADA - Academia (Entrada)
EMP2_RECEPCAO - Academia (Recepção)
EMP3_ENTRADA - Café (Entrada)
...e mais
```

---

## 📐 Regras de Negócio

### Sistema de Pontos

**Ganho de Pontos (QR Code):**
- Base: 100 pontos
- Multiplicador da empresa: varia de 1.0 a 3.0
- Cálculo: `100 × multiplicador`
- Limite: 3 scans por dia por empresa

**Custo de Promoções:**
- Fórmula: `desconto × 10 = pontos`
- Exemplos:
  - 10% desconto = 100 pontos
  - 20% desconto = 200 pontos
  - 50% desconto = 500 pontos
- Limite: 1 resgate por dia por promoção

### Avaliações
- 1 avaliação por cliente por empresa
- Atualização de avaliação: permitida
- Média recalculada automaticamente
- Influencia no ranking de empresas

### QR Codes
- Cada empresa tem múltiplos QR codes
- Localizações diferentes (Entrada, Caixa, etc.)
- Controle de uso (contador + último uso)
- Validação de QR ativo

### Transações
- Histórico completo mantido
- Tipos: `ganho` e `resgate`
- Saldo calculado: soma(ganho) - soma(resgate)
- Paginação: 20 itens por página

### Segurança
- Token Sanctum obrigatório
- Validação de perfil (cliente/empresa)
- Rate limiting por endpoint
- Validação de propriedade (empresa só edita suas promoções)

---

## 📊 Estrutura de Resposta

### Sucesso
```json
{
  "success": true,
  "message": "Operação realizada com sucesso!",
  "data": {...}
}
```

### Erro
```json
{
  "success": false,
  "message": "Descrição do erro",
  "errors": {
    "campo": ["Mensagem de validação"]
  }
}
```

### Códigos HTTP
- `200` - Sucesso
- `201` - Criado
- `400` - Dados inválidos
- `401` - Não autenticado
- `403` - Não autorizado
- `404` - Não encontrado
- `429` - Limite excedido
- `500` - Erro do servidor
