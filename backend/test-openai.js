#!/usr/bin/env node
/**
 * Teste de Integração OpenAI
 * Valida se a configuração está correta
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🧪 TESTE DE INTEGRAÇÃO OPENAI - TEM DE TUDO\n');

// 1. Verificar se Node.js está funcionando
console.log('1️⃣ Verificando Node.js...');
try {
    const nodeVersion = execSync('node --version', { encoding: 'utf8' }).trim();
    console.log(`   ✅ Node.js: ${nodeVersion}`);
} catch (error) {
    console.log('   ❌ Node.js não encontrado');
    process.exit(1);
}

// 2. Verificar se npm está funcionando
console.log('\n2️⃣ Verificando NPM...');
try {
    const npmVersion = execSync('npm --version', { encoding: 'utf8' }).trim();
    console.log(`   ✅ NPM: ${npmVersion}`);
} catch (error) {
    console.log('   ❌ NPM não encontrado');
    process.exit(1);
}

// 3. Verificar se o pacote OpenAI está instalado
console.log('\n3️⃣ Verificando pacote OpenAI...');
try {
    const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    if (packageJson.dependencies && packageJson.dependencies.openai) {
        console.log(`   ✅ OpenAI instalado: ${packageJson.dependencies.openai}`);
    } else {
        console.log('   ❌ Pacote OpenAI não encontrado no package.json');
        console.log('   💡 Execute: npm install openai');
        process.exit(1);
    }
} catch (error) {
    console.log('   ❌ Erro lendo package.json:', error.message);
    process.exit(1);
}

// 4. Verificar se o serviço OpenAI existe
console.log('\n4️⃣ Verificando serviço OpenAI...');
const servicePath = './openai-service.js';
if (fs.existsSync(servicePath)) {
    console.log(`   ✅ Serviço encontrado: ${servicePath}`);
} else {
    console.log(`   ❌ Serviço não encontrado: ${servicePath}`);
    process.exit(1);
}

// 5. Verificar .env
console.log('\n5️⃣ Verificando configurações .env...');
const envPath = './.env';
if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf8');
    const hasOpenAIKey = envContent.includes('OPENAI_API_KEY=') && 
                        !envContent.includes('OPENAI_API_KEY=\n') &&
                        !envContent.includes('OPENAI_API_KEY=""');
    
    if (hasOpenAIKey) {
        console.log('   ✅ OPENAI_API_KEY configurada no .env');
    } else {
        console.log('   ❌ OPENAI_API_KEY não configurada ou vazia no .env');
        console.log('   💡 Adicione: OPENAI_API_KEY=sk-...');
    }
} else {
    console.log('   ⚠️ Arquivo .env não encontrado');
    console.log('   💡 Crie um arquivo .env baseado no .env.example');
}

// 6. Teste básico do serviço (sem fazer chamada para API)
console.log('\n6️⃣ Testando carregamento do serviço...');
try {
    // Testar apenas se o arquivo pode ser carregado
    const serviceContent = fs.readFileSync(servicePath, 'utf8');
    if (serviceContent.includes('require(\'openai\')') || serviceContent.includes('OpenAI')) {
        console.log('   ✅ Serviço OpenAI parece estar correto');
    } else {
        console.log('   ❌ Serviço OpenAI malformado');
    }
} catch (error) {
    console.log('   ❌ Erro carregando serviço:', error.message);
}

// 7. Teste do controller PHP
console.log('\n7️⃣ Verificando controller PHP...');
const controllerPath = './app/Http/Controllers/OpenAIController.php';
if (fs.existsSync(controllerPath)) {
    console.log('   ✅ OpenAIController.php encontrado');
} else {
    console.log('   ❌ OpenAIController.php não encontrado');
}

// 8. Verificar rotas
console.log('\n8️⃣ Verificando rotas API...');
const routesPath = './routes/api.php';
if (fs.existsSync(routesPath)) {
    const routesContent = fs.readFileSync(routesPath, 'utf8');
    if (routesContent.includes('OpenAIController')) {
        console.log('   ✅ Rotas OpenAI configuradas');
    } else {
        console.log('   ❌ Rotas OpenAI não encontradas');
    }
} else {
    console.log('   ❌ Arquivo de rotas não encontrado');
}

console.log('\n🎯 RESUMO DO TESTE:');
console.log('━'.repeat(50));
console.log('Para testar completamente a integração:');
console.log('');
console.log('1. Configure sua API key no .env:');
console.log('   OPENAI_API_KEY=sk-sua-chave-aqui');
console.log('');
console.log('2. Teste o serviço Node.js:');
console.log('   node openai-service.js test');
console.log('');
console.log('3. Teste via Laravel/PHP:');
console.log('   GET /api/admin/openai/status');
console.log('   GET /api/admin/openai/test');
console.log('');
console.log('4. Teste chat:');
console.log('   POST /api/admin/openai/chat');
console.log('   Body: {"message": "Olá!"}');
console.log('');
console.log('✅ Se você configurou a API key, tudo deve funcionar!');