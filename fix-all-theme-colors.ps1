$oldColor = "#6F1AB6"
$newColor = "#9b59b6"
$htmlFiles = Get-ChildItem -Path "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public" -Filter "*.html" -Recurse

$count = 0
foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match $oldColor) {
        $content = $content -replace $oldColor, $newColor
        Set-Content -Path $file.FullName -Value $content -NoNewline
        $count++
        Write-Host "Atualizado: $($file.Name)"
    }
}

Write-Host ""
Write-Host "Total de arquivos atualizados: $count"
