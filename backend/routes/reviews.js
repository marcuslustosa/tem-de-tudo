/**
 * reviews.js - Rotas para avaliações de empresas e serviços
 */

const express = require('express');
const router = express.Router();
const reviewsController = require('../controllers/reviewsController');

// Adicionar avaliação
router.post('/', reviewsController.addReview);

// Buscar avaliações de uma empresa
router.get('/company/:companyId', reviewsController.getCompanyReviews);

module.exports = router;
