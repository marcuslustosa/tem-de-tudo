/**
 * notifications.js - Rotas para notificações de clientes e empresas
 */

const express = require('express');
const router = express.Router();
const notificationsController = require('../controllers/notificationsController');

// Adicionar notificação
router.post('/', notificationsController.addNotification);

// Buscar notificações de usuário
router.get('/user/:userId', notificationsController.getUserNotifications);

// Buscar notificações de empresa
router.get('/company/:companyId', notificationsController.getCompanyNotifications);

// Marcar notificação como lida
router.put('/read/:id', notificationsController.markAsRead);

module.exports = router;
