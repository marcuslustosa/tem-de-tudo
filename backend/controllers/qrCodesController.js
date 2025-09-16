/**
 * qrCodesController.js - Controlador para geração e validação de QR Codes
 */

const qrCodeService = require('../services/qrCodeService');

const generateQRCode = async (req, res) => {
  try {
    const { userId } = req.body;
    if (!userId) {
      return res.status(400).json({ error: 'Parâmetros insuficientes' });
    }
    const data = { userId, timestamp: Date.now() };
    const qrCode = await qrCodeService.generateQRCode(data);
    return res.status(200).json({ qrCodeData: qrCode });
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao gerar QR Code' });
  }
};

const validateQRCode = async (req, res) => {
  try {
    const { qrCodeData } = req.body;
    if (!qrCodeData) {
      return res.status(400).json({ error: 'QR Code não fornecido' });
    }
    const data = await qrCodeService.validateQRCode(qrCodeData);
    return res.status(200).json({ valid: data });
  } catch (error) {
    return res.status(400).json({ error: 'QR Code inválido' });
  }
};

module.exports = {
  generateQRCode,
  validateQRCode,
};
