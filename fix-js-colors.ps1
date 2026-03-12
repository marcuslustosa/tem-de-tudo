$file = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/js/spa-components.js"
$content = Get-Content $file -Raw
$content = $content -replace '#6F1AB6', '#9b59b6'
Set-Content -Path $file -Value $content -NoNewline
Write-Host "JavaScript atualizado!"
