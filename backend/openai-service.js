#!/usr/bin/env node
/**
 * OpenAI Service - Tem de Tudo
 * Serviço Node.js para integração com OpenAI API
 */

require('dotenv').config();

const OpenAI = require('openai');

// Validação da API Key
if (!process.env.OPENAI_API_KEY) {
    console.error('❌ ERRO: OPENAI_API_KEY não configurada no .env');
    process.exit(1);
}

// Inicialização segura do cliente OpenAI
const openai = new OpenAI({
    apiKey: process.env.OPENAI_API_KEY,
});

// Função para gerar resposta de chat
async function generateChatResponse(prompt, context = null) {
    try {
        const messages = [
            {
                role: "system",
                content: "Você é um assistente virtual do sistema de fidelidade 'Tem de Tudo'. Ajude os usuários com informações sobre pontos, estabelecimentos e benefícios."
            }
        ];

        if (context) {
            messages.push({
                role: "system", 
                content: `Contexto adicional: ${context}`
            });
        }

        messages.push({
            role: "user",
            content: prompt
        });

        const response = await openai.chat.completions.create({
            model: "gpt-3.5-turbo",
            messages: messages,
            max_tokens: 500,
            temperature: 0.7,
        });

        return {
            success: true,
            message: response.choices[0].message.content,
            usage: response.usage
        };
    } catch (error) {
        console.error('Erro OpenAI:', error.message);
        return {
            success: false,
            error: error.message
        };
    }
}

// Função para gerar sugestões de estabelecimentos
async function generateEstablishmentSuggestions(userPreferences) {
    try {
        const prompt = `Com base nas preferências do usuário: ${userPreferences}, sugira 3 estabelecimentos que poderiam interessar. Responda apenas com nomes e uma breve descrição.`;

        return await generateChatResponse(prompt);
    } catch (error) {
        console.error('Erro sugestões:', error.message);
        return {
            success: false,
            error: error.message
        };
    }
}

// CLI Interface
async function main() {
    const args = process.argv.slice(2);
    
    if (args.length === 0) {
        console.log('Uso: node openai-service.js <comando> [argumentos]');
        console.log('Comandos:');
        console.log('  chat "sua pergunta"');
        console.log('  suggest "suas preferências"');
        console.log('  test');
        process.exit(1);
    }

    const command = args[0];

    switch (command) {
        case 'chat':
            if (args.length < 2) {
                console.error('❌ Erro: Forneça uma pergunta para o chat');
                process.exit(1);
            }
            const chatResult = await generateChatResponse(args[1]);
            console.log(JSON.stringify(chatResult));
            break;

        case 'suggest':
            if (args.length < 2) {
                console.error('❌ Erro: Forneça as preferências do usuário');
                process.exit(1);
            }
            const suggestResult = await generateEstablishmentSuggestions(args[1]);
            console.log(JSON.stringify(suggestResult));
            break;

        case 'test':
            console.log('🧪 Testando conexão com OpenAI...');
            const testResult = await generateChatResponse('Olá! Esta é uma mensagem de teste.');
            if (testResult.success) {
                console.log('✅ Teste bem-sucedido!');
                console.log('📝 Resposta:', testResult.message);
            } else {
                console.log('❌ Erro no teste:', testResult.error);
            }
            break;

        default:
            console.error('❌ Comando inválido:', command);
            process.exit(1);
    }
}

// Executar apenas se for chamado diretamente
if (require.main === module) {
    main().catch(error => {
        console.error('❌ Erro fatal:', error.message);
        process.exit(1);
    });
}

// Exportar funções para uso em outros módulos
module.exports = {
    generateChatResponse,
    generateEstablishmentSuggestions
};