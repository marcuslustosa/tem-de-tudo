$file = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/manifest.json"
$content = Get-Content $file -Raw
$content = $content -replace '#6F1AB6', '#9b59b6'
Set-Content -Path $file -Value $content -NoNewline
Write-Host "Manifest.json atualizado!"

$file2 = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/tdt-logo.svg"
$content2 = Get-Content $file2 -Raw
$content2 = $content2 -replace '#6F1AB6', '#9b59b6'
$content2 = $content2 -replace '#4A0E8C', '#603863'
Set-Content -Path $file2 -Value $content2 -NoNewline
Write-Host "Logo SVG atualizado!"
