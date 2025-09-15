/**
 * companies.js - Rotas para gerenciamento de empresas
 * Segue padrões PSR-12 adaptados para JavaScript, código limpo e seguro.
 */

const express = require('express');
const router = express.Router();
const CompaniesController = require('../controllers/companiesController');

// Solicitar cadastro de empresa
router.post('/request', CompaniesController.requestCompany);

// Listar empresas (opcional filtro por aprovação)
router.get('/', CompaniesController.listCompanies);

// Confirmar pagamento da empresa
router.put('/confirm-payment/:id', CompaniesController.confirmPayment);

// Aprovar empresa (perfil master)
router.put('/approve/:id', CompaniesController.approveCompany);

module.exports = router;
