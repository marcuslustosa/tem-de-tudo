/**
 * seed.js - Script para popular banco com dados fictícios
 * Cria 3 clientes e 5 empresas para demonstração
 */

const { sequelize, User, Company } = require('./models');
const bcrypt = require('bcrypt');

async function seed() {
  try {
    await sequelize.sync({ force: true }); // Reseta o banco

    // Criar clientes fictícios
    const passwordHash = await bcrypt.hash('senha123', 10);
    const clients = [
      { name: 'Cliente Um', email: 'cliente1@example.com', passwordHash },
      { name: 'Cliente Dois', email: 'cliente2@example.com', passwordHash },
      { name: 'Cliente Três', email: 'cliente3@example.com', passwordHash },
    ];
    await User.bulkCreate(clients);

    // Criar empresas fictícias
    const companies = [
      { name: 'Empresa A', email: 'empresaA@example.com', phone: '1111-1111', address: 'Rua A, 123', approved: true, paymentConfirmed: true },
      { name: 'Empresa B', email: 'empresaB@example.com', phone: '2222-2222', address: 'Rua B, 456', approved: true, paymentConfirmed: true },
      { name: 'Empresa C', email: 'empresaC@example.com', phone: '3333-3333', address: 'Rua C, 789', approved: false, paymentConfirmed: false },
      { name: 'Empresa D', email: 'empresaD@example.com', phone: '4444-4444', address: 'Rua D, 101', approved: true, paymentConfirmed: true },
      { name: 'Empresa E', email: 'empresaE@example.com', phone: '5555-5555', address: 'Rua E, 202', approved: false, paymentConfirmed: false },
    ];
    await Company.bulkCreate(companies);

    console.log('Banco populado com dados fictícios com sucesso.');
    process.exit(0);
  } catch (error) {
    console.error('Erro ao popular banco:', error);
    process.exit(1);
  }
}

seed();
