$file = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/dashboard-cliente.html"
$content = Get-Content $file -Raw
$content = $content -replace 'theme-color.*content="#6F1AB6"', 'theme-color content="#9b59b6"'
Set-Content -Path $file -Value $content -NoNewline
Write-Host "Theme color atualizado!"
