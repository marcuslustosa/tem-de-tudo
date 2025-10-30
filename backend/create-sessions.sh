#!/bin/bash
set -e

echo "=== SCRIPT DE MIGRATIONS CRÍTICAS ==="
echo "$(date) - Iniciando..."

cd /var/www/html

# Criar tabela sessions diretamente via SQL
echo "Criando tabela sessions via SQL..."

# Conexão PostgreSQL com valores explícitos
PGPASSWORD=$DB_PASSWORD psql \
  -h "dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com" \
  -U "tem_de_tudo_database_user" \
  -d "tem_de_tudo_database" \
  -p 5432 << 'SQL_SCRIPT'
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
PGPASSWORD=$DB_PASSWORD psql \
  -h "dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com" \
  -U "tem_de_tudo_database_user" \
  -d "tem_de_tudo_database" \
  -p 5432 \
  -c "\dt"

echo "Script concluído em $(date)"