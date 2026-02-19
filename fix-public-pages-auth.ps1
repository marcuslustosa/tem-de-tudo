# Script para remover auth inline de paginas de autenticacao (login, registro, etc)
$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "REMOVENDO AUTH INLINE DE PAGINAS DE AUTENTICACAO..." -ForegroundColor Cyan

# Paginas que NAO devem ter auth inline (sao paginas publicas)
$publicPages = @(
    "entrar.html",
    "entrar-novo.html", 
    "registro.html",
    "cadastro.html",
    "cadastro-novo.html",
    "recuperar-senha.html",
    "register-company.html",
    "register-admin.html",
    "selecionar-perfil.html",
    "index.html"
)

$contador = 0

foreach ($pageName in $publicPages) {
    $filePath = Join-Path $publicPath $pageName
    
    if (Test-Path $filePath) {
        $content = Get-Content $filePath -Raw -Encoding UTF8
        $originalContent = $content
        
        # Remover scripts de auth inline que redirecionam se NAO tem token
        $content = $content -replace '<script>\s*const token = localStorage\.getItem\(''tem_de_tudo_token''\);\s*if \(!token\) \{\s*window\.location\.href = ''[^'']+'';\s*\}\s*</script>\s*', ''
        
        if ($content -ne $originalContent) {
            $content | Set-Content $filePath -Encoding UTF8 -NoNewline
            $contador++
            Write-Host "OK: $pageName" -ForegroundColor Green
        }
    }
}

Write-Host "CONCLUIDO! $contador arquivos corrigidos" -ForegroundColor Green
