/**
 * payments.js - Rotas para pagamentos de empresas anunciantes
 */

const express = require('express');
const router = express.Router();
const paymentsController = require('../controllers/paymentsController');

// Registrar pagamento
router.post('/', paymentsController.registerPayment);

// Atualizar status do pagamento
router.put('/:id/status', paymentsController.updatePaymentStatus);

module.exports = router;
