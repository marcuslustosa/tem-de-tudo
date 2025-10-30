FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . .
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

RUN chmod +x entrypoint.sh

ENTRYPOINT ["./entrypoint.sh"]
# Executar migrations\n\
echo "Executando migrations..."\n\
LARAVEL_ENV=production php artisan migrate --force --verbose\n\
\n\
# Seeds essenciais\n\
echo "Executando seeds essenciais..."\n\
LARAVEL_ENV=production php artisan db:seed --force --class=AdminUserSeeder\n\
\n\
# Verificar tabelas\n\
echo "=== Verificando Tabelas Criadas ==="\n\
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "\dt" || echo "Erro listando tabelas"\n\
\n\
echo "Script concluído em $(date)"\n\
MIGRATION_SCRIPT\n\
\n\
chmod +x /var/www/html/run-migrations.sh\n\
\n\
log "========================================"\n\
\n\
# Verificar e ajustar permissões e diretórios\n\
log "Verificando diretórios e permissões..."\n\
\n\
DIRS_TO_CHECK=(\n\
    "/var/www/html/storage"\n\
    "/var/www/html/storage/framework"\n\
    "/var/www/html/storage/framework/views"\n\
    "/var/www/html/storage/framework/cache"\n\
    "/var/www/html/storage/framework/sessions"\n\
    "/var/www/html/storage/logs"\n\
    "/var/www/html/bootstrap/cache"\n\
    "/var/www/html/public"\n\
)\n\
\n\
for dir in "${DIRS_TO_CHECK[@]}"; do\n\
    if [ ! -d "$dir" ]; then\n\
        log "Criando diretório: $dir"\n\
        mkdir -p "$dir"\n\
    fi\n\
    log "Ajustando permissões: $dir"\n\
    chown -R www-data:www-data "$dir"\n\
    chmod -R 775 "$dir"\n\
done\n\
\n\
# Verificar permissões\n\
log "Verificando permissões finais..."\n\
ls -la /var/www/html/storage\n\
ls -la /var/www/html/bootstrap/cache\n\
\n\
# Ajustar permissões finais\n\
log "Ajustando permissões finais..."\n\
find /var/www/html/storage -type d -exec chmod 775 {} \\; 2>/dev/null || true\n\
find /var/www/html/storage -type f -exec chmod 664 {} \\; 2>/dev/null || true\n\
\n\
# Criar arquivo de status\n\
mkdir -p /var/www/html/storage/logs\n\
echo "$(date) - Ambiente pronto" > /var/www/html/storage/logs/startup.log 2>/dev/null || true\n\
\n\
log "=== Iniciando Apache e Migrations em Background ==="\n\
log "Aplicação disponível na porta 80"\n\
\n\
# Rodar migrations em background\n\
/var/www/html/run-migrations.sh > /var/www/html/storage/logs/migrations.log 2>&1 &\n\
\n\
# Iniciar Apache\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]