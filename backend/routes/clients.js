/**
 * clients.js - Rotas para operações de clientes (usuários)
 */

const express = require('express');
const router = express.Router();
const clientsController = require('../controllers/clientsController');

// Obter perfil do cliente pelo ID
router.get('/:id', clientsController.getProfile);

// Atualizar perfil do cliente
router.put('/:id', clientsController.updateProfile);

// Listar todos os clientes (perfil master)
router.get('/', clientsController.listClients);

module.exports = router;
