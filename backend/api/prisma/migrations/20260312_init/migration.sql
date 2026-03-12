-- Enable extension for UUID generation
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Users
CREATE TABLE "User" (
    "id"           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "name"         TEXT NOT NULL,
    "email"        TEXT NOT NULL UNIQUE,
    "passwordHash" TEXT NOT NULL,
    "role"         TEXT NOT NULL CHECK ("role" IN ('admin','company','customer')),
    "createdAt"    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Companies
CREATE TABLE "Company" (
    "id"          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "name"        TEXT NOT NULL,
    "themeColor"  TEXT,
    "createdAt"   TIMESTAMPTZ NOT NULL DEFAULT now(),
    "ownerUserId" UUID NOT NULL REFERENCES "User"("id") ON DELETE CASCADE
);

-- Stores
CREATE TABLE "Store" (
    "id"         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "name"       TEXT NOT NULL,
    "address"    TEXT,
    "city"       TEXT,
    "latitude"   DOUBLE PRECISION,
    "longitude"  DOUBLE PRECISION,
    "createdAt"  TIMESTAMPTZ NOT NULL DEFAULT now(),
    "companyId"  UUID NOT NULL REFERENCES "Company"("id") ON DELETE CASCADE
);

-- Loyalty accounts
CREATE TABLE "LoyaltyAccount" (
    "id"              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "pointsBalance"   INTEGER NOT NULL DEFAULT 0,
    "tier"            TEXT,
    "createdAt"       TIMESTAMPTZ NOT NULL DEFAULT now(),
    "customerUserId"  UUID NOT NULL REFERENCES "User"("id") ON DELETE CASCADE,
    "companyId"       UUID NOT NULL REFERENCES "Company"("id") ON DELETE CASCADE,
    CONSTRAINT "user_company_unique" UNIQUE ("customerUserId","companyId")
);

-- Transactions
CREATE TABLE "Transaction" (
    "id"          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "type"        TEXT NOT NULL CHECK ("type" IN ('earn','redeem','bonus_signup','bonus_birthday')),
    "points"      INTEGER NOT NULL,
    "description" TEXT,
    "meta"        JSONB,
    "createdAt"   TIMESTAMPTZ NOT NULL DEFAULT now(),
    "accountId"   UUID NOT NULL REFERENCES "LoyaltyAccount"("id") ON DELETE CASCADE
);

-- Coupons
CREATE TABLE "Coupon" (
    "id"          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "title"       TEXT NOT NULL,
    "description" TEXT,
    "pointsCost"  INTEGER NOT NULL,
    "expiresAt"   TIMESTAMPTZ,
    "stock"       INTEGER,
    "createdAt"   TIMESTAMPTZ NOT NULL DEFAULT now(),
    "companyId"   UUID NOT NULL REFERENCES "Company"("id") ON DELETE CASCADE
);

-- Redemptions
CREATE TABLE "Redemption" (
    "id"          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "status"      TEXT NOT NULL CHECK ("status" IN ('reserved','used','cancelled')),
    "qrcodeToken" TEXT NOT NULL UNIQUE,
    "createdAt"   TIMESTAMPTZ NOT NULL DEFAULT now(),
    "usedAt"      TIMESTAMPTZ,
    "accountId"   UUID NOT NULL REFERENCES "LoyaltyAccount"("id") ON DELETE CASCADE,
    "couponId"    UUID NOT NULL REFERENCES "Coupon"("id") ON DELETE CASCADE
);

-- Notifications
CREATE TABLE "Notification" (
    "id"        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "title"     TEXT NOT NULL,
    "body"      TEXT NOT NULL,
    "readAt"    TIMESTAMPTZ,
    "createdAt" TIMESTAMPTZ NOT NULL DEFAULT now(),
    "userId"    UUID NOT NULL REFERENCES "User"("id") ON DELETE CASCADE
);

-- QR Codes
CREATE TABLE "QrCode" (
    "id"         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    "token"      TEXT NOT NULL UNIQUE,
    "payload"    JSONB,
    "expiresAt"  TIMESTAMPTZ,
    "redeemedAt" TIMESTAMPTZ,
    "createdAt"  TIMESTAMPTZ NOT NULL DEFAULT now(),
    "companyId"  UUID NOT NULL REFERENCES "Company"("id") ON DELETE CASCADE,
    "storeId"    UUID REFERENCES "Store"("id") ON DELETE SET NULL
);
