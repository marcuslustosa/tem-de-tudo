/**
 * api.test.js - Testes automatizados para endpoints principais do backend
 * Usando Jest e Supertest
 */

const request = require('supertest');
const app = require('../backend/app');
const { sequelize, Company, User, Payment } = require('../backend/models');

describe('API Tem de Tudo - Testes principais', () => {
  let clientToken = '';
  let companyId = '';
  let paymentId = '';

  // Limpar banco antes dos testes para evitar conflitos
  beforeAll(async () => {
    await sequelize.sync({ force: true });
  });

  // Teste cadastro de cliente
  test('POST /api/auth/register - Cadastro de cliente', async () => {
    const email = `cliente${Date.now()}@teste.com`;
    const res = await request(app)
      .post('/api/auth/register')
      .send({
        name: 'Teste Cliente',
        email,
        password: 'senha123'
      });
    expect(res.statusCode).toBe(201);
    expect(res.body.message).toBe('Usuário registrado com sucesso.');
  });

  // Teste login de cliente
  test('POST /api/auth/login - Login de cliente', async () => {
    const email = `cliente${Date.now()}@teste.com`;
    // Primeiro registra o usuário para garantir que existe
    await request(app)
      .post('/api/auth/register')
      .send({
        name: 'Teste Cliente',
        email,
        password: 'senha123'
      });
    // Aguarda 500ms para garantir commit no banco
    await new Promise(resolve => setTimeout(resolve, 500));
    const res = await request(app)
      .post('/api/auth/login')
      .send({
        email,
        password: 'senha123'
      });
    expect(res.statusCode).toBe(200);
    expect(res.body.token).toBeDefined();
    clientToken = res.body.token;
  });

  // Teste solicitação de cadastro de empresa
  test('POST /api/companies/request - Solicitação de cadastro de empresa', async () => {
    const email = `empresa${Date.now()}@teste.com`;
    const res = await request(app)
      .post('/api/companies/request')
      .send({
        name: `Empresa Teste ${Date.now()}`,
        description: 'Descrição da empresa teste',
        email,
        phone: '123456789',
        address: 'Rua Teste, 123'
      });
    expect(res.statusCode).toBe(201);
    expect(res.body.message).toBe('Cadastro solicitado com sucesso.');
    companyId = res.body.companyId;
  });

  // Teste registro de pagamento
  test('POST /api/payments - Registro de pagamento', async () => {
    const res = await request(app)
      .post('/api/payments')
      .send({
        companyId,
        amount: 100,
        paymentMethod: 'Mercado Pago',
        transactionId: `tx${Date.now()}`
      });
    expect(res.statusCode).toBe(201);
    expect(res.body.message).toBe('Pagamento registrado.');
    paymentId = res.body.paymentId;
  });

  // Teste atualização status pagamento
  test('PUT /api/payments/:id/status - Atualizar status pagamento', async () => {
    const res = await request(app)
      .put(`/api/payments/${paymentId}/status`)
      .send({ status: 'approved' });
    expect(res.statusCode).toBe(200);
    expect(res.body.message).toBe('Status do pagamento atualizado.');
  });

  // Teste confirmação de pagamento da empresa
  test('PUT /api/companies/confirm-payment/:id - Confirmar pagamento empresa', async () => {
    const res = await request(app)
      .put(`/api/companies/confirm-payment/${companyId}`);
    expect(res.statusCode).toBe(200);
    expect(res.body.message).toBe('Pagamento confirmado para a empresa.');
  });

  // Teste aprovação de empresa (perfil master)
  test('PUT /api/companies/approve/:id - Aprovar empresa', async () => {
    const res = await request(app)
      .put(`/api/companies/approve/${companyId}`);
    expect(res.statusCode).toBe(200);
    expect(res.body.message).toBe('Empresa aprovada e liberada para anunciar.');
  });

  // Teste geração de QR Code
  test('POST /api/qrCodes/generate - Gerar QR Code para usuário', async () => {
    // Primeiro registra um usuário para gerar QR Code
    const registerRes = await request(app)
      .post('/api/auth/register')
      .send({
        name: 'Teste QR',
        email: `qr${Date.now()}@teste.com`,
        password: 'senha123'
      });
    const userId = registerRes.body.userId || 1;
    const res = await request(app)
      .post('/api/qrCodes/generate')
      .send({ userId });
    expect(res.statusCode).toBe(200);
    expect(res.body.qrCodeData).toBeDefined();
  });

  // Teste validação de QR Code
  test('POST /api/qrCodes/validate - Validar QR Code', async () => {
    const res = await request(app)
      .post('/api/qrCodes/validate')
      .send({ qrCodeData: 'dummyData' });
    expect(res.statusCode).toBe(200);
    expect(res.body.valid).toBeDefined();
  });
});
