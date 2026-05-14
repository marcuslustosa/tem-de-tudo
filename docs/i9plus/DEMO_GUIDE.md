# Demo Guide i9Plus

## Objetivo

Este guia prepara a apresentacao local/staging do PWA de fidelizacao com dados ficticios coerentes com as Fases 0 a 8.

## Seed demo

Rodar somente em ambiente Laravel com dependencias instaladas:

```bash
cd backend
php artisan db:seed --class=I9PlusDemoSeeder
```

Seeder criada:

- `backend/database/seeders/I9PlusDemoSeeder.php`

## Credenciais demo

Senha padrao de todos os usuarios demo:

- `password`

Admin:

- `admin@demo.local`

Empresas:

- `malagueta@demo.local`
- `texano@demo.local`
- `makoto@demo.local`
- `florenza@demo.local`
- `pendente@demo.local`
- `suspensa@demo.local`

Clientes:

- `joao@demo.local`
- `maria@demo.local`
- `pedro@demo.local`
- `ana@demo.local`

## Dados ficticios gerados

Empresas ativas:

- `Malagueta Galpao`
- `Texano Burger`
- `Makoto Sushi`
- `Florenza Boutique`

Empresas para governanca/admin:

- `Empresa Pendente Demo`
- `Empresa Suspensa Demo`

Clientes demo:

- `Joao Cliente Demo`
- `Maria Aniversariante`
- `Pedro Inativo`
- `Ana Fidelidade`

Conjuntos demonstrados pela seed:

- empresa publica ativa e empresas nao publicas por status;
- QR Code canonico da empresa gerado por `QRCodeService`;
- QR do cliente gerado sob demanda por `ClienteQrCodeService` no endpoint `GET /api/cliente/meu-qrcode`;
- vinculos cliente/empresa em `InscricaoEmpresa`;
- bonus de adesao ativos e um resgate validado presencialmente;
- cartoes fidelidade com cenarios de `0`, `5`, `14` e `16` pontos;
- promocoes com imagem placeholder interna e notificacoes fake;
- bonus aniversario ativo para empresas;
- Maria elegivel no mes atual;
- lembrete de retorno com Pedro como cliente inativo;
- avaliacoes com medias visiveis nas empresas;
- registros de notificacao sem depender de push real.

## URLs principais para a demo

- `/index.html`
- `/entrar.html`
- `/meus_pontos.html`
- `/parceiros_tem_de_tudo.html`
- `/dashboard_parceiro.html`
- `/validar_resgate.html`
- `/dashboard_admin_master.html`
- `/gest_o_de_estabelecimentos.html`
- `/relat_rios_gerais_master.html`

Rota publica de detalhe:

- abrir por listagem de parceiros ou usar `detalhe_do_parceiro.html?id=<ID_DA_EMPRESA>`

## Roteiro recomendado

1. Entrar como cliente com `joao@demo.local`.
2. Abrir `meus_pontos.html` e mostrar o QR do cliente.
3. Navegar para a pagina publica de `Malagueta Galpao`.
4. Mostrar bonus de adesao, cartao fidelidade, promocoes e avaliacoes.
5. Entrar como `maria@demo.local` para demonstrar o bonus aniversario do mes.
6. Entrar como `pedro@demo.local` para demonstrar lembrete/cliente inativo.
7. Entrar como empresa com `malagueta@demo.local`.
8. Abrir `validar_resgate.html` e demonstrar leitura do QR do cliente.
9. Mostrar validacao presencial de bonus, pontos, recompensa e promocao.
10. Abrir `dashboard_parceiro.html` e `clientes_fidelizados_loja.html` para relatorios e base de clientes.
11. Entrar como admin com `admin@demo.local`.
12. Abrir `gest_o_de_estabelecimentos.html` e mostrar empresa pendente e empresa suspensa.
13. Abrir `dashboard_admin_master.html` e `relat_rios_gerais_master.html`.

## Limitacoes conhecidas

- push real continua dependente de `VAPID_*`, subscription do navegador e ambiente configurado;
- a seed cria registros para demonstracao visual/operacional, mas nao dispara push real;
- o link publico atual so refletira estas mudancas depois de push/deploy;
- staging tecnico continua pendente ate rodar `composer install`, `php artisan migrate --pretend`, `php artisan route:list` e `php artisan test`.
