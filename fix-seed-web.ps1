$file = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public/seed-web.php"
$content = Get-Content $file -Raw
$content = $content -replace '#6F1AB6', '#9b59b6'
$content = $content -replace '#5A1494', '#603863'
$content = $content -replace '#8B5CF6', '#8e44ad'
Set-Content -Path $file -Value $content -NoNewline
Write-Host "seed-web.php atualizado!"
