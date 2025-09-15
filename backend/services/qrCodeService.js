/**
 * qrCodeService.js - Serviço para geração e validação de QR Codes
 */

const QRCode = require('qrcode');

const generateQRCode = async (data) => {
  try {
    const qrCodeDataURL = await QRCode.toDataURL(JSON.stringify(data));
    return qrCodeDataURL;
  } catch (error) {
    throw new Error('Erro ao gerar QR Code');
  }
};

const validateQRCode = (qrCodeString) => {
  try {
    const data = JSON.parse(qrCodeString);
    // Aqui pode-se adicionar validações adicionais conforme a estrutura esperada
    return data;
  } catch (error) {
    throw new Error('QR Code inválido');
  }
};

module.exports = {
  generateQRCode,
  validateQRCode,
};
