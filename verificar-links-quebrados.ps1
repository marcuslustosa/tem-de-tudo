$base = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$allFiles = Get-ChildItem "$base/*.html" | Select-Object -ExpandProperty Name
$brokenLinks = @()

Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $matches = [regex]::Matches($content, 'href="([^"]+\.html)"')
    foreach ($match in $matches) {
        $link = $match.Groups[1].Value
        if ($link -notmatch "^http" -and $link -notmatch "^#" -and $link -notmatch "^javascript" -and $link -notmatch "^tel:" -and $link -notmatch "^mailto:") {
            $fullPath = "$base/$link"
            if (-not (Test-Path $fullPath)) {
                $brokenLinks += "$($_.Name) -> $link"
            }
        }
    }
}

Write-Host "=== LINKS QUEBRADOS ENCONTRADOS ===" -ForegroundColor Red
$brokenLinks | ForEach-Object { Write-Host $_ -ForegroundColor Yellow }
Write-Host ""
Write-Host "Total: $($brokenLinks.Count) links quebrados" -ForegroundColor Cyan
