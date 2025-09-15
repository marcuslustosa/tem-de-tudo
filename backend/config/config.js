/**
 * config.js - Configurações do sistema, tokens e credenciais
 * Insira aqui as credenciais para sistemas de pagamento e outras configurações.
 */

module.exports = {
  database: {
    dialect: 'sqlite',
    storage: './database/database.sqlite',
    logging: false,
  },
  payment: {
    mercadoPagoToken: '', // Insira o token do Mercado Pago aqui
    pagSeguroToken: '',   // Insira o token do PagSeguro aqui
  },
  jwtSecret: 'your_jwt_secret_key', // Chave secreta para JWT (autenticação)
};
