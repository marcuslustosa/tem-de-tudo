$files = Get-ChildItem -Path "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public" -Filter "*.html"
$missing = @()
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    if ($content -notmatch "vivo-styles\.css") {
        $missing += $file.Name
    }
}
Write-Host "Arquivos sem vivo-styles.css:"
foreach ($m in $missing) {
    Write-Host "  - $m"
}
Write-Host ""
Write-Host "Total: $($missing.Count) arquivos"
