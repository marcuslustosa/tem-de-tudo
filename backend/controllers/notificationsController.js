/**
 * notificationsController.js - Controlador para notificações de clientes e empresas
 */

const Notification = require('../models/Notification');

const addNotification = async (req, res) => {
  try {
    const { userId, companyId, message } = req.body;
    if (!message) {
      return res.status(400).json({ error: 'Mensagem é obrigatória' });
    }
    const notification = await Notification.create({
      userId: userId || null,
      companyId: companyId || null,
      message,
      read: false,
    });
    return res.status(201).json(notification);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao adicionar notificação' });
  }
};

const getUserNotifications = async (req, res) => {
  try {
    const { userId } = req.params;
    const notifications = await Notification.findAll({
      where: { userId },
      order: [['createdAt', 'DESC']],
    });
    return res.status(200).json(notifications);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao buscar notificações' });
  }
};

const getCompanyNotifications = async (req, res) => {
  try {
    const { companyId } = req.params;
    const notifications = await Notification.findAll({
      where: { companyId },
      order: [['createdAt', 'DESC']],
    });
    return res.status(200).json(notifications);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao buscar notificações' });
  }
};

const markAsRead = async (req, res) => {
  try {
    const { id } = req.params;
    const notification = await Notification.findByPk(id);
    if (!notification) {
      return res.status(404).json({ error: 'Notificação não encontrada' });
    }
    notification.read = true;
    await notification.save();
    return res.status(200).json(notification);
  } catch (error) {
    return res.status(500).json({ error: 'Erro ao marcar notificação como lida' });
  }
};

module.exports = {
  addNotification,
  getUserNotifications,
  getCompanyNotifications,
  markAsRead,
};
