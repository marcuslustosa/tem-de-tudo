# Instruções para Deploy do Projeto "Tem de Tudo"

## Backend - Deploy no Railway

1. Crie uma conta gratuita em [Railway.app](https://railway.app).
2. Crie um novo projeto e conecte seu repositório Git com o código backend, ou faça upload manual do código.
3. Configure as variáveis de ambiente no Railway:
   - `MERCADO_PAGO_TOKEN` (se usar Mercado Pago)
   - `PAGSEGURO_TOKEN` (se usar PagSeguro)
   - `JWT_SECRET` (chave secreta para autenticação JWT)
4. O Railway detecta automaticamente o app Node.js e faz o deploy.
5. Após o deploy, copie a URL pública do backend gerada pelo Railway.

## Frontend - Hospedagem na Hostinger

1. Faça upload dos arquivos da pasta `frontend/` para o servidor da Hostinger via FTP ou gerenciador de arquivos.
2. No arquivo `frontend/js/app.js`, atualize a constante `API_BASE_URL` com a URL pública do backend no Railway.
3. Configure o domínio na Hostinger para apontar para o site estático hospedado.

## DNS e Domínio

- Configure o apontamento DNS do seu domínio para o servidor da Hostinger (frontend).
- Se desejar, configure subdomínios para o backend no Railway, usando CNAME ou redirecionamentos.

## Considerações Finais

- O backend no Railway é escalável e gerenciado, ideal para APIs Node.js.
- O frontend na Hostinger é rápido e simples para sites estáticos.
- Use HTTPS para segurança.
- Mantenha tokens e segredos seguros nas variáveis de ambiente do Railway.

---

Se precisar, posso ajudar com scripts de deploy, configuração de variáveis ou qualquer outra dúvida.
