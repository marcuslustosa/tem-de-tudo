$base = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$files = Get-ChildItem "$base/*.html" -Recurse

$count = 0
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match 'theme-color.*content="#6A0DAD"') {
        $newContent = $content -replace 'theme-color.*content="#6A0DAD"', 'theme-color content="#9b59b6"'
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        Write-Host "Corrigido: $($file.Name)" -ForegroundColor Green
        $count++
    }
}

Write-Host ""
Write-Host "Total de arquivos corrigidos: $count" -ForegroundColor Cyan
