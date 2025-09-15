/**
 * clientsController.js - Controlador para operações de clientes (usuários)
 */

const { User } = require('../models');
const bcrypt = require('bcrypt');

const clientsController = {
  // Obter perfil do cliente pelo ID
  async getProfile(req, res) {
    try {
      const userId = req.params.id;
      const user = await User.findByPk(userId, {
        attributes: ['id', 'name', 'email', 'points', 'role', 'createdAt'],
      });
      if (!user) {
        return res.status(404).json({ error: 'Cliente não encontrado.' });
      }
      return res.json(user);
    } catch (error) {
      console.error('Erro ao obter perfil:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  },

  // Atualizar perfil do cliente
  async updateProfile(req, res) {
    try {
      const userId = req.params.id;
      const { name, email, password } = req.body;

      const user = await User.findByPk(userId);
      if (!user) {
        return res.status(404).json({ error: 'Cliente não encontrado.' });
      }

      if (name) user.name = name;
      if (email) user.email = email;
      if (password) {
        user.passwordHash = await bcrypt.hash(password, 10);
      }

      await user.save();
      return res.json({ message: 'Perfil atualizado com sucesso.' });
    } catch (error) {
      console.error('Erro ao atualizar perfil:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  },

  // Listar todos os clientes (para perfil master)
  async listClients(req, res) {
    try {
      const clients = await User.findAll({
        where: { role: 'client' },
        attributes: ['id', 'name', 'email', 'points', 'createdAt'],
      });
      return res.json(clients);
    } catch (error) {
      console.error('Erro ao listar clientes:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  },
};

module.exports = clientsController;
