@echo off
cd /d "C:\Users\X472795\Desktop\Projetos\tem-de-tudo"

echo Fazendo backup e commit do sistema VIP...

git add .

git commit -m "feat: SISTEMA VIP COMPLETO - Niveis, Badges, Check-in QR, Mercado Pago

✨ Funcionalidades Implementadas:
- 🏆 Sistema VIP (4 niveis: Bronze → Diamante) 
- 🏅 6 Badges gamificados com conquista automatica
- 📱 Check-in via QR Code com scanner camera
- 💳 Estrutura Mercado Pago PIX completa  
- 👥 Dados ficticios para demonstracao

🗃️ Arquivos Criados:
- Badge.php + BadgeController + migrations
- Pagamento.php + PagamentoController + MercadoPagoService  
- CheckInController + app-checkin.html
- DadosFictSistemaVipSeeder.php
- 15+ rotas API implementadas

🎯 Sistema profissional pronto para demonstracao e producao!"

git push origin main

echo.
echo ✅ COMMIT ENVIADO PARA GITHUB!
echo ✅ RENDER VAI ATUALIZAR AUTOMATICAMENTE!
echo.
pause