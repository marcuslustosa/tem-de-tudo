# 🕐 Configurar Scheduler no Render (Plano Free)

## 📋 Visão Geral

Como o Render Free não suporta cron jobs nativos, criamos endpoints HTTP para executar o Laravel Scheduler. Você pode usar serviços gratuitos de cron job como **cron-job.org** ou **EasyCron** para chamar esses endpoints periodicamente.

## 🔐 Token de Segurança

O endpoint é protegido por um token configurado nas variáveis de ambiente:

**Variável:** `SCHEDULER_TOKEN`  
**Valor padrão:** `tem-de-tudo-scheduler-2026-secure-token-xyz`

> ⚠️ **IMPORTANTE:** Altere este token no Render Dashboard para um valor único e secreto!

## 📡 Endpoints Disponíveis

### 1. Executar Scheduler Completo
```
GET https://app-tem-de-tudo.onrender.com/api/scheduler/run?token=SEU_TOKEN
```

Executa todos os comandos agendados do Laravel Scheduler.

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Scheduler executado com sucesso",
  "output": "No scheduled commands are ready to run.",
  "timestamp": "2026-02-18 08:00:00"
}
```

### 2. Executar Bônus Aniversário
```
GET https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=SEU_TOKEN
```

Executa apenas o comando de processamento de bônus de aniversário.

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Bônus aniversário processado com sucesso",
  "output": "Processados X clientes aniversariantes",
  "timestamp": "2026-02-18 08:00:00"
}
```

### 3. Status do Scheduler
```
GET https://app-tem-de-tudo.onrender.com/api/scheduler/status?token=SEU_TOKEN
```

Retorna informações sobre a configuração do scheduler.

**Resposta:**
```json
{
  "success": true,
  "server_time": "2026-02-18 08:00:00",
  "timezone": "America/Sao_Paulo",
  "scheduler_configured": true,
  "commands": {
    "bonus:aniversario": "Executa diariamente às 08:00"
  }
}
```

## ⚙️ Configurar Cron Job Gratuito

### Opção 1: cron-job.org (Recomendado)

1. Acesse: https://cron-job.org/en/
2. Crie uma conta gratuita
3. Clique em **"Create cronjob"**
4. Configure:
   ```
   Title: Tem de Tudo - Bônus Aniversário
   URL: https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=SEU_TOKEN
   Schedule: 
     - Every day
     - At 08:00 (America/Sao_Paulo)
   ```
5. Salve o cron job

**Limites do plano free:**
- ✅ 50 execuções/dia
- ✅ Execução a cada 1 minuto (ou específico)
- ✅ 10 cron jobs simultâneos

### Opção 2: EasyCron

1. Acesse: https://www.easycron.com/
2. Crie uma conta gratuita
3. Adicione um novo cron job:
   ```
   URL: https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=SEU_TOKEN
   When: Daily at 08:00
   Timezone: GMT-3 (Brasília)
   ```

**Limites do plano free:**
- ✅ 1 cron job
- ✅ Execução diária

### Opção 3: GitHub Actions (Avançado)

Crie um workflow no repositório:

`.github/workflows/scheduler.yml`:
```yaml
name: Laravel Scheduler

on:
  schedule:
    - cron: '0 11 * * *'  # 08:00 BRT = 11:00 UTC

jobs:
  run-scheduler:
    runs-on: ubuntu-latest
    steps:
      - name: Execute Scheduler
        run: |
          curl -X GET "https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=${{ secrets.SCHEDULER_TOKEN }}"
```

Adicione o secret `SCHEDULER_TOKEN` no GitHub: Settings > Secrets and variables > Actions

## 🔍 Monitoramento

### 1. Via Logs do Render

No dashboard do Render, vá em **Logs** e procure por:
```
[2026-02-18 08:00:00] local.INFO: Scheduler executado via HTTP
[2026-02-18 08:00:00] local.INFO: Processando bônus de aniversário
```

### 2. Via Endpoint de Status

Teste manualmente no navegador:
```
https://app-tem-de-tudo.onrender.com/api/scheduler/status?token=SEU_TOKEN
```

### 3. Configurar Alertas no Cron-Job.org

No cron-job.org, você pode configurar notificações por email se o job falhar.

## 🧪 Testar Manualmente

### No navegador:
```
https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=SEU_TOKEN
```

### No terminal (PowerShell):
```powershell
$token = "tem-de-tudo-scheduler-2026-secure-token-xyz"
$url = "https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=$token"
Invoke-RestMethod -Uri $url -Method Get
```

### Com curl:
```bash
curl "https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus?token=SEU_TOKEN"
```

## 🔒 Segurança

### Alterar o Token no Render

1. Acesse o Dashboard do Render
2. Vá no serviço "tem-de-tudo"
3. Vá em **Environment**
4. Edite `SCHEDULER_TOKEN`
5. Coloque um valor único e complexo:
   ```
   Exemplo: td2026-Sch3d-9f82b-a1c3e-secure
   ```
6. Salve e faça redeploy

### Usar Header ao invés de Query String (Mais Seguro)

```bash
curl -H "X-Scheduler-Token: SEU_TOKEN" \
     https://app-tem-de-tudo.onrender.com/api/scheduler/birthday-bonus
```

No cron-job.org, adicione em **Request headers**:
```
X-Scheduler-Token: SEU_TOKEN
```

## 📊 Frequência Recomendada

| Comando | Frequência | Horário |
|---------|-----------|---------|
| `scheduler/run` | A cada 5 minutos | * * * * * |
| `scheduler/birthday-bonus` | Diário | 08:00 BRT |

> **Nota:** O comando `bonus:aniversario` já está configurado para rodar apenas uma vez por dia, então mesmo que você chame várias vezes, ele não vai duplicar bônus.

## ⚠️ Troubleshooting

### Erro 401 - Token inválido
Verifique se o token está correto e atualizado no Render.

### Erro 500 - Internal Server Error
Verifique os logs do Render para mais detalhes.

### Scheduler não executa
1. Verifique se o cron job está ativo no serviço externo
2. Teste a URL manualmente no navegador
3. Verifique os logs do Render

### Render em sleep mode (cold start)
O plano free do Render hiberna após 15 minutos de inatividade. O primeiro request pode demorar 30-60 segundos.

**Solução:** Configure um cron job para chamar o endpoint de status a cada 10 minutos:
```
https://app-tem-de-tudo.onrender.com/api/scheduler/status?token=SEU_TOKEN
```

Isso mantém o serviço "acordado" e garante que o bônus de aniversário execute rapidamente às 08:00.

## ✅ Checklist de Configuração

- [ ] Alterar `SCHEDULER_TOKEN` no Render Dashboard
- [ ] Criar conta no cron-job.org
- [ ] Configurar cron job para `birthday-bonus` às 08:00
- [ ] (Opcional) Configurar cron job para `status` a cada 10 min
- [ ] Testar endpoint manualmente
- [ ] Verificar logs do Render
- [ ] Configurar alertas de falha

---

**Pronto!** O sistema de bônus aniversário agora funciona automaticamente no Render Free! 🎂✨
