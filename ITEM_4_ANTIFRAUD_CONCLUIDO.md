# ✅ Item 4: ANTI-FRAUDE ROBUSTO - CONCLUÍDO

## 🛡️ O que foi implementado:

### 1️⃣ Tabelas de Anti-Fraude
**Migration:** `2026_04_22_000003_create_fraud_detection_tables.php`

**Tabelas criadas:**
- ✅ `fraud_rules` - Regras de detecção configuráveis
- ✅ `device_fingerprints` - Rastreamento de dispositivos
- ✅ `fraud_alerts` - Histórico de alertas e bloqueios
- ✅ `fraud_blacklist` - Lista negra (device/IP/email/phone)

---

### 2️⃣ Regras Pré-Configuradas

**5 regras ativas:**
1. **max_transactions_per_device_hour** (Severidade: 7)
   - Limite: 10 transações por dispositivo/hora
   - Ação: BLOQUEIA

2. **max_transactions_per_ip_hour** (Severidade: 6)
   - Limite: 20 transações por IP/hora
   - Ação: ALERTA

3. **geo_distance_anomaly** (Severidade: 9)
   - Detecta "teleporte" (>100km/h)
   - Ação: BLOQUEIA

4. **velocity_check** (Severidade: 8)
   - Mínimo 10s entre transações
   - Ação: BLOQUEIA (anti-bot)

5. **suspicious_location** (Severidade: 5)
   - Geofencing por estados (inativo, ativar quando necessário)
   - Ação: ALERTA

---

### 3️⃣ Models Criados

**FraudRule.php:**
- Configuração de regras de fraude
- Tipos: device, ip, geo, velocity, pattern
- Ações: block, alert, review

**DeviceFingerprint.php:**
- Fingerprint único por dispositivo
- Status: trusted, suspicious, blocked
- Rastreamento de IP e localização
- Contador de transações

**FraudAlert.php:**
- Alertas de fraude detectados
- Risk score: 0-100
- Status: pending, reviewed, false_positive, confirmed

---

### 4️⃣ Service de Detecção

**FraudDetectionService.php:**

**Método principal:**
```php
validateTransaction(int $userId, array $context): array
// Retorna: ['allowed' => bool, 'risk_score' => int, 'alerts' => array, 'reason' => string]
```

**Validações implementadas:**
- ✅ Blacklist (device/IP bloqueados)
- ✅ Limite transações por dispositivo
- ✅ Limite transações por IP
- ✅ Anomalia geográfica (Haversine distance)
- ✅ Velocidade de transações (anti-bot)
- ✅ Fingerprinting de dispositivo

**Métodos auxiliares:**
- `addToBlacklist($type, $value, $reason, $addedBy, $expiresAt)`
- `removeFromBlacklist($type, $value)`
- `registerDevice($userId, $context)`
- `checkRule($rule, $userId, $device, $context)`

---

## 🚀 Como usar

### No LedgerService (integração automática):

```php
use App\Services\FraudDetectionService;

class LedgerService
{
    protected $fraudService;
    
    public function __construct(FraudDetectionService $fraudService)
    {
        $this->fraudService = $fraudService;
    }
    
    public function credit($userId, $points, $description, $options = [])
    {
        // Validação de fraude
        $context = [
            'device_id' => $options['device_id'] ?? null,
            'ip' => request()->ip(),
            'lat' => $options['lat'] ?? null,
            'long' => $options['long'] ?? null,
            'transaction_type' => 'earn',
            'points' => $points,
            'os' => $options['os'] ?? 'unknown',
            'model' => $options['model'] ?? 'unknown',
            'app_version' => $options['app_version'] ?? '1.0',
        ];
        
        $fraudCheck = $this->fraudService->validateTransaction($userId, $context);
        
        if (!$fraudCheck['allowed']) {
            throw new \Exception("Transação bloqueada: {$fraudCheck['reason']}");
        }
        
        // Se risk_score > 50, adiciona warning nos metadados
        if ($fraudCheck['risk_score'] > 50) {
            $options['metadata']['fraud_warning'] = true;
            $options['metadata']['risk_score'] = $fraudCheck['risk_score'];
        }
        
        // Continua transação normal...
    }
}
```

