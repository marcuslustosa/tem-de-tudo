-- ============================================
-- VERIFICAR TODAS AS TABELAS DO BANCO
-- ============================================

-- 1. LISTAR TODAS AS TABELAS
SELECT 
    table_name,
    pg_size_pretty(pg_total_relation_size(quote_ident(table_name)::regclass)) as tamanho
FROM information_schema.tables
WHERE table_schema = 'public' 
AND table_type = 'BASE TABLE'
ORDER BY table_name;

-- 2. CONTAR REGISTROS EM CADA TABELA
SELECT 
    schemaname,
    tablename,
    n_live_tup as registros
FROM pg_stat_user_tables
ORDER BY tablename;

-- 3. VERIFICAR TABELAS ESSENCIAIS
SELECT 
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users') THEN '✅' 
        ELSE '❌' 
    END as users,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'empresas') THEN '✅' 
        ELSE '❌' 
    END as empresas,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'pontos') THEN '✅' 
        ELSE '❌' 
    END as pontos,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'qr_codes') THEN '✅' 
        ELSE '❌' 
    END as qr_codes,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'promocoes') THEN '✅' 
        ELSE '❌' 
    END as promocoes,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'avaliacoes') THEN '✅' 
        ELSE '❌' 
    END as avaliacoes,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'bonus_adesao') THEN '✅' 
        ELSE '❌' 
    END as bonus_adesao,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'cartao_fidelidades') THEN '✅' 
        ELSE '❌' 
    END as cartao_fidelidade;

-- 4. VERIFICAR USUÁRIOS CRIADOS
SELECT 
    id,
    name,
    email,
    perfil,
    status,
    pontos_totais,
    nivel,
    created_at
FROM users
ORDER BY perfil, id
LIMIT 20;

-- 5. VERIFICAR SE TEM DADOS DE TESTE
SELECT 
    'ADMIN' as tipo,
    COUNT(*) as total
FROM users 
WHERE perfil = 'admin'
UNION ALL
SELECT 
    'CLIENTES' as tipo,
    COUNT(*) as total
FROM users 
WHERE perfil = 'cliente'
UNION ALL
SELECT 
    'EMPRESAS' as tipo,
    COUNT(*) as total
FROM users 
WHERE perfil = 'empresa';

-- 6. LISTAR ESTRUTURA DA TABELA USERS
SELECT 
    column_name,
    data_type,
    character_maximum_length,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'users'
ORDER BY ordinal_position;

-- 7. VERIFICAR MIGRATIONS EXECUTADAS
SELECT 
    id,
    migration,
    batch
FROM migrations
ORDER BY id DESC
LIMIT 30;

-- ============================================
-- RESULTADO ESPERADO
-- ============================================

/*
TABELAS ESSENCIAIS (mínimo):
✅ users
✅ empresas  
✅ pontos
✅ qr_codes
✅ promocoes
✅ avaliacoes
✅ bonus_adesao
✅ cartao_fidelidades
✅ inscricoes_empresa
✅ personal_access_tokens
✅ migrations

USUÁRIOS DE TESTE:
- 1 admin
- 5 clientes
- 5 empresas

Total esperado: 11 usuários
*/
