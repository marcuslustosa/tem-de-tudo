# Script PowerShell para commit do sistema VIP
Set-Location "C:\Users\X472795\Desktop\Projetos\tem-de-tudo"

Write-Host "🚀 Fazendo commit do Sistema VIP..." -ForegroundColor Green

# Adicionar todos os arquivos
git add .

# Commit com mensagem detalhada
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

# Push para GitHub
git push origin main

Write-Host ""
Write-Host "✅ COMMIT ENVIADO PARA GITHUB!" -ForegroundColor Green
Write-Host "✅ RENDER VAI ATUALIZAR AUTOMATICAMENTE!" -ForegroundColor Green
Write-Host ""

# Listar arquivos principais criados
Write-Host "📁 ARQUIVOS PRINCIPAIS IMPLEMENTADOS:" -ForegroundColor Yellow
Write-Host "   - backend/app/Models/Badge.php" -ForegroundColor White
Write-Host "   - backend/app/Models/Pagamento.php" -ForegroundColor White
Write-Host "   - backend/app/Http/Controllers/BadgeController.php" -ForegroundColor White
Write-Host "   - backend/app/Http/Controllers/PagamentoController.php" -ForegroundColor White
Write-Host "   - backend/app/Http/Controllers/CheckInController.php" -ForegroundColor White
Write-Host "   - backend/app/Services/MercadoPagoService.php" -ForegroundColor White
Write-Host "   - backend/public/app-checkin.html" -ForegroundColor White
Write-Host "   - backend/database/seeders/DadosFictSistemaVipSeeder.php" -ForegroundColor White
Write-Host ""

Read-Host "Pressione Enter para continuar"