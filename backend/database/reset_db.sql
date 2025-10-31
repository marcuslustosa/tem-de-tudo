DO $$ 
DECLARE 
    _sql text;
BEGIN
    -- Desabilita triggers
    SET session_replication_role = 'replica';
    
    -- Drop todas as views
    FOR _sql IN (SELECT 'DROP VIEW IF EXISTS ' || quote_ident(schemaname) || '.' || quote_ident(viewname) || ' CASCADE;'
                 FROM pg_views WHERE schemaname = 'public')
    LOOP
        EXECUTE _sql;
    END LOOP;

    -- Drop todas as tabelas
    FOR _sql IN (SELECT 'DROP TABLE IF EXISTS ' || quote_ident(schemaname) || '.' || quote_ident(tablename) || ' CASCADE;'
                 FROM pg_tables WHERE schemaname = 'public')
    LOOP
        EXECUTE _sql;
    END LOOP;

    -- Drop todas as sequences
    FOR _sql IN (SELECT 'DROP SEQUENCE IF EXISTS ' || quote_ident(schemaname) || '.' || quote_ident(sequencename) || ' CASCADE;'
                 FROM pg_sequences WHERE schemaname = 'public')
    LOOP
        EXECUTE _sql;
    END LOOP;

    -- Drop todos os tipos
    FOR _sql IN (SELECT 'DROP TYPE IF EXISTS ' || quote_ident(n.nspname) || '.' || quote_ident(t.typname) || ' CASCADE;'
                 FROM pg_type t JOIN pg_namespace n ON (t.typnamespace = n.oid)
                 WHERE n.nspname = 'public' AND t.typtype = 'c')
    LOOP
        EXECUTE _sql;
    END LOOP;

    -- Reabilita triggers
    SET session_replication_role = 'origin';
END $$;

-- Recria schema limpo
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO public;