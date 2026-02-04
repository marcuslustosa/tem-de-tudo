# 📋 CHECKLIST - O QUE FALTA FAZER
**Data:** 04/02/2026  
**Status:** Sistema 85% funcional  
**Prioridade:** Crítico → Importante → Opcional

---

## 🔴 CRÍTICO (Fazer AGORA - 15 minutos)

### ✅ 1. Configurar Mailtrap (2 minutos)

**Status:** ⚠️ Código pronto, falta credenciais  
**Impacto:** E-mails não são enviados  
**Arquivos:** `backend/.env`

**Passo a passo:**
```bash
# 1. Criar conta gratuita
Acesse: https://mailtrap.io
Clique: Sign Up (ou use Google)
Confirme: E-mail

# 2. Copiar credenciais
Dashboard → My Inbox → SMTP Settings
Integração: Laravel 9+
Copiar: MAIL_USERNAME e MAIL_PASSWORD

# 3. Editar .env
Abrir: backend/.env
Localizar linhas 48-49:
MAIL_USERNAME=null
MAIL_PASSWORD=null

# 4. Substituir por:
MAIL_USERNAME=seu_username_aqui
MAIL_PASSWORD=sua_password_aqui

# 5. Testar
Acessar: http://127.0.0.1:8001/recuperar-senha.html
Digitar: cliente@teste.com
Verificar: Inbox no Mailtrap
```

**Resultado esperado:**
- ✅ E-mail com design roxo recebido
- ✅ Token visível no corpo
- ✅ Link de reset funcional

---

### ✅ 2. Testar QR Scanner (10 minutos + deploy)

**Status:** ⚠️ HTML criado, precisa HTTPS  
**Impacto:** Check-in por QR não funciona  
**Arquivos:** `backend/public/app-scanner.html`

**Passo a passo:**
```bash
# 1. Commit mudanças
cd C:\Users\X472795\Desktop\Projetos\tem-de-tudo
git add .
git commit -m "fix: Corrigir promoções + E-mail configurado"
git push origin main

# 2. Aguardar deploy (5-10 min)
Render.com detecta push
Executa build automático
Verifica logs em: https://dashboard.render.com

# 3. Testar no celular
Acessar: https://seu-app.onrender.com/app-scanner.html
Permitir: Acesso à câmera
Escanear: QR Code de empresa teste
Verificar: Pontos creditados

# 4. Gerar QR Code teste
Acessar: https://www.qr-code-generator.com
Inserir: {"empresa_id": 1, "tipo": "checkin"}
Baixar: QR Code
Imprimir: ou mostrar na tela
```

**Resultado esperado:**
- ✅ Câmera abre no celular
- ✅ QR Code é reconhecido
- ✅ Pontos creditados na conta
- ✅ Notificação de sucesso

---

## 🟡 IMPORTANTE (Fazer essa semana - 2-4 horas cada)

### 3. Firebase - Notificações Push

**Status:** ❌ Não configurado  
**Tempo estimado:** 2 horas  
**Complexidade:** Média

**Requisitos:**
- Conta Google
- Firebase Console
- Node.js instalado

**Passos:**
1. Criar projeto Firebase
2. Ativar Cloud Messaging
3. Baixar `firebase-adminsdk.json`
4. Colocar em `backend/storage/app/`
5. Adicionar no `.env`:
   ```env
   FIREBASE_CREDENTIALS=storage/app/firebase-adminsdk.json
   FCM_SERVER_KEY=sua_chave_aqui
   ```
6. Testar notificação de boas-vindas

**Arquivos afetados:**
- `backend/app/Services/FirebaseService.php`
- `backend/resources/views/welcome.html`
- `backend/public/firebase-messaging-sw.js`

---

### 4. MercadoPago - Pagamentos Premium

**Status:** ❌ Não integrado  
**Tempo estimado:** 3 horas  
**Complexidade:** Média

**Requisitos:**
- Conta MercadoPago (vendedor)
- Credenciais de produção

**Passos:**
1. Criar conta MercadoPago: https://www.mercadopago.com.br
2. Ir em: Seu negócio → Credenciais
3. Copiar: PUBLIC_KEY e ACCESS_TOKEN
4. Adicionar no `.env`:
   ```env
   MERCADOPAGO_PUBLIC_KEY=APP_USR-xxxx
   MERCADOPAGO_ACCESS_TOKEN=APP_USR-xxxx
   ```
