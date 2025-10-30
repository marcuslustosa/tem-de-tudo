FROM php:8.2-apache

# Instalar dependências e extensões PHP
RUN apt-get update
RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql
RUN a2enmod rewrite

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . .
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Configurar permissões e diretórios
RUN mkdir -p storage/framework/sessions
RUN mkdir -p storage/framework/views
RUN mkdir -p storage/framework/cache
RUN mkdir -p storage/logs
RUN mkdir -p bootstrap/cache
RUN chmod -R 777 storage
RUN chmod -R 777 bootstrap/cache
RUN chmod +x entrypoint.sh

# Expor porta
EXPOSE 80

# Definir script de entrada
ENTRYPOINT ["./entrypoint.sh"]