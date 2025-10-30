#!/bin/bash
set -e

echo "=== SCRIPT DE MIGRATIONS CRÍTICAS ==="
echo "$(date) - Iniciando..."

cd /var/www/html

# Criar tabela sessions diretamente via SQL
echo "Criando tabela sessions via SQL..."

# Conexão PostgreSQL
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE << 'SQL_SCRIPT'
-- Criar tabela sessions se não existir
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

-- Criar índices
CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions(user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions(last_activity);

-- Ajustar permissões
ALTER TABLE sessions OWNER TO tem_de_tudo_database_user;
SQL_SCRIPT

echo "✓ Tabela sessions criada com sucesso!"

# Listar tabelas para confirmar
echo "Tabelas no banco:"
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "\dt"

echo "Script concluído em $(date)"