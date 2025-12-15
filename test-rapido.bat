@echo off
echo ========================================
echo  TESTE RAPIDO - SISTEMA TEM DE TUDO
echo ========================================
echo.

cd backend

echo [1/6] Instalando dependencias...
call composer install --no-interaction --prefer-dist
if %errorlevel% neq 0 (
    echo ERRO: Falha ao instalar dependencias
    pause
    exit /b 1
)
echo ✓ Dependencias instaladas
echo.

echo [2/6] Configurando ambiente...
if not exist .env (
    copy .env.example .env
    echo ✓ Arquivo .env criado
) else (
    echo ✓ Arquivo .env ja existe
)
echo.

echo [3/6] Gerando chave da aplicacao...
php artisan key:generate --force
echo ✓ Chave gerada
echo.

echo [4/6] Executando migrations...
php artisan migrate:fresh --force
if %errorlevel% neq 0 (
    echo ERRO: Falha nas migrations
    echo Verifique a conexao com o banco de dados em .env
    pause
    exit /b 1
)
echo ✓ Migrations executadas
echo.

echo [5/6] Populando banco de dados...
php artisan db:seed --force
if %errorlevel% neq 0 (
    echo ERRO: Falha nos seeders
    pause
    exit /b 1
)
echo ✓ Banco populado
echo.

echo [6/6] Otimizando sistema...
php artisan config:cache
php artisan route:cache
echo ✓ Sistema otimizado
echo.

echo ========================================
echo  SISTEMA PRONTO PARA USAR!
echo ========================================
echo.
echo CREDENCIAIS DE TESTE:
echo.
echo Admin:
echo   Email: admin@temdetudo.com
echo   Senha: admin123
echo.
echo Cliente:
echo   Email: cliente@teste.com
echo   Senha: 123456
echo.
echo Empresa:
echo   Email: empresa@teste.com
echo   Senha: 123456
echo.
echo ========================================
echo Para iniciar o servidor, execute:
echo   php artisan serve
echo.
echo Depois acesse:
echo   http://localhost:8000/login.html
echo ========================================
echo.
pause
