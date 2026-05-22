# Fluxo de dados demo i9Plus

## Objetivo

O seeder `I9PlusDemoSeeder` prepara uma base de apresentacao para os tres perfis do PWA: admin/master, empresa e cliente.

## Comando para Railway

Execute no ambiente Railway quando a demo precisar ser restaurada ou quando um deploy novo subir sem dados:

```bash
php artisan migrate --force --no-interaction && php artisan db:seed --class=I9PlusDemoSeeder --force --no-interaction
```

## Acessos demo

- Admin: `admin@demo.local` / `password`
- Empresa: `malagueta@demo.local` / `password`
- Cliente: `joao@demo.local` / `password`
- Cliente para validar UX de push: `cliente.push@demo.local` / `password`

## O que a seed cria

- Empresas ativas para vitrine publica, incluindo Malagueta Galpao.
- Empresas pendentes e suspensas para o painel admin.
- Clientes vinculados a empresas por `inscricoes_empresa`.
- Promocoes ativas e historicas.
- Promocao `Teste de Push` para a Malagueta.
- Bonus de adesao, cartao fidelidade, bonus aniversario e lembrete de retorno.
- Avaliacoes e logs de notificacao enviados para demonstrar historico.

## Push real

A seed nao cria `push_subscriptions` falsas. O numero de clientes com push ativo depende do cliente abrir o PWA no navegador/dispositivo e ativar notificacoes.

No painel da empresa, o bloco "Notificacoes e campanhas" mostra:

- status do servidor de push vindo de `/api/push/public-key`;
- clientes vinculados vindos de `inscricoes_empresa`;
- clientes com push ativo vindos de `push_subscriptions`;
- promocoes ativas da empresa;
- ultimo envio real registrado em `notificacoes_push`.

## Onde validar por perfil

Admin/master:
- `/dashboard_admin_master.html`
- `/gest_o_de_estabelecimentos.html`
- `/gest_o_de_clientes_master.html`

Empresa:
- `/dashboard_parceiro.html`
- `/meu_perfil.html`
- `/gest_o_de_ofertas_parceiro.html`
- `/clientes_fidelizados_loja.html`

Cliente:
- `/meus_pontos.html`
- `/parceiros_tem_de_tudo.html`
- `/detalhe_do_parceiro.html?id=1`

## Diagnostico quando os dados nao aparecem

1. Confirme que o comando de migrate + seed foi executado no Railway.
2. Teste login com `malagueta@demo.local`.
3. Abra `/api/empresa/relatorios/resumo` autenticado como empresa e confira `cards.total_clientes_vinculados`.
4. Abra `/api/cliente/dashboard` autenticado como cliente e confira `empresas_vinculadas`.
5. Abra `/api/admin/relatorios/resumo` autenticado como admin e confira status de empresas e top empresas.

Se os endpoints retornarem vazio apos seed, o problema e API/dados. Se os endpoints retornarem dados mas a tela ficar vazia, o problema e renderizacao frontend.
