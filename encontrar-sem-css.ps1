# Script para encontrar arquivos HTML sem referência ao CSS
$files = Get-ChildItem -Path backend/public -Recurse -Filter "*.html" | Where-Object { (Get-Content $_.FullName -Raw) -notmatch "vivo-styles" }
foreach ($file in $files) {
    Write-Output $file.FullName
}