5. Implementar em `app-premium.html`:
   ```javascript
   const mp = new MercadoPago('PUBLIC_KEY');
   const checkout = mp.checkout({
       preference: { id: 'preference_id' }
   });
   ```
6. Criar webhook para confirmação

**Preços sugeridos:**
- Premium Mensal: R$ 9,90
- Premium Anual: R$ 99,00 (2 meses grátis)

---

### 5. Google Maps - Geolocalização

**Status:** ❌ Não implementado  
**Tempo estimado:** 2 horas  
**Complexidade:** Baixa

**Requisitos:**
- Conta Google Cloud
- Cartão de crédito (R$ 1.500 grátis/mês)

**Passos:**
1. Criar projeto: https://console.cloud.google.com
2. Ativar APIs:
   - Maps JavaScript API
   - Geocoding API
   - Places API
3. Criar credencial (API Key)
4. Adicionar no `.env`:
   ```env
   GOOGLE_MAPS_API_KEY=AIzaSyXXXXXXXXXXXXX
   ```
5. Implementar em `app-empresas.html`:
   ```javascript
   const map = new google.maps.Map(document.getElementById('map'), {
       center: { lat: -23.550520, lng: -46.633308 },
       zoom: 12
   });
   ```
6. Adicionar marcadores de empresas
7. Filtro por distância (raio 5km)

---

### 6. Relatórios Admin - Dados Reais

**Status:** ⚠️ Dados mockados  
**Tempo estimado:** 4 horas  
**Complexidade:** Média

**Requisitos:**
- Chart.js (já incluído)
- Laravel Query Builder

**Tarefas:**
1. **Dashboard Stats:**
   ```php
   // AuthController@adminDashboard
   $stats = [
       'total_users' => User::count(),
       'total_empresas' => Empresa::count(),
       'total_pontos' => Ponto::sum('pontos'),
       'total_check_ins' => CheckIn::count()
   ];
   ```

2. **Gráfico de Check-ins (últimos 7 dias):**
   ```php
   $checkIns = CheckIn::where('created_at', '>=', now()->subDays(7))
       ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
       ->groupBy('date')
       ->get();
   ```

3. **Gráfico de Cupons por Empresa:**
   ```php
   $cupons = Cupom::join('empresas', 'cupons.empresa_id', '=', 'empresas.id')
       ->selectRaw('empresas.nome, COUNT(*) as total')
       ->groupBy('empresas.id')
       ->get();
   ```

4. **Exportar CSV:**
   ```php
   use Illuminate\Support\Facades\Response;
   
   public function exportCsv() {
       $data = User::all()->toArray();
       $csv = Writer::createFromString('');
       $csv->insertOne(array_keys($data[0]));
       $csv->insertAll($data);
       
       return Response::make($csv, 200, [
           'Content-Type' => 'text/csv',
           'Content-Disposition' => 'attachment; filename="users.csv"'
       ]);
   }
   ```

---

### 7. Bônus Aniversário - Teste

**Status:** ⚠️ API existe, não testado  
**Tempo estimado:** 30 minutos  
**Complexidade:** Baixa

**Passos:**
1. Cadastrar data de nascimento:
   ```bash
   cd backend
   php artisan tinker
   $user = User::find(2);
   $user->data_nascimento = now()->format('Y-m-d');
   $user->save();
   ```

2. Testar endpoint:
   ```bash
   curl -X POST http://127.0.0.1:8001/api/cliente/resgatar-bonus-aniversario \
       -H "Authorization: Bearer SEU_TOKEN" \
       -H "Content-Type: application/json"
   ```

3. Verificar pontos creditados:
   ```bash
   php artisan tinker
   User::find(2)->pontos;  # Deve ter +500
   ```

