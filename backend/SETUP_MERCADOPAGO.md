# 💳 Configuração Mercado Pago - Integração PIX

## Passo 1: Criar Conta Mercado Pago

1. Acesse: https://www.mercadopago.com.br
2. Crie uma conta ou faça login
3. Complete o cadastro (dados pessoais/empresa)

## Passo 2: Acessar Área de Desenvolvedores

1. Acesse: https://www.mercadopago.com.br/developers
2. Vá em **Suas integrações**
3. Clique em **Criar aplicação**
4. Nome: `TemDeTudo - Compra de Pontos`
5. Selecione: **Pagamentos online**
6. Clique em **Criar aplicação**

## Passo 3: Obter Credenciais de Teste

1. Na tela da aplicação criada
2. Vá na aba **Credenciais**
3. Copie as **Credenciais de teste**:
   - **Public Key (teste)**: `TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx`
   - **Access Token (teste)**: `TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx`

## Passo 4: Criar Usuários de Teste

1. Na mesma área de desenvolvedores
2. Vá em **Usuários de teste**
3. Clique em **Criar usuário de teste**
4. Crie 2 usuários:
   - **Vendedor** (quem recebe o pagamento)
   - **Comprador** (quem faz o pagamento)
5. Anote os emails e senhas gerados

## Passo 5: Instalar SDK do Mercado Pago

```bash
# No diretório backend/
composer require mercadopago/dx-php
```

## Passo 6: Atualizar .env.render

```env
# Mercado Pago Configuration
MERCADOPAGO_PUBLIC_KEY=TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx
MERCADOPAGO_ACCESS_TOKEN=TEST-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx
MERCADOPAGO_WEBHOOK_SECRET=sua_secret_aqui_opcional
```

## Passo 7: Atualizar PaymentController

Já está implementado! O código básico está em:
`app/Http/Controllers/PaymentController.php`

Mas vou criar uma versão melhorada:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MercadoPago\SDK;
use MercadoPago\Payment;
use MercadoPago\Payer;

class PaymentController extends Controller
{
    public function __construct()
    {
        SDK::setAccessToken(env('MERCADOPAGO_ACCESS_TOKEN'));
    }

    public function createPixPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'email' => 'required|email'
        ]);

        try {
            $payment = new Payment();
            $payment->transaction_amount = floatval($request->amount);
            $payment->description = $request->description;
            $payment->payment_method_id = "pix";
            
            $payer = new Payer();
            $payer->email = $request->email;
            $payment->payer = $payer;
            
            $payment->save();
            
            if ($payment->status === 'pending') {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $payment->id,
                        'qr_code' => $payment->point_of_interaction->transaction_data->qr_code,
                        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64,
                        'expires_at' => $payment->date_of_expiration,
                        'status' => $payment->status
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PIX'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function checkPaymentStatus($paymentId)
    {
        try {
            $payment = Payment::find_by_id($paymentId);
            
            return response()->json([
                'success' => true,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

## Passo 8: Configurar Webhook (Importante!)

1. No painel do Mercado Pago Developers
2. Vá em **Webhooks**
3. Clique em **Criar webhook**
4. URL: `https://tem-de-tudo.onrender.com/api/webhooks/mercadopago`
5. Eventos: Selecione **payment**
6. Salve

Criar rota no `routes/api.php`:

```php
Route::post('/webhooks/mercadopago', [PaymentController::class, 'handleWebhook']);
```

Implementar no controller:

```php
public function handleWebhook(Request $request)
{
    $data = $request->all();
    
    // Validar signature (segurança)
    $signature = $request->header('x-signature');
    // Implementar validação...
    
    if ($data['type'] === 'payment') {
        $paymentId = $data['data']['id'];
        $payment = Payment::find_by_id($paymentId);
        
        if ($payment->status === 'approved') {
            // Adicionar pontos ao usuário
            $user = User::where('email', $payment->payer->email)->first();
            if ($user) {
                $user->pontos += $this->calculatePoints($payment->transaction_amount);
                $user->save();
                
                // Enviar notificação
                // ...
            }
        }
    }
    
    return response()->json(['success' => true]);
}
```

## Passo 9: Testar com Cartões de Teste

**Cartão de Crédito Aprovado:**
- Número: `5031 4332 1540 6351`
- CVV: `123`
- Validade: Qualquer data futura
- Nome: Qualquer nome

**PIX Teste:**
1. Gere o QR Code
2. Use o app do Mercado Pago em modo sandbox
3. Escaneie o QR Code
4. Confirme o pagamento

## Passo 10: Produção (quando estiver pronto)

1. No painel do Mercado Pago
2. Vá em **Credenciais**
3. Ative o **Modo Produção**
4. Complete os dados fiscais da empresa
5. Copie as **Credenciais de produção**:
   - Public Key (prod)
   - Access Token (prod)
6. Atualize o `.env`:

```env
MERCADOPAGO_PUBLIC_KEY=APP_USR-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx
MERCADOPAGO_ACCESS_TOKEN=APP_USR-xxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxx
```

## ✅ Pronto!

Sistema de pagamento PIX integrado!

---

## 🔧 Troubleshooting

### Erro: "Invalid access token"
- Verifique se copiou o token completo
- Certifique-se de usar o token correto (teste/produção)

### QR Code não é gerado
- Verifique se payment_method_id é "pix"
- Confirme que o país é Brasil (BRL)

### Webhook não é chamado
- Verifique se a URL está acessível publicamente
- Teste com ngrok localmente
- Confira logs do Mercado Pago

### Pagamento não aprova em teste
- Use apenas os cartões de teste oficiais
- Verifique se está em modo sandbox

---

## 📊 Taxas Mercado Pago

- **PIX**: 0,99% por transação
- **Cartão de Crédito**: 4,99% + R$ 0,40
- **Cartão de Débito**: 3,99% + R$ 0,40

## 🔐 Segurança

- **NUNCA** commite as credenciais no código
- Use variáveis de ambiente
- Valide todos os webhooks
- Implemente rate limiting
- Registre todas as transações
