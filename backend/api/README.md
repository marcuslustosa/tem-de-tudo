# API Tem de Tudo (Express + Prisma + PostgreSQL)

## Requisitos
- Node 18+
- Banco PostgreSQL (Railway)

## Configuração
1. Copie `.env.example` para `.env` e ajuste `DATABASE_URL`, `JWT_SECRET` e `PORT` (opcional).
2. Instale dependências: `npm install`.
3. Aplique migrations no Postgres: `prisma migrate deploy` (ou rode o SQL em `prisma/migrations/20260312_init/migration.sql`).
4. Rode seed opcional: `npm run seed`.

## Scripts
- `npm run dev` – servidor com nodemon
- `npm start` – servidor em produção
- `npm run prisma:migrate` – aplica migrations
- `npm run prisma:studio` – UI do Prisma
- `npm run seed` – popula admin/empresa/cliente demo

## Endpoints principais
- `GET /health`
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`
- `GET /companies` / `POST /companies` (admin)
- `GET /accounts`
- `POST /accounts/:id/earn` (admin/company)
- `POST /accounts/:id/redeem`
- `GET /transactions?accountId=...`
- `GET /coupons` / `POST /companies/:companyId/coupons`
- `POST /qrcode/scan` (admin/company)
- `GET/POST /notifications`, `POST /notifications/:id/read`