4. Implementar cronjob diário:
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       $schedule->call(function () {
           $hoje = now()->format('m-d');
           $users = User::whereRaw("DATE_FORMAT(data_nascimento, '%m-%d') = ?", [$hoje])->get();
           
           foreach ($users as $user) {
               $user->pontos += 500;
               $user->save();
               
               // Enviar e-mail parabenizando
               Mail::to($user->email)->send(new BirthdayBonus());
           }
       })->dailyAt('08:00');
   }
   ```

---

## 🟢 OPCIONAL (Backlog - Próximas sprints)

### 8. Gamificação (8 horas)
- [ ] Sistema de conquistas (badges)
- [ ] Ranking de clientes (leaderboard)
- [ ] Desafios semanais
- [ ] Níveis VIP personalizados
- [ ] Prêmios especiais

**Tecnologias:** Laravel Events, Cache Redis, Vue.js

---

### 9. Chat Suporte (12 horas)
- [ ] Socket.io para tempo real
- [ ] Bot de respostas automáticas
- [ ] Histórico de conversas
- [ ] Atendentes online
- [ ] Avaliação do atendimento

**Tecnologias:** Laravel Echo, Socket.io, Redis

---

### 10. Relatórios PDF (4 horas)
- [ ] Extrato de pontos em PDF
- [ ] Cupons imprimíveis
- [ ] Relatórios empresariais
- [ ] Nota fiscal de compra
- [ ] Comprovante de resgate

**Tecnologias:** DomPDF, Blade Templates

---

### 11. Sync Offline (6 horas)
- [ ] LocalStorage avançado
- [ ] IndexedDB para grandes dados
- [ ] Fila de ações offline
- [ ] Sync automático ao voltar online
- [ ] Resolução de conflitos

**Tecnologias:** Service Worker, IndexedDB API

---

### 12. Performance (10 horas)
- [ ] Cache Redis para queries
- [ ] CDN para assets (Cloudflare)
- [ ] Lazy loading de imagens
- [ ] Minificação de JS/CSS
- [ ] Gzip compression
- [ ] Database indexing

**Tecnologias:** Redis, Laravel Cache, Webpack

---

### 13. Autenticação 2FA (6 horas)
- [ ] Google Authenticator
- [ ] SMS com Twilio
- [ ] E-mail com código
- [ ] Backup codes
- [ ] Gerenciamento de dispositivos

**Tecnologias:** Laravel Fortify, Twilio API

---

## 📊 ESTATÍSTICAS ATUAIS

| Item | Quantidade | Status |
|------|------------|--------|
| **Usuários** | 53 | ✅ OK |
| **Empresas** | 8 | ✅ OK |
| **Promoções** | 19 | ✅ OK |
| **Pontos** | 180 transações | ✅ OK |
| **Cupons** | 160 | ✅ OK |
| **QR Codes** | 24 | ✅ OK |
| **Check-ins** | 244 | ✅ OK |
| **Páginas** | 28 | ✅ OK |

---

## 🎯 PRIORIZAÇÃO

### Fazer HOJE (04/02/2026):
1. ✅ Promoções - **CONCLUÍDO!**
2. ⏳ Credenciais Mailtrap - **15 min**
3. ⏳ Deploy + Testar QR - **30 min**

### Fazer SEMANA (05-11/02/2026):
4. Firebase Push - **2h**
5. Google Maps - **2h**
6. Relatórios Reais - **4h**
7. Teste Bônus Aniversário - **30 min**

### Fazer MÊS (Fevereiro):
8. MercadoPago Premium - **3h**
9. Gamificação básica - **8h**
10. Chat Suporte - **12h**

### Backlog (Março+):
11. Relatórios PDF
12. Sync Offline
13. Performance
14. 2FA

---

## ✅ VALIDAÇÃO DE CONCLUSÃO

Antes de marcar como concluído, verificar:

**Para Mailtrap:**
- [ ] E-mail recebido no inbox
- [ ] Template roxo carregando
- [ ] Link de reset funcional
- [ ] Token válido por 60 min

**Para QR Scanner:**
- [ ] Câmera abre no celular
- [ ] QR Code reconhecido
- [ ] Pontos creditados
- [ ] Histórico atualizado

**Para cada funcionalidade:**
- [ ] Código testado
- [ ] Logs sem erros
- [ ] Documentação atualizada
- [ ] Commit realizado

---

## 📞 SUPORTE

**Dúvidas?**
- Mailtrap: https://help.mailtrap.io
- Render: https://render.com/docs
- Firebase: https://firebase.google.com/docs
- MercadoPago: https://www.mercadopago.com.br/developers

**Logs de erro:**
```bash
# Laravel
tail -f backend/storage/logs/laravel.log

# Render
render logs -f

# Browser
F12 → Console
```
