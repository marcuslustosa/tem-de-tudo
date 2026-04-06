# 🚀 GUIA RÁPIDO - COMO RODAR O BUILD

## Pré-requisitos

Se Node.js não estiver instalado:

### Instalar Node.js (Windows)
1. Baixar: https://nodejs.org/en/download/
2. Escolher "LTS" (recomendado)
3. Executar instalador
4. Verificar: `node --version` e `npm --version`

## Executar Build de Assets

### Opção 1: PowerShell (Windows)
```powershell
cd C:\Users\X472795\Desktop\tem-de-tudo\tem-de-tudo\backend
.\build-assets.ps1
```

### Opção 2: Bash (Git Bash/WSL/Linux)
```bash
cd /c/Users/X472795/Desktop/tem-de-tudo/tem-de-tudo/backend
bash build-assets.sh
```

## O que o script faz

1. Instala `terser` globalmente (se não existir)
2. Minifica `public/js/stitch-app.js`
3. Gera `public/dist/stitch-app.min.js`
4. Mostra tamanho antes/depois e % de redução

## Exemplo de saída esperada

```
🔨 Iniciando build de assets...
📦 Minificando stitch-app.js...
✅ Minificado: 152340 bytes → 51234 bytes (66.37% redução)

✅ Build concluído! Use /dist/stitch-app.min.js em produção
```

## Usar versão minificada

### Substituir em TODAS as páginas HTML:

**ANTES:**
```html
<script src="/js/stitch-app.js?v=20260401-stab14"></script>
```

**DEPOIS:**
```html
<script src="/dist/stitch-app.min.js?v=20260406-prod"></script>
```

### Buscar e substituir em massa (PowerShell):

```powershell
cd public
Get-ChildItem *.html -Recurse | ForEach-Object {
    (Get-Content $_.FullName) `
        -replace '/js/stitch-app\.js\?v=20260401-stab14', '/dist/stitch-app.min.js?v=20260406-prod' |
    Set-Content $_.FullName
}
```

## Verificar resultado

```powershell
# Ver tamanho dos arquivos
Get-Item public/js/stitch-app.js, public/dist/stitch-app.min.js | 
    Select-Object Name, @{N='Size(KB)';E={[math]::Round($_.Length/1KB,2)}} | 
    Format-Table
```

## Troubleshooting

### Erro: "terser não é reconhecido"
```powershell
npm install -g terser
```

### Erro: "Impossível carregar arquivo .ps1"
```powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
```

### Erro: "Permission denied" (bash)
```bash
chmod +x build-assets.sh
bash build-assets.sh
```

---

**Última atualização:** 06/04/2026
