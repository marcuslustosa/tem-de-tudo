const { User, Company, Payment, Review, Notification } = require('../models');

module.exports = {
  dashboard: async (req, res) => {
    try {
      const totalUsers = await User.count();
      const totalCompanies = await Company.count();
      const totalPayments = await Payment.count();
      res.json({ totalUsers, totalCompanies, totalPayments });
    } catch (error) {
      res.status(500).json({ error: 'Erro ao carregar dashboard' });
    }
  },

  listCompanies: async (req, res) => {
    try {
      const companies = await Company.findAll();
      res.json(companies);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao listar empresas' });
    }
  },

  updateCompany: async (req, res) => {
    try {
      const { id } = req.params;
      const updates = req.body;
      const company = await Company.findByPk(id);
      if (!company) return res.status(404).json({ error: 'Empresa não encontrada' });
      await company.update(updates);
      res.json(company);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao atualizar empresa' });
    }
  },

  deleteCompany: async (req, res) => {
    try {
      const { id } = req.params;
      const company = await Company.findByPk(id);
      if (!company) return res.status(404).json({ error: 'Empresa não encontrada' });
      await company.destroy();
      res.json({ message: 'Empresa deletada com sucesso' });
    } catch (error) {
      res.status(500).json({ error: 'Erro ao deletar empresa' });
    }
  },

  listUsers: async (req, res) => {
    try {
      const users = await User.findAll();
      res.json(users);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao listar usuários' });
    }
  },

  updateUser: async (req, res) => {
    try {
      const { id } = req.params;
      const updates = req.body;
      const user = await User.findByPk(id);
      if (!user) return res.status(404).json({ error: 'Usuário não encontrado' });
      await user.update(updates);
      res.json(user);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao atualizar usuário' });
    }
  },

  deleteUser: async (req, res) => {
    try {
      const { id } = req.params;
      const user = await User.findByPk(id);
      if (!user) return res.status(404).json({ error: 'Usuário não encontrado' });
      await user.destroy();
      res.json({ message: 'Usuário deletado com sucesso' });
    } catch (error) {
      res.status(500).json({ error: 'Erro ao deletar usuário' });
    }
  },

  listPayments: async (req, res) => {
    try {
      const payments = await Payment.findAll();
      res.json(payments);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao listar pagamentos' });
    }
  },

  updatePayment: async (req, res) => {
    try {
      const { id } = req.params;
      const updates = req.body;
      const payment = await Payment.findByPk(id);
      if (!payment) return res.status(404).json({ error: 'Pagamento não encontrado' });
      await payment.update(updates);
      res.json(payment);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao atualizar pagamento' });
    }
  },

  listApprovals: async (req, res) => {
    try {
      const approvals = await Company.findAll({ where: { approved: false } });
      res.json(approvals);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao listar aprovações' });
    }
  },

  approveCompany: async (req, res) => {
    try {
      const { id } = req.params;
      const company = await Company.findByPk(id);
      if (!company) return res.status(404).json({ error: 'Empresa não encontrada' });
      company.approved = true;
      await company.save();
      res.json(company);
    } catch (error) {
      res.status(500).json({ error: 'Erro ao aprovar empresa' });
    }
  },

  getReports: async (req, res) => {
    try {
      // Placeholder for report generation logic
      res.json({ message: 'Relatórios não implementados ainda' });
    } catch (error) {
      res.status(500).json({ error: 'Erro ao gerar relatórios' });
    }
  },
};
