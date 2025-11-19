#!/usr/bin/env node
/**
 * Teste das APIs de Login e Cadastro
 */

const API_BASE = 'http://localhost:8000/api';

async function testAPI() {
    console.log('🧪 TESTANDO APIs de LOGIN e CADASTRO\n');
    
    // 1. Testar login de cliente existente
    console.log('1️⃣ Testando login de cliente...');
    try {
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'cliente@teste.com',
                password: '123456'
            })
        });

        const loginResult = await response.json();
        
        if (response.ok && loginResult.success) {
            console.log('   ✅ Login de cliente funcionando!');
            console.log(`   📱 Token: ${loginResult.data.token.substring(0, 20)}...`);
            console.log(`   👤 Nome: ${loginResult.data.user.name}`);
            console.log(`   🎯 Perfil: ${loginResult.data.user.perfil}`);
            console.log(`   🔗 Redirect: ${loginResult.data.redirect_to}`);
        } else {
            console.log('   ❌ Erro no login:', loginResult.message);
            console.log('   📝 Detalhes:', loginResult);
        }
        
    } catch (error) {
        console.log('   ❌ Erro na requisição:', error.message);
    }

    console.log('\n' + '━'.repeat(50) + '\n');

    // 2. Testar login de empresa
    console.log('2️⃣ Testando login de empresa...');
    try {
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'empresa@teste.com',
                password: '123456'
            })
        });

        const loginResult = await response.json();
        
        if (response.ok && loginResult.success) {
            console.log('   ✅ Login de empresa funcionando!');
            console.log(`   📱 Token: ${loginResult.data.token.substring(0, 20)}...`);
            console.log(`   🏪 Nome: ${loginResult.data.user.name}`);
            console.log(`   🎯 Perfil: ${loginResult.data.user.perfil}`);
            console.log(`   🔗 Redirect: ${loginResult.data.redirect_to}`);
        } else {
            console.log('   ❌ Erro no login:', loginResult.message);
            console.log('   📝 Detalhes:', loginResult);
        }
        
    } catch (error) {
        console.log('   ❌ Erro na requisição:', error.message);
    }

    console.log('\n' + '━'.repeat(50) + '\n');

    // 3. Testar cadastro de novo cliente
    console.log('3️⃣ Testando cadastro de novo cliente...');
    try {
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: 'Cliente Teste',
                email: `cliente.teste.${Date.now()}@exemplo.com`,
                password: '123456',
                password_confirmation: '123456',
                perfil: 'cliente',
                telefone: '11999887766'
            })
        });

        const registerResult = await response.json();
        
        if (response.ok && registerResult.success) {
            console.log('   ✅ Cadastro de cliente funcionando!');
            console.log(`   📱 Token: ${registerResult.data.token.substring(0, 20)}...`);
            console.log(`   👤 Nome: ${registerResult.data.user.name}`);
            console.log(`   📧 Email: ${registerResult.data.user.email}`);
            console.log(`   🎯 Perfil: ${registerResult.data.user.perfil}`);
            console.log(`   🔗 Redirect: ${registerResult.data.redirect_to}`);
        } else {
            console.log('   ❌ Erro no cadastro:', registerResult.message);
            console.log('   📝 Detalhes:', registerResult);
        }
        
    } catch (error) {
        console.log('   ❌ Erro na requisição:', error.message);
    }

    console.log('\n' + '━'.repeat(50) + '\n');

    // 4. Testar cadastro de nova empresa
    console.log('4️⃣ Testando cadastro de nova empresa...');
    try {
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: 'Empresa Teste Ltda',
                email: `empresa.teste.${Date.now()}@exemplo.com`,
                password: '123456',
                password_confirmation: '123456',
                perfil: 'empresa',
                telefone: '1133334444',
                cnpj: '12.345.678/0001-95',
                endereco: 'Rua Teste, 123 - São Paulo/SP'
            })
        });

        const registerResult = await response.json();
        
        if (response.ok && registerResult.success) {
            console.log('   ✅ Cadastro de empresa funcionando!');
            console.log(`   📱 Token: ${registerResult.data.token.substring(0, 20)}...`);
            console.log(`   🏪 Nome: ${registerResult.data.user.name}`);
            console.log(`   📧 Email: ${registerResult.data.user.email}`);
            console.log(`   🎯 Perfil: ${registerResult.data.user.perfil}`);
            console.log(`   🔗 Redirect: ${registerResult.data.redirect_to}`);
        } else {
            console.log('   ❌ Erro no cadastro:', registerResult.message);
            console.log('   📝 Detalhes:', registerResult);
        }
        
    } catch (error) {
        console.log('   ❌ Erro na requisição:', error.message);
    }

    console.log('\n' + '━'.repeat(50) + '\n');

    // 5. Testar status da API
    console.log('5️⃣ Testando status da API...');
    try {
        const response = await fetch(`${API_BASE}/debug`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const debugResult = await response.json();
        
        if (response.ok && debugResult.status === 'OK') {
            console.log('   ✅ API funcionando!');
            console.log(`   🗄️ Banco: ${debugResult.database.status}`);
            console.log(`   🌍 Ambiente: ${debugResult.environment}`);
        } else {
            console.log('   ❌ Problema na API:', debugResult.message);
        }
        
    } catch (error) {
        console.log('   ❌ Erro na requisição:', error.message);
    }

    console.log('\n🎯 RESUMO DOS TESTES:');
    console.log('━'.repeat(50));
    console.log('✅ Se todos os testes passaram, o sistema está funcionando!');
    console.log('🔗 Acesse: http://localhost:8000');
    console.log('📱 Use os endpoints /api/auth/login e /api/auth/register');
    console.log('🛠️ Logs detalhados estão em storage/logs/laravel.log');
}

// Verificar se o servidor está rodando
async function checkServer() {
    try {
        const response = await fetch(`${API_BASE}/debug`);
        if (!response.ok) {
            throw new Error('Servidor não responde');
        }
        return true;
    } catch (error) {
        console.log('❌ ERRO: Servidor Laravel não está rodando!');
        console.log('💡 Execute: php artisan serve --host=0.0.0.0 --port=8000');
        return false;
    }
}

// Executar testes
(async () => {
    const serverRunning = await checkServer();
    if (serverRunning) {
        await testAPI();
    }
})();