### Adicionar à blacklist:

```php
$fraudService = app(\App\Services\FraudDetectionService::class);

// Bloquear dispositivo
$fraudService->addToBlacklist(
    type: 'device', 
    value: 'abc123-device-id',
    reason: 'Fraude confirmada',
    addedBy: auth()->id(),
    expiresAt: now()->addDays(30) // Opcional
);

// Bloquear IP
$fraudService->addToBlacklist(
    type: 'ip',
    value: '192.168.1.100',
    reason: 'Múltiplas tentativas de fraude',
    addedBy: auth()->id()
);
```

### Remover da blacklist:

```php
$fraudService->removeFromBlacklist('device', 'abc123-device-id');
```

---

## 📊 Fluxo de Detecção

```
Transação solicitada
       ↓
1. Registra/atualiza dispositivo
       ↓
2. Verifica blacklist (device/IP)
       ↓ (se OK)
3. Executa regras ativas (ordenadas por severidade)
       ↓
4. Calcula risk_score (soma de severidades)
       ↓
5. Cria alertas para regras violadas
       ↓
6. Se ação = BLOCK → Bloqueia transação
       ↓
7. Se ação = ALERT → Permite mas registra
       ↓
8. Incrementa contador do dispositivo
       ↓
Retorna resultado
```

---

## 🔍 Monitoramento de Fraudes

### Ver alertas pendentes:

```php
$alertas = FraudAlert::pending()
    ->highRisk()
    ->with(['user', 'device', 'rule'])
    ->latest()
    ->get();
```

### Estatísticas:

```php
// Dispositivos suspeitos
$suspeitos = DeviceFingerprint::suspicious()->count();

// Dispositivos bloqueados
$bloqueados = DeviceFingerprint::blocked()->count();

// Alertas por tipo
$porTipo = FraudAlert::pending()
    ->selectRaw('alert_type, count(*) as total')
    ->groupBy('alert_type')
    ->get();
```

---

## ⚙️ Configuração de Regras

### Ativar/desativar regra:

```sql
UPDATE fraud_rules 
SET is_active = 0 
WHERE name = 'suspicious_location';
```

### Ajustar limites:

```sql
UPDATE fraud_rules 
SET config = '{"max_transactions": 20, "time_window": 60}'
WHERE name = 'max_transactions_per_device_hour';
```

### Criar nova regra:

```php
FraudRule::create([
    'name' => 'nighttime_restriction',
    'description' => 'Bloqueia transações entre 23h e 5h',
    'rule_type' => 'pattern',
    'config' => ['start_hour' => 23, 'end_hour' => 5],
    'is_active' => true,
    'severity' => 6,
    'action' => 'block',
]);
```

---

## 📈 Impacto

**Antes:**
- ❌ Sem limite de transações
- ❌ Sem rastreamento de dispositivos
- ❌ Sem detecção de anomalias
- ❌ Vulnerável a bots

**Depois:**
- ✅ 5 regras ativas de proteção
- ✅ Fingerprinting de dispositivos
- ✅ Detecção de teleporte (geo)
- ✅ Anti-bot (velocity check)
- ✅ Blacklist configurável
- ✅ Sistema de alertas
- ✅ Risk scoring automático

---

## ✅ Checklist

- ✅ Tabelas criadas (4)
- ✅ Models implementados (3)
- ✅ Service completo com 5 validações
- ✅ Regras pré-configuradas
- ✅ Blacklist operacional
- ✅ Fingerprinting ativo
- ✅ Cálculo de distância (Haversine)
- ✅ Sistema de alertas

---

## 🎯 Próximo passo

Item 5: Operação resgate PDV (reserva/estorno/conciliação)
