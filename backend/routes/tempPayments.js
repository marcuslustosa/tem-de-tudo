const express = require('express');
const router = express.Router();
const { Payment } = require('../models');

// Endpoint temporário para listar pagamentos de uma empresa
router.get('/company/:companyId', async (req, res) => {
  try {
    const { companyId } = req.params;
    const payments = await Payment.findAll({
      where: { companyId },
      order: [['paymentDate', 'DESC']],
    });
    res.json(payments);
  } catch (error) {
    res.status(500).json({ error: 'Erro ao listar pagamentos' });
  }
});

module.exports = router;
