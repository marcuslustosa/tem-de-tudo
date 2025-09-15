/**
 * pointsController.js - Controlador para sistema de pontos e recompensas
 */

const Point = require('../models/Point');

const addPoints = async (req, res) => {
  try {
    const { userId, companyId, points, description } = req.body;
    if (!userId || !companyId || !points) {
      return res.status(400).json({ error: 'Parâmetros insuficientes' });
    }
    const pointEntry = await Point.create({
      userId,
      companyId,
      points,
      description,
      redeemed: false,
    });
    return res.status(201).json(pointEntry);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao adicionar pontos' });
  }
};

const redeemPoints = async (req, res) => {
  try {
    const { id } = req.params;
    const pointEntry = await Point.findByPk(id);
    if (!pointEntry) {
      return res.status(404).json({ error: 'Registro de pontos não encontrado' });
    }
    if (pointEntry.redeemed) {
      return res.status(400).json({ error: 'Pontos já resgatados' });
    }
    pointEntry.redeemed = true;
    await pointEntry.save();
    return res.status(200).json(pointEntry);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao resgatar pontos' });
  }
};

const getUserPoints = async (req, res) => {
  try {
    const { userId } = req.params;
    const points = await Point.findAll({
      where: { userId, redeemed: false },
    });
    return res.status(200).json(points);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao buscar pontos do usuário' });
  }
};

module.exports = {
  addPoints,
  redeemPoints,
  getUserPoints,
};
