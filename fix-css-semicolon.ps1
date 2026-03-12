$file = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/css/vivo-styles-final.css"
$text = Get-Content $file -Raw
$text = $text -replace 'border-radius: 0 var\(-radius-lg\) var\(-radius-lg\);', 'border-radius: 0 var(--radius-lg) var(--radius-lg);' 
Set-Content -Path $file -Value $text -NoNewline
Write-Host "CSS corrigido com sucesso!"
