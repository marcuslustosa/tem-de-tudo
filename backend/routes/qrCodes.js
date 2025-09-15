/**
 * qrCodes.js - Rotas para geração e validação de QR Codes
 */

const express = require('express');
const router = express.Router();
const qrCodesController = require('../controllers/qrCodesController');

// Gerar QR Code
router.post('/generate', qrCodesController.generateQRCode);

// Validar QR Code
router.post('/validate', qrCodesController.validateQRCode);

module.exports = router;
