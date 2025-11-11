# 🔧 CORREÇÕES CRÍTICAS - SISTEMA TEM DE TUDO

## 📋 PLANO DE CORREÇÕES

### 1. **CONFIGURAÇÕES E DEPENDÊNCIAS**
- [ ] Corrigir composer.json (JWT, outras dependências)
- [ ] Configurar .env.example com todas as variáveis necessárias
- [ ] Ajustar config/database.php para PostgreSQL completo
- [ ] Configurar JWT corretamente

### 2. **BANCO DE DADOS E MIGRAÇÕES**
- [ ] Corrigir migração principal (2024_01_01_000000_setup_database_structure.php)
- [ ] Criar migração para campos faltantes
- [ ] Ajustar tipos de dados para PostgreSQL
- [ ] Corrigir relacionamentos e chaves estrangeiras

### 3. **MODELOS (MODELS)**
- [ ] Corrigir User.php (campos, relacionamentos)
- [x] Corrigir Empresa.php (campos, métodos)
- [ ] Corrigir CheckIn.php (relacionamentos)
- [ ] Corrigir Ponto.php (relacionamentos)
- [ ] Corrigir Coupon.php (relacionamentos)
- [ ] Corrigir QRCode.php (relacionamentos)
- [ ] Corrigir DiscountLevel.php (relacionamentos)

### 4. **CONTROLLERS**
- [ ] Corrigir AuthController.php (campos, validações)
- [ ] Corrigir PontosController.php (métodos, campos)
- [ ] Corrigir QRCodeController.php (métodos)
- [ ] Corrigir DiscountController.php (métodos)
- [ ] Corrigir EmpresaController.php (métodos)
- [ ] Corrigir AdminReportController.php (métodos)

### 5. **SEEDERS**
- [ ] Corrigir DatabaseSeeder.php (campos corretos)
- [ ] Criar seeders para empresas e dados iniciais

### 6. **SERVICES**
- [ ] Corrigir NotificationService.php
- [ ] Corrigir FirebaseNotificationService.php

### 7. **TESTES E DEPLOY**
- [ ] Testar migrations locais
- [ ] Testar seeders
- [ ] Verificar render.yaml
- [ ] Testar deploy no Render

## 🚨 PROBLEMAS CRÍTICOS IDENTIFICADOS

### **Migração Principal**
- Campo `users.type` deveria ser `users.role`
- Campo `empresas.name` deveria ser `empresas.nome`
- Campo `empresas.address` deveria ser `empresas.endereco`
- Campo `empresas.phone` deveria ser `empresas.telefone`
- Campo `admins.name` deveria ser `admins.nome`
- Campo `admins.phone` deveria ser `admins.telefone`
- Campo `admins.company` deveria ser `admins.empresa`
- Campo `admins.cnpj` deveria ser `admins.cnpj`
- Campo `admins.permissions` deveria ser `admins.permissoes`
- Campo `admins.created_by` deveria ser `admins.criado_por`
- Campo `admins.status` deveria ser `admins.status`

### **Campos Faltantes**
- `users.telefone`
- `users.status`
- `empresas.points_multiplier`
- `check_ins.qr_code_id`
- `check_ins.bonus_applied`
- `coupons.dados_extra`
- `qr_codes.name`
- `qr_codes.location`
- `qr_codes.active_offers`
- `qr_codes.usage_count`
- `qr_codes.last_used_at`

### **Relacionamentos Quebrados**
- User -> Empresa (falta)
- CheckIn -> QRCode (falta)
- Ponto -> Coupon (falta)

### **Controllers com Campos Errados**
- AuthController usa `phone` ao invés de `telefone`
- PontosController usa campos que não existem
- QRCodeController usa campos incorretos

## ✅ STATUS ATUAL
- [x] Análise completa do projeto
- [x] Correções iniciadas
- [x] Migração principal corrigida (campos users, check_ins)
- [x] Modelo Admin corrigido (campos e relacionamentos)
- [x] AuthController corrigido (validações e campos)
- [x] PontosController corrigido (QRCode import e campos)
- [ ] Testes pendentes
- [ ] Deploy pendente
