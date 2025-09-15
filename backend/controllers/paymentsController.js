/**
 * paymentsController.js - Controlador para pagamentos de empresas anunciantes
 */

const Payment = require('../models/Payment');

const registerPayment = async (req, res) => {
  try {
    const { companyId, amount, paymentMethod, transactionId } = req.body;
    if (!companyId || !amount) {
      return res.status(400).json({ error: 'Parâmetros insuficientes' });
    }
    const payment = await Payment.create({
      companyId,
      amount,
      paymentMethod: paymentMethod || null,
      transactionId: transactionId || null,
      status: 'pending',
    });
    return res.status(201).json(payment);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao registrar pagamento' });
  }
};

const updatePaymentStatus = async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;
    const payment = await Payment.findByPk(id);
    if (!payment) {
      return res.status(404).json({ error: 'Pagamento não encontrado' });
    }
    if (!['pending', 'confirmed', 'cancelled'].includes(status)) {
      return res.status(400).json({ error: 'Status inválido' });
    }
    payment.status = status;
    await payment.save();
    return res.status(200).json(payment);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao atualizar status do pagamento' });
  }
};

module.exports = {
  registerPayment,
  updatePaymentStatus,
};
