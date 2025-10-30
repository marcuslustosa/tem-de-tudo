#!/bin/bash
set -e

echo "=== SCRIPT DE MIGRATIONS CRÍTICAS ==="
echo "$(date) - Iniciando..."

cd /var/www/html

# Criar arquivo SQL temporário
cat > sessions.sql << 'SQLEND'
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
SQLEND

echo "Criando tabela sessions via SQL..."

# Exportar variáveis de ambiente PostgreSQL
export PGPASSWORD="$DB_PASSWORD"
export PGHOST="dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com"
export PGUSER="tem_de_tudo_database_user"
export PGDATABASE="tem_de_tudo_database"
export PGPORT="5432"

# Executar o arquivo SQL
psql -f sessions.sql

if [ $? -eq 0 ]; then
    echo "✓ Tabela sessions criada com sucesso!"
    
    # Listar tabelas para confirmar
    echo "Tabelas no banco:"
    psql -c "\dt"
    
    # Limpar arquivo temporário
    rm sessions.sql
else
    echo "❌ Erro ao criar tabela sessions"
    exit 1
fi

echo "Script concluído em $(date)"