# CLAUDE.md

Guia para trabalhar neste repositório. Edite à vontade.

## O que é o projeto

**Tem de Tudo** — plataforma de programa de fidelidade que conecta **clientes** e
**estabelecimentos (empresas)**. Cliente acumula/resgata pontos, bônus e benefícios;
empresa cria ofertas e valida no balcão; **admin master** aprova empresas e gerencia tudo.

## Stack e estrutura

- **Backend:** Laravel (PHP 8.2) em [backend/](backend/). API em [backend/routes/api.php](backend/routes/api.php).
- **Frontend:** páginas **HTML estáticas** + Tailwind (via CDN) em [backend/public/](backend/public/),
  com um bundle JS único: fonte [backend/public/js/stitch-app.js](backend/public/js/stitch-app.js)
  → servido minificado em [backend/public/dist/stitch-app.min.js](backend/public/dist/stitch-app.min.js).
  Todas as páginas carregam o **`/dist/stitch-app.min.js`**.
- **Banco:** local = **SQLite**; produção (Railway) = **PostgreSQL**.

## Comandos essenciais

```bash
# Setup local (sem isso, o app cai em pgsql+SSL e dá 500):
cp backend/.env.local backend/.env        # configura SQLite
cd backend && php artisan migrate --force # cria/atualiza tabelas
php artisan serve                          # sobe o servidor local

# Após editar o JS (public/js/stitch-app.js), SEMPRE regerar o bundle servido:
node --check public/js/stitch-app.js       # valida sintaxe
npx --yes terser public/js/stitch-app.js --compress --mangle --output public/dist/stitch-app.min.js --comments false

# Lint PHP antes de commitar:
php -l caminho/do/arquivo.php
```

## ⚠️ Gotchas críticos (PostgreSQL em produção)

O app foi desenvolvido para **SQLite/MySQL** (tolerantes) mas roda em **PostgreSQL**
(estrito) no Railway. Isso causa duas classes de bug que JÁ apareceram várias vezes:

1. **Boolean ≠ integer.** Enviar `1`/`0` ou PHP `true`/`false` (Laravel converte bool→int)
   para coluna `boolean` do pg estoura (`SQLSTATE 42804/42883`).
   - **Leitura (WHERE):** use os macros `whereTrue('col')` / `whereFalse('col')`
     (registrados em [AppServiceProvider](backend/app/Providers/AppServiceProvider.php)) em vez de `where('col', true)`.
   - **Escrita (INSERT/UPDATE via model):** use o trait
     [PgSafeBooleans](backend/app/Models/Concerns/PgSafeBooleans.php) no model (mutators
     gravam `'true'`/`'false'` no pg). NÃO usar em models cujo fluxo já converte manualmente
     (ex.: `User` no register) para evitar dupla conversão.
   - **Escrita via `DB::table()` ou mass-update:** use string pg-safe (`'true'`/`'false'`)
     ou `DB::raw('false')` — o trait/mutator NÃO é aplicado nesses casos.

2. **`LIKE` é case-sensitive no pg.** Busca por "sushi" não acha "Makoto Sushi".
   - Use `whereRaw('LOWER(col) LIKE ?', ['%'.mb_strtolower($termo).'%'])` em buscas de texto.

## Deploy (Railway)

- Push no `main` do GitHub → **deploy automático**.
- O **Pre-deploy Command** (nas Settings do serviço Railway) roda `php artisan migrate --force`
  (e seed). **Se ele falhar, o deploy inteiro falha** e a versão antiga continua no ar.
  → **Migrations novas devem ser à prova de falha** (envolver em `try/catch`), pois uma
  migration que quebra no pg derruba todos os deploys.
- Variáveis relevantes: `SEED_ON_START` (roda seed em todo deploy se `true`),
  `RUN_MIGRATIONS_ON_START`. Banco via `DATABASE_URL` / `DB_SSLMODE=prefer`.

## Convenções

- Mensagens de commit **nunca** levam linha de co-autoria (sem `Co-Authored-By` de IA).
- Frontend: textos em pt-BR **com acentuação correta**; cor da marca / magenta = **#b01774**
  (token `--i9-gradient` em [i9plus-phase8.css](backend/public/css/i9plus-phase8.css)).
- Models com mutator boolean próprio (não duplicar): `Promocao` (ativo), `NotificacaoPush`
  (enviado), `BonusAdesaoResgate` (resgatado), `InscricaoEmpresa`.
