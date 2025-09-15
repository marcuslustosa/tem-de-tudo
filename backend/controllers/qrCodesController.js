/**
 * qrCodesController.js - Controlador para geração e validação de QR Codes
 */

const qrCodeService = require('../services/qrCodeService');

const generateQRCode = async (req, res) => {
  try {
    const { userId, companyId, points } = req.body;
    if (!userId || !companyId || !points) {
      return res.status(400).json({ error: 'Parâmetros insuficientes' });
    }
    const data = { userId, companyId, points, timestamp: Date.now() };
    const qrCode = await qrCodeService.generateQRCode(data);
    return res.status(201).json({ qrCode });
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao gerar QR Code' });
  }
};

const validateQRCode = (req, res) => {
  try {
    const { qrCodeString } = req.body;
    if (!qrCodeString) {
      return res.status(400).json({ error: 'QR Code não fornecido' });
    }
    const data = qrCodeService.validateQRCode(qrCodeString);
    return res.status(200).json({ data });
  } catch (error) {
    return res.status(400).json({ error: 'QR Code inválido' });
  }
};

module.exports = {
  generateQRCode,
  validateQRCode,
};
