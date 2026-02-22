# Fix massive HTML corruption - Remove "ãããoã" pattern from all HTML files
Write-Host "🚀 Corrigindo corrupção massiva..." -ForegroundColor Green

$publicDir = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$backupDir = "$publicDir\corrupted-backup"

# Create backup directory
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir
}

# Get all HTML files
$htmlFiles = Get-ChildItem -Path $publicDir -Filter "*.html" -Recurse

Write-Host "📊 Encontrados $($htmlFiles.Count) arquivos HTML" -ForegroundColor Cyan

foreach ($file in $htmlFiles) {
    try {
        Write-Host "🔧 Corrigindo: $($file.Name)" -ForegroundColor Yellow
        
        # Backup original
        $backupPath = Join-Path $backupDir $file.Name
        Copy-Item $file.FullName $backupPath -Force
        
        # Read with UTF-8
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        
        # Remove corruption pattern
        $correctedContent = $content -replace 'ãããoã', ''
        
        # Write back fixed content
        Set-Content -Path $file.FullName -Value $correctedContent -Encoding UTF8
        
        Write-Host "✅ Corrigido: $($file.Name)" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ Erro em $($file.Name): $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "🎉 Correção concluída!" -ForegroundColor Green
Write-Host "📋 Backup em: $backupDir" -ForegroundColor Cyan