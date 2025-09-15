/**
 * reviewsController.js - Controlador para avaliações de empresas e serviços
 */

const Review = require('../models/Review');

const addReview = async (req, res) => {
  try {
    const { userId, companyId, rating, comment } = req.body;
    if (!userId || !companyId || !rating) {
      return res.status(400).json({ error: 'Parâmetros insuficientes' });
    }
    if (rating < 1 || rating > 5) {
      return res.status(400).json({ error: 'Avaliação deve ser entre 1 e 5' });
    }
    const review = await Review.create({
      userId,
      companyId,
      rating,
      comment,
    });
    return res.status(201).json(review);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao adicionar avaliação' });
  }
};

const getCompanyReviews = async (req, res) => {
  try {
    const { companyId } = req.params;
    const reviews = await Review.findAll({
      where: { companyId },
    });
    return res.status(200).json(reviews);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao buscar avaliações' });
  }
};

module.exports = {
  addReview,
  getCompanyReviews,
};
