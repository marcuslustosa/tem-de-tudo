# Instruções de Deploy para o Projeto Tem de Tudo

## Deploy no Railway
1. Crie uma conta no [Railway](https://railway.app/).
2. Crie um novo projeto e conecte seu repositório GitHub.
3. Configure as variáveis de ambiente no Railway (tokens de pagamento, JWT secret).
4. O Railway detecta o `package.json` e instala as dependências automaticamente.
5. Configure o comando de start: `node backend/app.js`.
6. Faça deploy e acesse a URL fornecida.

## Deploy no Hostinger
1. Faça upload dos arquivos via FTP ou Git.
2. Configure o Node.js no painel Hostinger.
3. Configure as variáveis de ambiente.
4. Defina o script de start para `node backend/app.js`.
5. Inicie a aplicação.
6. Configure o domínio para apontar para o servidor.

## Deploy no Vercel
1. Crie uma conta no [Vercel](https://vercel.com/).
2. Importe o repositório do projeto.
3. Configure as variáveis de ambiente no painel do Vercel.
4. Configure o build command (se necessário) e o start command: `node backend/app.js`.
5. Certifique-se que o backend serve os arquivos estáticos do frontend para evitar erro 404.
6. Faça deploy e acesse a URL gerada.

## Observações
- Sempre configure as variáveis de ambiente para tokens e segredos.
- Teste a aplicação localmente antes do deploy.
- Use o arquivo `Procfile` para facilitar deploys em plataformas que o suportam.
- Para o frontend, o backend serve os arquivos estáticos para facilitar o deploy unificado.
