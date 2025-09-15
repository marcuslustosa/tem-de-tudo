/**
 * points.js - Rotas para sistema de pontos e recompensas
 */

const express = require('express');
const router = express.Router();
const pointsController = require('../controllers/pointsController');

// Adicionar pontos
router.post('/', pointsController.addPoints);

// Resgatar pontos
router.put('/redeem/:id', pointsController.redeemPoints);

// Buscar pontos do usuário
router.get('/user/:userId', pointsController.getUserPoints);

module.exports = router;
